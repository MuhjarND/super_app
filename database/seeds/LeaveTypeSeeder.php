<?php

use Illuminate\Database\Seeder;
use App\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['code' => 'CT', 'name' => 'Cuti Tahunan', 'description' => 'Hak 12 hari kerja per tahun.', 'requires_balance' => true, 'requires_document' => false, 'requires_verification' => false, 'requires_ppk_approval' => false, 'max_days' => 12, 'service_years_required' => 1, 'status' => 'active'],
            ['code' => 'CB', 'name' => 'Cuti Besar', 'description' => 'Cuti besar setelah masa kerja 5 tahun.', 'requires_balance' => false, 'requires_document' => false, 'requires_verification' => false, 'requires_ppk_approval' => true, 'max_months' => 3, 'service_years_required' => 5, 'status' => 'active'],
            ['code' => 'CS', 'name' => 'Cuti Sakit', 'description' => 'Cuti sakit dengan surat dokter bila lebih dari satu hari.', 'requires_balance' => false, 'requires_document' => true, 'requires_verification' => true, 'requires_ppk_approval' => false, 'status' => 'active'],
            ['code' => 'CM', 'name' => 'Cuti Melahirkan', 'description' => 'Cuti melahirkan maksimal tiga bulan.', 'requires_balance' => false, 'requires_document' => true, 'requires_verification' => true, 'requires_ppk_approval' => false, 'max_months' => 3, 'status' => 'active'],
            ['code' => 'CAP', 'name' => 'Cuti Karena Alasan Penting', 'description' => 'Cuti dengan alasan penting dan dokumen sesuai policy.', 'requires_balance' => false, 'requires_document' => true, 'requires_verification' => true, 'requires_ppk_approval' => false, 'status' => 'active'],
            ['code' => 'CBS', 'name' => 'Cuti Bersama', 'description' => 'Cuti bersama yang dikelola terpusat.', 'requires_balance' => false, 'requires_document' => false, 'requires_verification' => false, 'requires_ppk_approval' => false, 'status' => 'active'],
            ['code' => 'CLTN', 'name' => 'Cuti di Luar Tanggungan Negara', 'description' => 'Cuti khusus dengan approval pejabat berwenang.', 'requires_balance' => false, 'requires_document' => true, 'requires_verification' => true, 'requires_ppk_approval' => true, 'status' => 'active'],
        ];
        foreach ($types as $type) { LeaveType::updateOrCreate(['code' => $type['code']], $type); }
    }
}
