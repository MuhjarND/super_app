@extends('layouts.app')

@section('title', 'Rekap Saldo Cuti')

@push('styles')
<style>
    .leave-balance-report-table {
        min-width: 1340px;
    }

    .leave-balance-rule {
        color: #64748b;
        font-size: 0.74rem;
        line-height: 1.35;
        max-width: 260px;
    }

    .leave-balance-number {
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }

    .leave-balance-remaining {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        border-radius: 999px;
        padding: 5px 10px;
        font-weight: 900;
    }

    .leave-balance-action {
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
@include('admin._alerts')
@php
    $selectedBalanceYear = (int) old('year', $filters['year'] ?? now()->year);
    $previousBalanceYear = $selectedBalanceYear - 1;
    $twoYearsAgoBalanceYear = $selectedBalanceYear - 2;
    $canEditBalanceRecap = auth()->user()->canAccessLeaveBalanceReport();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
    <div>
        <h3 class="mb-1">Rekap Saldo Cuti</h3>
        <p class="text-muted mb-0">Total saldo cuti pegawai dihitung mengikuti ketentuan SE Sekma Nomor 13 Tahun 2019.</p>
    </div>
    <div class="app-action-group">
        <button type="button" class="btn btn-primary" id="openAnnualBalanceCreate" data-toggle="modal" data-target="#annualBalanceModal">
            <i class="fas fa-edit mr-1"></i> Input Rekap
        </button>
        <a href="{{ route('cuti.balances.pdf', request()->query()) }}" class="app-icon-btn pdf" title="PDF">
            <i class="fas fa-file-pdf"></i>
        </a>
        <a href="{{ route('cuti.balances.excel', request()->query()) }}" class="app-icon-btn download" title="Excel">
            <i class="fas fa-file-excel"></i>
        </a>
    </div>
</div>

<div class="modal fade" id="annualBalanceModal" tabindex="-1" aria-labelledby="annualBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="annualBalanceModalLabel">Input Rekap Cuti Tahunan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('cuti.balances.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="annualBalanceUser">Pegawai</label>
                        <select name="user_id" id="annualBalanceUser" class="form-control @error('user_id') is-invalid @enderror" required>
                            <option value="">Pilih pegawai</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (string) old('user_id') === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}{{ $employee->nip ? ' - ' . $employee->nip : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="annualBalanceYear">Tahun</label>
                            <input type="number" name="year" id="annualBalanceYear" class="form-control @error('year') is-invalid @enderror" min="2000" max="{{ now()->year }}" value="{{ old('year', $filters['year'] ?? now()->year) }}" required>
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="annualBalanceCarryPrevious"><span data-annual-year-label="previous">{{ $previousBalanceYear }}</span></label>
                            <input type="number" name="carry_forward_previous_year" id="annualBalanceCarryPrevious" class="form-control @error('carry_forward_previous_year') is-invalid @enderror" min="0" max="6" value="{{ old('carry_forward_previous_year', 0) }}" required>
                            @error('carry_forward_previous_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="annualBalanceCarryTwoYearsAgo"><span data-annual-year-label="two-years-ago">{{ $twoYearsAgoBalanceYear }}</span></label>
                            <input type="number" name="carry_forward_two_years_ago" id="annualBalanceCarryTwoYearsAgo" class="form-control @error('carry_forward_two_years_ago') is-invalid @enderror" min="0" max="6" value="{{ old('carry_forward_two_years_ago', 0) }}" required>
                            @error('carry_forward_two_years_ago')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted d-block mt-1" id="annualBalanceTwoYearsHint">Hanya berlaku untuk kondisi tidak cuti dua tahun berturut-turut.</small>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="annualBalanceUsed">Terpakai</label>
                            <input type="number" name="used_days" id="annualBalanceUsed" class="form-control @error('used_days') is-invalid @enderror" min="0" max="24" value="{{ old('used_days', 0) }}" required>
                            @error('used_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="unused_two_consecutive_years" value="1" id="annualBalanceUnusedTwoYears" class="custom-control-input @error('unused_two_consecutive_years') is-invalid @enderror" {{ old('unused_two_consecutive_years') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="annualBalanceUnusedTwoYears">
                                Tidak mengambil cuti tahunan sama sekali pada <span data-annual-year-label="two-years-ago">{{ $twoYearsAgoBalanceYear }}</span> dan <span data-annual-year-label="previous">{{ $previousBalanceYear }}</span>
                            </label>
                            @error('unused_two_consecutive_years')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="alert alert-light border mb-0 small text-muted">
                        Hak <span data-annual-year-label="current">{{ $selectedBalanceYear }}</span> ditetapkan 12 hari. Sisa dua tahun sebelumnya hanya dihitung untuk kondisi tidak mengambil cuti tahunan sama sekali selama dua tahun berturut-turut; selain itu, sisa tersebut hangus pada tahun berjalan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> <span id="annualBalanceSubmitLabel">Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('cuti.balances.index') }}">
            <div class="row">
                <div class="col-md-2 form-group mb-md-0">
                    <input type="number" name="year" class="form-control" value="{{ $filters['year'] ?? now()->year }}" placeholder="Tahun">
                </div>
                <div class="col-md-3 form-group mb-md-0">
                    <select name="leave_type_id" class="form-control">
                        <option value="">Semua Jenis</option>
                        @foreach($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}" {{ (string) ($filters['leave_type_id'] ?? '') === (string) $leaveType->id ? 'selected' : '' }}>{{ $leaveType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group mb-md-0">
                    <select name="unit_id" class="form-control">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ (string) ($filters['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>{{ $unit->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group mb-md-0">
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nama / NIP">
                </div>
                <div class="col-md-2 d-flex" style="gap:6px;">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    <a href="{{ route('cuti.balances.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 leave-balance-report-table">
                <thead>
                    <tr>
                        <th>Pegawai</th>
                        <th>Unit</th>
                        <th>Jenis Cuti</th>
                        <th>Tahun</th>
                        <th>{{ $selectedBalanceYear }}</th>
                        <th>{{ $previousBalanceYear }}</th>
                        <th>{{ $twoYearsAgoBalanceYear }}</th>
                        <th>Total Hak</th>
                        <th>Terpakai</th>
                        <th>Tertahan</th>
                        <th>Sisa</th>
                        <th>Ketentuan</th>
                        @if($canEditBalanceRecap)
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $balance)
                        @php
                            $isAnnualBalance = optional($balance->leaveType)->code === \App\LeaveType::CODE_TAHUNAN;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ optional($balance->user)->name }}</strong><br>
                                <small class="text-muted">{{ optional($balance->user)->nip ?: '-' }}</small>
                            </td>
                            <td>{{ optional(optional($balance->user)->unit)->nama ?: '-' }}</td>
                            <td>{{ optional($balance->leaveType)->name }}</td>
                            <td>{{ $balance->year }}</td>
                            <td><span class="leave-balance-number">{{ $balance->entitlement }} hari</span></td>
                            <td><span class="leave-balance-number">{{ $balance->carry_forward_previous_year }} hari</span></td>
                            <td><span class="leave-balance-number">{{ $balance->carry_forward_two_years_ago }} hari</span></td>
                            <td><span class="leave-balance-number">{{ $balance->total_balance }} hari</span></td>
                            <td>{{ $balance->used_days }} hari</td>
                            <td>{{ $balance->reserved_days }} hari</td>
                            <td>
                                <span class="badge leave-balance-remaining badge-{{ $balance->remaining_balance > 0 ? 'success' : 'secondary' }}">
                                    {{ $balance->remaining_balance }} hari
                                </span>
                            </td>
                            <td><div class="leave-balance-rule">{{ $balance->rule_note }}</div></td>
                            @if($canEditBalanceRecap)
                                <td class="leave-balance-action">
                                    @if($isAnnualBalance)
                                        <button type="button"
                                            class="app-icon-btn edit js-edit-annual-balance"
                                            title="Edit rekap cuti tahunan"
                                            data-toggle="modal"
                                            data-target="#annualBalanceModal"
                                            data-user-id="{{ optional($balance->user)->id }}"
                                            data-user-name="{{ optional($balance->user)->name }}"
                                            data-year="{{ $balance->year }}"
                                            data-carry-previous="{{ $balance->carry_forward_previous_year }}"
                                            data-carry-two-years-ago="{{ $balance->carry_forward_two_years_ago }}"
                                            data-unused-two-years="{{ $balance->unused_two_consecutive_years ? 1 : 0 }}"
                                            data-used-days="{{ $balance->used_days }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEditBalanceRecap ? 13 : 12 }}" class="text-center text-muted py-4">Belum ada data saldo cuti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">{{ $balances->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        var $yearInput = $('#annualBalanceYear');
        var $annualModal = $('#annualBalanceModal');
        var $previousCarryInput = $('#annualBalanceCarryPrevious');
        var $twoYearsCarryInput = $('#annualBalanceCarryTwoYearsAgo');
        var $unusedTwoYearsInput = $('#annualBalanceUnusedTwoYears');
        var updateAnnualYearLabels = function () {
            var year = parseInt($yearInput.val(), 10) || {{ $selectedBalanceYear }};
            $('[data-annual-year-label="current"]').text(year);
            $('[data-annual-year-label="previous"]').text(year - 1);
            $('[data-annual-year-label="two-years-ago"]').text(year - 2);
        };
        var updateTwoYearsAgoAvailability = function () {
            var canUseTwoYearsAgo = $unusedTwoYearsInput.is(':checked');

            $previousCarryInput.prop('readonly', canUseTwoYearsAgo);
            $twoYearsCarryInput.prop('readonly', true);
            if (canUseTwoYearsAgo) {
                $previousCarryInput.val(6);
                $twoYearsCarryInput.val(6);
            } else {
                $twoYearsCarryInput.val(0);
            }
        };
        var setAnnualModalMode = function (mode, data) {
            var isEdit = mode === 'edit';
            $('#annualBalanceModalLabel').text(isEdit ? 'Edit Rekap Cuti Tahunan' : 'Input Rekap Cuti Tahunan');
            $('#annualBalanceSubmitLabel').text(isEdit ? 'Perbarui' : 'Simpan');

            if (isEdit && data) {
                $('#annualBalanceUser').val(data.userId || '');
                $yearInput.val(data.year || {{ $selectedBalanceYear }});
                $previousCarryInput.val(data.carryPrevious || 0);
                $twoYearsCarryInput.val(data.carryTwoYearsAgo || 0);
                $unusedTwoYearsInput.prop('checked', data.unusedTwoYears === 1 || data.unusedTwoYears === '1' || data.unusedTwoYears === true);
                $('#annualBalanceUsed').val(data.usedDays || 0);
            } else {
                $('#annualBalanceUser').val('');
                $yearInput.val({{ $selectedBalanceYear }});
                $previousCarryInput.val(0);
                $twoYearsCarryInput.val(0);
                $unusedTwoYearsInput.prop('checked', false);
                $('#annualBalanceUsed').val(0);
            }

            updateAnnualYearLabels();
            updateTwoYearsAgoAvailability();
        };

        $yearInput.on('input change', updateAnnualYearLabels);
        $previousCarryInput.on('input change', updateTwoYearsAgoAvailability);
        $unusedTwoYearsInput.on('change', updateTwoYearsAgoAvailability);
        $('#openAnnualBalanceCreate').on('click', function () {
            setAnnualModalMode('create');
        });
        $('.js-edit-annual-balance').on('click', function () {
            var $button = $(this);
            setAnnualModalMode('edit', {
                userId: $button.data('user-id'),
                year: $button.data('year'),
                carryPrevious: $button.data('carry-previous'),
                carryTwoYearsAgo: $button.data('carry-two-years-ago'),
                unusedTwoYears: $button.data('unused-two-years'),
                usedDays: $button.data('used-days')
            });
        });
        $annualModal.on('hidden.bs.modal', function () {
            setAnnualModalMode('create');
        });
        updateAnnualYearLabels();
        updateTwoYearsAgoAvailability();

        @if($errors->has('user_id') || $errors->has('year') || $errors->has('carry_forward_previous_year') || $errors->has('carry_forward_two_years_ago') || $errors->has('unused_two_consecutive_years') || $errors->has('used_days'))
        $('#annualBalanceModal').modal('show');
        @endif
    });
</script>
@endpush
