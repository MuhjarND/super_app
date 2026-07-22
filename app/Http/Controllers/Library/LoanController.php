<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\BookCopy;
use App\Library\Loan;
use App\Library\LoanItem;
use App\Library\Member;
use App\Library\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with(['member', 'loanItems']);
        $member = null;

        if (!auth()->user()->canManageLibraryModule()) {
            $member = $this->resolveCurrentMember(true);
            $query->where('member_id', $member ? $member->id : 0);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('loan_number', 'like', "%{$request->search}%")
                  ->orWhereHas('member', fn($m) => $m->where('name', 'like', "%{$request->search}%")
                      ->orWhere('member_number', 'like', "%{$request->search}%"));
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('loan_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('loan_date', '<=', $request->date_to);
        }

        // Update status ke terlambat jika sudah lewat due_date
        $overdueQuery = Loan::where('status', 'dipinjam')
            ->where('due_date', '<', Carbon::today());
        if (!auth()->user()->canManageLibraryModule()) {
            $overdueQuery->where('member_id', $member ? $member->id : 0);
        }
        $overdueQuery->update(['status' => 'terlambat']);

        $loans = $query->latest()->paginate(15)->withQueryString();

        return view('library.loans.index', compact('loans'));
    }

    public function create()
    {
        $loanDays    = Setting::get('loan_days', 7);
        $maxBooks    = Setting::get('max_books_per_loan', 3);
        $member = auth()->user()->canManageLibraryModule()
            ? null
            : $this->resolveCurrentMember(true);

        return view('library.loans.create', compact('loanDays', 'maxBooks', 'member'));
    }

    public function store(Request $request)
    {
        $canManage = auth()->user()->canManageLibraryModule();
        $maxBooks = max(1, (int) Setting::get('max_books_per_loan', 3));
        $rules = [
            'note' => 'nullable|string|max:1000',
            'copy_codes' => 'required|array|min:1|max:' . $maxBooks,
            'copy_codes.*' => 'required|string|distinct|exists:library_book_copies,copy_code',
        ];

        if ($canManage) {
            $rules += [
                'member_id' => 'required|exists:library_members,id',
                'loan_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:loan_date',
            ];
        }

        $validated = $request->validate($rules);
        $member = $canManage
            ? Member::findOrFail($validated['member_id'])
            : $this->resolveCurrentMember(true);

        if ($member->status !== 'aktif') {
            return back()->with('error', 'Anggota tidak aktif, tidak dapat meminjam.');
        }

        $loanDate = $canManage ? Carbon::parse($validated['loan_date']) : Carbon::today();
        $dueDate = $canManage
            ? Carbon::parse($validated['due_date'])
            : $loanDate->copy()->addDays((int) Setting::get('loan_days', 7));

        $loan = DB::transaction(function () use ($validated, $member, $loanDate, $dueDate) {
            $copies = BookCopy::whereIn('copy_code', $validated['copy_codes'])
                ->lockForUpdate()
                ->get()
                ->keyBy('copy_code');

            foreach ($validated['copy_codes'] as $code) {
                $copy = $copies->get($code);
                if (!$copy || $copy->status !== 'tersedia') {
                    throw ValidationException::withMessages([
                        'copy_codes' => "Eksemplar {$code} tidak tersedia untuk dipinjam.",
                    ]);
                }
            }

            $loan = Loan::create([
                'loan_number' => Loan::generateNumber(),
                'member_id'  => $member->id,
                'user_id'    => auth()->id(),
                'loan_date'  => $loanDate->toDateString(),
                'due_date'   => $dueDate->toDateString(),
                'status'     => 'dipinjam',
                'note'       => $validated['note'] ?? null,
            ]);

            foreach ($validated['copy_codes'] as $code) {
                $copy = $copies->get($code);
                LoanItem::create([
                    'loan_id'      => $loan->id,
                    'book_copy_id' => $copy->id,
                ]);
                $copy->update(['status' => 'dipinjam']);
            }

            return $loan;
        });

        return redirect()->route('library.loans.show', $loan)->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function show(Loan $loan)
    {
        if (!auth()->user()->canManageLibraryModule()) {
            $this->resolveCurrentMember(true);
        }

        $loan->load(['member.user', 'user', 'loanItems.bookCopy.book', 'returnRecord', 'loanItems.fine']);
        abort_unless($loan->isVisibleTo(auth()->user()), 403);

        return view('library.loans.show', compact('loan'));
    }

    public function destroy(Loan $loan)
    {
        if ($loan->status === 'dikembalikan') {
            return back()->with('error', 'Peminjaman yang sudah dikembalikan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($loan) {
            foreach ($loan->loanItems as $item) {
                $item->bookCopy->update(['status' => 'tersedia']);
            }
            $loan->loanItems()->delete();
            $loan->delete();
        });

        return redirect()->route('library.loans.index')->with('success', 'Data peminjaman dihapus.');
    }

    protected function resolveCurrentMember($create = false)
    {
        $user = auth()->user();
        $member = Member::where('user_id', $user->id)->first();
        if ($member || !$create) {
            return $member;
        }

        $member = null;
        if ($user->email || $user->nip) {
            $member = Member::whereNull('user_id')
                ->where(function ($query) use ($user) {
                    if ($user->email) {
                        $query->where('email', $user->email);
                    }
                    if ($user->nip) {
                        $method = $user->email ? 'orWhere' : 'where';
                        $query->{$method}('member_number', $user->nip);
                    }
                })
                ->first();
        }

        if ($member) {
            $member->update(['user_id' => $user->id]);
            return $member;
        }

        return Member::create([
            'user_id' => $user->id,
            'member_number' => Member::generateNumber(),
            'name' => $user->name,
            'gender' => $this->inferGenderFromNip($user->nip),
            'class_position' => $user->display_jabatan,
            'phone' => $user->no_hp,
            'email' => $user->email,
            'status' => 'aktif',
        ]);
    }

    protected function inferGenderFromNip($nip)
    {
        $digits = preg_replace('/\D+/', '', (string) $nip);

        return strlen($digits) >= 15 && substr($digits, 14, 1) === '2' ? 'P' : 'L';
    }
}
