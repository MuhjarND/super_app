{{-- Acuan: KOP SURAT PTA PAPUA BARAT.docx, header kantor baru (November 2026). --}}
@php
    $letterheadLogoPath = public_path('images/rapat/logo-kop-pta-papua-barat.png');
    $letterheadLogo = is_file($letterheadLogoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($letterheadLogoPath))
        : null;
@endphp
<style>
    .rapat-letterhead {
        font-family: 'Times New Roman', Times, serif;
        color: #000;
        border-bottom: 3pt double #000;
        padding-bottom: 5pt;
        margin-bottom: 12pt;
        page-break-inside: avoid;
    }
    .rapat-letterhead table { width: 100%; border-collapse: collapse; }
    .rapat-letterhead td { padding: 0; border: 0; vertical-align: middle; }
    .rapat-letterhead .logo-cell { width: 58pt; padding-right: 7pt; }
    .rapat-letterhead .logo-cell img { width: 53pt; height: auto; display: block; }
    .rapat-letterhead .heading-cell { text-align: center; }
    .rapat-letterhead .institution { font-size: 12pt; font-weight: bold; line-height: 1.15; white-space: nowrap; }
    .rapat-letterhead .address { margin-top: 4pt; font-size: 8pt; line-height: 1.2; }
    .rapat-letterhead .address div { margin: 0; padding: 0; }
</style>
<div class="rapat-letterhead" data-letterhead-version="kantor-baru-2026">
    <table>
        <tr>
            @if($letterheadLogo)
                <td class="logo-cell"><img src="{{ $letterheadLogo }}" alt="Logo Pengadilan Tinggi Agama Papua Barat"></td>
            @endif
            <td class="heading-cell">
                <div class="institution">MAHKAMAH AGUNG REPUBLIK INDONESIA</div>
                <div class="institution">DIREKTORAT JENDERAL BADAN PERADILAN AGAMA</div>
                <div class="institution">PENGADILAN TINGGI AGAMA PAPUA BARAT</div>
                <div class="address">
                    <div>Jalan: Brigjen Marinir (Purna) Abraham O Atururi</div>
                    <div>(Kompleks Perkantoran Gubernur Papua Barat), Katebu, Distrik Manokwari Selatan</div>
                    <div>Kabupaten Manokwari, Provinsi Papua Barat Kode Pos: 98315</div>
                    <div>Website: https://pta-papuabarat.go.id &nbsp; email: ptapapuabarat@gmail.com</div>
                </div>
            </td>
        </tr>
    </table>
</div>
