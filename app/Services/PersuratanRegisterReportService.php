<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PersuratanRegisterReportService
{
    public function periodLabel(Carbon $startDate, Carbon $endDate)
    {
        if ($startDate->isSameMonth($endDate) && $startDate->year === $endDate->year) {
            return 'BULAN ' . strtoupper($startDate->copy()->locale('id')->translatedFormat('F'))
                . ' TAHUN ' . $startDate->year;
        }

        return 'PERIODE '
            . strtoupper($startDate->copy()->locale('id')->translatedFormat('d F Y'))
            . ' S.D. '
            . strtoupper($endDate->copy()->locale('id')->translatedFormat('d F Y'));
    }

    public function incomingRows(Collection $letters)
    {
        return $letters->values()->map(function ($letter, $index) {
            $latestDisposition = $letter->relationLoaded('disposisis')
                ? $letter->disposisis->sortByDesc('created_at')->first()
                : null;
            $classification = $letter->klasifikasiKode;

            return [
                'number' => $index + 1,
                'classification' => $classification
                    ? trim($classification->kode . ' - ' . $classification->nama)
                    : '-',
                'letter_number' => $letter->nomor_surat ?: '-',
                'nature' => ucfirst((string) ($letter->sifat ?: '-')),
                'sender_type' => $letter->opsi_pengirim === 'mahkamah_agung'
                    ? 'Mahkamah Agung'
                    : 'Non Mahkamah Agung',
                'sender' => $letter->pengirim ?: '-',
                'date' => optional($letter->tanggal_surat)->format('Y-m-d') ?: '-',
                'subject' => $letter->perihal ?: '-',
                'status' => $this->incomingStatus($letter->status, $latestDisposition),
                'file_status' => $letter->file_path ? 'Berkas tersedia' : 'Belum ada berkas',
                'creator' => optional($letter->creator)->name ?: '-',
            ];
        });
    }

    public function outgoingRows(Collection $letters)
    {
        return $letters->values()->map(function ($letter, $index) {
            return [
                'number' => $index + 1,
                'letter_number' => $letter->nomor_surat_formatted ?: ($letter->nomor_surat ?: '-'),
                'recipient' => $letter->opsi_penerima === 'external'
                    ? ($letter->penerima_external ?: 'External')
                    : 'Internal',
                'date' => optional($letter->tanggal_surat)->format('Y-m-d') ?: '-',
                'subject' => $letter->perihal ?: '-',
                'status' => $letter->status === 'lengkap' ? 'Selesai' : 'Belum selesai',
                'file_status' => $letter->file_path || $letter->status === 'lengkap'
                    ? 'Berkas tersedia'
                    : 'Belum ada berkas',
                'creator' => optional($letter->creator)->name ?: '-',
            ];
        });
    }

    protected function incomingStatus($status, $latestDisposition = null)
    {
        if ($status === 'selesai') {
            return 'Selesai';
        }

        if ($latestDisposition) {
            return $latestDisposition->tipe === 'naikan' ? 'Dinaikkan' : 'Diteruskan';
        }

        if ($status === 'didisposisi') {
            return 'Didisposisi';
        }

        return $status === 'baru' ? 'Baru' : ucfirst((string) ($status ?: '-'));
    }
}
