<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MobileModuleMenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request, $module)
    {
        $user = $request->user();
        $menu = $this->buildMenu($user, $module);

        abort_unless($menu, 404);
        abort_unless(!empty($menu['items']), 403);

        return view('mobile-menu.show', [
            'menu' => $menu,
        ]);
    }

    protected function buildMenu($user, $module)
    {
        $isSuperAdmin = $user && $user->isSuperAdmin();

        $menus = [
            'dashboard' => [
                'title' => 'Dashboard',
                'subtitle' => 'Akses cepat ke halaman utama dan pengaturan akun.',
                'icon' => 'fas fa-th-large',
                'tone' => 'indigo',
                'items' => [
                    $this->item('Beranda', route('dashboard'), 'fas fa-home', 'indigo'),
                    $this->item('Profil Saya', route('profile.edit'), 'fas fa-user-cog', 'slate'),
                    $this->item('Authenticator', route('two-factor.edit'), 'fas fa-shield-alt', 'teal'),
                ],
            ],
            'action' => [
                'title' => 'Tindak Lanjut',
                'subtitle' => 'Pusat item kerja yang perlu diproses.',
                'icon' => 'fas fa-bell',
                'tone' => 'red',
                'items' => array_values(array_filter([
                    $user->canAccessUnifiedActionCenter() ? $this->item('Inbox Kerja', route('action-center.index'), 'fas fa-inbox', 'red') : null,
                    ($isSuperAdmin || $user->canAccessApprovalCenter()) ? $this->item('Approval', route('approval.index'), 'fas fa-tasks', 'orange') : null,
                ])),
            ],
            'calendar' => [
                'title' => 'Kalender',
                'subtitle' => 'Agenda lintas modul dalam satu tampilan.',
                'icon' => 'far fa-calendar-alt',
                'tone' => 'blue',
                'items' => $user->canAccessIntegratedCalendar()
                    ? [$this->item('Kalender Terpadu', route('calendar.integrated.index'), 'far fa-calendar-alt', 'blue')]
                    : [],
            ],
            'approval' => [
                'title' => 'Approval',
                'subtitle' => 'Daftar approval dan riwayat keputusan.',
                'icon' => 'fas fa-check-double',
                'tone' => 'teal',
                'items' => ($isSuperAdmin || $user->canAccessApprovalCenter()) ? [
                    $this->item('Tindaklanjuti', route('approval.index'), 'fas fa-tasks', 'teal'),
                    $this->item('Riwayat', route('approval.history'), 'fas fa-history', 'slate'),
                ] : [],
            ],
            'persuratan' => [
                'title' => 'Persuratan',
                'subtitle' => 'Surat masuk, surat keluar, dan template surat.',
                'icon' => 'fas fa-envelope-open-text',
                'tone' => 'indigo',
                'items' => array_values(array_filter([
                    ($isSuperAdmin || $user->canAccessSuratMasukMenu()) ? $this->item('Surat Masuk', route('surat-masuk.index'), 'far fa-envelope', 'indigo') : null,
                    ($isSuperAdmin || $user->canAccessSuratKeluarMenu()) ? $this->item('Surat Keluar', route('surat-keluar.index'), 'far fa-paper-plane', 'blue') : null,
                    ($isSuperAdmin || $user->canAccessSuratTemplateMenu()) ? $this->item('Template Surat', route('surat-template.index'), 'far fa-file-word', 'slate') : null,
                ])),
            ],
            'rapat' => [
                'title' => 'Rapat / Agenda',
                'subtitle' => 'Kelola rapat, notulensi, absensi, dan agenda pimpinan.',
                'icon' => 'fas fa-users',
                'tone' => 'teal',
                'items' => ($isSuperAdmin || $user->canAccessMeetingModule()) ? array_values(array_filter([
                    $this->item('Rapat', route('rapat.index'), 'far fa-calendar-alt', 'teal'),
                    ($isSuperAdmin || $user->canAccessMeetingMinutes()) ? $this->item('Notulensi', route('rapat.notulensi.index'), 'far fa-file-alt', 'indigo') : null,
                    $this->item('Tindak Lanjut', route('rapat.notulensi.follow-ups'), 'fas fa-tasks', 'orange'),
                    $this->item('Absensi', route('rapat.absensi.index'), 'fas fa-clipboard-check', 'green'),
                    $this->item('Laporan', route('rapat.laporan.index'), 'far fa-file-pdf', 'red'),
                    ($isSuperAdmin || $user->canAccessAgendaPimpinan()) ? $this->item('Agenda Pimpinan', route('rapat.agenda.index'), 'fas fa-calendar-day', 'blue') : null,
                    ($isSuperAdmin || $user->canManageVoting()) ? $this->item('E-Voting', route('rapat.voting.index'), 'fas fa-poll', 'slate') : null,
                ])) : [],
            ],
            'cuti' => [
                'title' => 'Cuti',
                'subtitle' => 'Pengajuan, approval, saldo, dan laporan cuti.',
                'icon' => 'fas fa-calendar-check',
                'tone' => 'red',
                'items' => ($isSuperAdmin || $user->canAccessLeaveModule()) ? array_values(array_filter([
                    $this->item('Pengajuan', route('cuti.index'), 'fas fa-calendar-alt', 'red'),
                    ($isSuperAdmin || $user->canAccessLeaveApproval()) ? $this->item('Approval', route('cuti.approval.index'), 'fas fa-user-check', 'teal') : null,
                    $this->item('Rekap Saldo', route('cuti.balances.index'), 'fas fa-wallet', 'blue'),
                    $this->item('Laporan', route('cuti.reports.index'), 'far fa-chart-bar', 'orange'),
                    ($isSuperAdmin || $user->canManageLeaveMasterData()) ? $this->item('Jenis Cuti', route('cuti.master.types.index'), 'far fa-list-alt', 'indigo') : null,
                    ($isSuperAdmin || $user->canManageLeaveMasterData()) ? $this->item('Kebijakan', route('cuti.master.policies.index'), 'fas fa-sliders-h', 'slate') : null,
                    ($isSuperAdmin || $user->canManageLeaveMasterData()) ? $this->item('Cuti Bersama', route('cuti.master.holidays.index'), 'far fa-calendar-check', 'green') : null,
                    ($isSuperAdmin || $user->canManageLeaveMasterData()) ? $this->item('Delegasi', route('cuti.master.delegations.index'), 'fas fa-people-arrows', 'teal') : null,
                ])) : [],
            ],
            'perawatan' => [
                'title' => 'Perawatan',
                'subtitle' => 'Barang, transaksi perawatan, laporan, dan data master.',
                'icon' => 'fas fa-tools',
                'tone' => 'orange',
                'items' => ($isSuperAdmin || $user->canAccessInventoryModule()) ? array_values(array_filter([
                    $this->item('Dashboard', route('perawatan-alat-mesin.index'), 'fas fa-tachometer-alt', 'orange'),
                    $this->item('Master Barang', route('perawatan-alat-mesin.items.index'), 'fas fa-boxes', 'indigo'),
                    $this->item('Transaksi', route('perawatan-alat-mesin.maintenance.index'), 'fas fa-tools', 'teal'),
                    $this->item('Laporan', route('perawatan-alat-mesin.reports.index'), 'fas fa-file-invoice-dollar', 'red'),
                    $this->item('QR Code', route('perawatan-alat-mesin.qrcode.index'), 'fas fa-qrcode', 'slate'),
                    ($isSuperAdmin || $user->canManageInventoryMasterData()) ? $this->item('Satuan', route('perawatan-alat-mesin.master.index', 'units'), 'fas fa-ruler-combined', 'blue') : null,
                    ($isSuperAdmin || $user->canManageInventoryMasterData()) ? $this->item('Kondisi', route('perawatan-alat-mesin.master.index', 'conditions'), 'fas fa-clipboard-check', 'green') : null,
                    ($isSuperAdmin || $user->canManageInventoryMasterData()) ? $this->item('Ruang', route('perawatan-alat-mesin.master.index', 'rooms'), 'fas fa-door-open', 'indigo') : null,
                    ($isSuperAdmin || $user->canManageInventoryMasterData()) ? $this->item('Brand / Merk', route('perawatan-alat-mesin.master.index', 'brands'), 'fas fa-tags', 'orange') : null,
                    ($isSuperAdmin || $user->canManageInventoryMasterData()) ? $this->item('Kuasa Pengguna', route('perawatan-alat-mesin.authority.index'), 'fas fa-user-tie', 'slate') : null,
                ])) : [],
            ],
            'persediaan' => [
                'title' => 'Persediaan',
                'subtitle' => 'Ajukan ATK dan lihat riwayat.',
                'icon' => 'fas fa-warehouse',
                'tone' => 'green',
                'items' => ($isSuperAdmin || $user->canAccessSupplyModule()) ? array_values(array_filter([
                    $this->item('Ajukan Barang', route('persediaan.requests.create'), 'fas fa-shopping-cart', 'indigo'),
                    $this->item('Pengajuan', route('persediaan.requests.index'), 'fas fa-clipboard-list', 'blue'),
                    $this->item('Barang Diambil', route('persediaan.pickups.index'), 'fas fa-box-open', 'orange'),
                    ($isSuperAdmin || $user->canManageSupplyModule()) ? $this->item('Master Barang', route('persediaan.items.index'), 'fas fa-boxes', 'slate') : null,
                ])) : [],
            ],
            'zi' => [
                'title' => 'Progress ZI',
                'subtitle' => 'Rekapan, monitoring kegiatan, pedoman, dan master ZI.',
                'icon' => 'fas fa-chart-line',
                'tone' => 'purple',
                'items' => ($isSuperAdmin || $user->canAccessProgressZiModule()) ? array_values(array_filter([
                    $this->item('Rekapan ZI', route('progress-zi.dashboard'), 'fas fa-chart-line', 'indigo'),
                    $this->item('Monitoring', route('progress-zi.activities.index'), 'fas fa-tasks', 'orange'),
                    $this->item('Pedoman ZI', route('progress-zi.guidelines.index'), 'fas fa-book-open', 'blue'),
                    $this->item('Laporan', route('progress-zi.reports.index'), 'far fa-chart-bar', 'red'),
                    $this->item('Verifikasi', route('progress-zi.verifications.index'), 'fas fa-clipboard-check', 'green'),
                    ($isSuperAdmin || $user->canManageProgressZiMasterData()) ? $this->item('Periode', route('progress-zi.periods.index'), 'far fa-calendar-alt', 'slate') : null,
                    ($isSuperAdmin || $user->canManageProgressZiMasterData()) ? $this->item('Area ZI', route('progress-zi.areas.index'), 'fas fa-layer-group', 'teal') : null,
                ])) : [],
            ],
            'arsip' => [
                'title' => 'Arsip',
                'subtitle' => 'Pusat arsip lintas modul.',
                'icon' => 'far fa-folder-open',
                'tone' => 'slate',
                'items' => ($isSuperAdmin || $user->canAccessArchiveMenu()) ? [
                    $this->item('Arsip Terpadu', route('arsip.index'), 'far fa-folder-open', 'slate'),
                ] : [],
            ],
        ];

        return $menus[$module] ?? null;
    }

    protected function item($label, $url, $icon, $tone)
    {
        return compact('label', 'url', 'icon', 'tone');
    }
}
