<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class DocumentQrCodeService
{
    protected $logoDataUri;

    public function dataUri($url, $size = 120)
    {
        $size = max(72, (int) $size);
        $svg = (string) app('qrcode')
            ->format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('H')
            ->generate((string) $url);

        $logo = $this->logoDataUri();
        if ($logo) {
            $svg = $this->embedLogo($svg, $logo, $size);
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate QR sebagai PNG tanpa ketergantungan Imagick.
     *
     * FPDI/FPDF hanya dapat menempelkan gambar raster. Instalasi produksi
     * aplikasi memakai GD, sehingga matriks QR dirender langsung ke PNG dan
     * tetap diberi logo PAPEDA di bagian tengah.
     */
    public function pngBinary($url, $size = 600)
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('Ekstensi GD diperlukan untuk membuat barcode e-sign.');
        }

        $size = max(240, (int) $size);
        $matrix = Encoder::encode((string) $url, ErrorCorrectionLevel::H(), 'UTF-8')->getMatrix();
        $matrixSize = $matrix->getWidth();
        $quietZone = 4;
        $moduleCount = $matrixSize + ($quietZone * 2);
        $moduleSize = max(1, (int) floor($size / $moduleCount));
        $canvasSize = $moduleSize * $moduleCount;
        $image = imagecreatetruecolor($canvasSize, $canvasSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 17, 24, 39);
        imagefilledrectangle($image, 0, 0, $canvasSize, $canvasSize, $white);

        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + $quietZone) * $moduleSize;
                $top = ($y + $quietZone) * $moduleSize;
                imagefilledrectangle($image, $left, $top, $left + $moduleSize - 1, $top + $moduleSize - 1, $black);
            }
        }

        $logoPath = public_path('logo_qr.png');
        if (is_file($logoPath)) {
            $logo = @imagecreatefromstring((string) file_get_contents($logoPath));
            if ($logo) {
                $logoWidth = max(1, (int) round($canvasSize * 0.20));
                $logoHeight = max(1, (int) round(imagesy($logo) * ($logoWidth / max(1, imagesx($logo)))));
                $padding = max(3, (int) round($canvasSize * 0.018));
                $backgroundLeft = (int) floor(($canvasSize - $logoWidth) / 2) - $padding;
                $backgroundTop = (int) floor(($canvasSize - $logoHeight) / 2) - $padding;
                imagefilledrectangle(
                    $image,
                    $backgroundLeft,
                    $backgroundTop,
                    $backgroundLeft + $logoWidth + ($padding * 2),
                    $backgroundTop + $logoHeight + ($padding * 2),
                    $white
                );
                imagecopyresampled(
                    $image,
                    $logo,
                    $backgroundLeft + $padding,
                    $backgroundTop + $padding,
                    0,
                    0,
                    $logoWidth,
                    $logoHeight,
                    imagesx($logo),
                    imagesy($logo)
                );
                imagedestroy($logo);
            }
        }

        ob_start();
        imagepng($image, null, 8);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (!$binary) {
            throw new \RuntimeException('Barcode e-sign gagal dibuat.');
        }

        return $binary;
    }

    public function logoDataUri()
    {
        if ($this->logoDataUri !== null) {
            return $this->logoDataUri ?: null;
        }

        $path = public_path('logo_qr.png');
        if (!is_file($path)) {
            $this->logoDataUri = '';

            return null;
        }

        $binary = $this->optimizedLogoBinary($path);
        if (!$binary) {
            $this->logoDataUri = '';

            return null;
        }

        $this->logoDataUri = 'data:image/png;base64,' . base64_encode($binary);

        return $this->logoDataUri;
    }

    protected function optimizedLogoBinary($path)
    {
        $original = file_get_contents($path);
        if (!$original || !function_exists('imagecreatefromstring')) {
            return $original;
        }

        $source = @imagecreatefromstring($original);
        if (!$source) {
            return $original;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = 96;
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / max(1, $sourceWidth))));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagepng($target, null, 8);
        $optimized = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $optimized ?: $original;
    }

    protected function embedLogo($svg, $logoDataUri, $size)
    {
        $logoSize = round($size * 0.23, 2);
        $padding = round($size * 0.025, 2);
        $backgroundSize = $logoSize + ($padding * 2);
        $logoPosition = round(($size - $logoSize) / 2, 2);
        $backgroundPosition = round(($size - $backgroundSize) / 2, 2);
        $radius = round($size * 0.035, 2);

        $overlay = sprintf(
            '<g id="papeda-qr-logo"><rect x="%1$s" y="%1$s" width="%2$s" height="%2$s" rx="%3$s" ry="%3$s" fill="#ffffff"/><image x="%4$s" y="%4$s" width="%5$s" height="%5$s" preserveAspectRatio="xMidYMid meet" href="%6$s" xlink:href="%6$s"/></g>',
            $backgroundPosition,
            $backgroundSize,
            $radius,
            $logoPosition,
            $logoSize,
            $logoDataUri
        );

        if (strpos($svg, 'xmlns:xlink=') === false) {
            $svg = preg_replace('/<svg\s/', '<svg xmlns:xlink="http://www.w3.org/1999/xlink" ', $svg, 1);
        }

        return preg_replace('/<\/svg>\s*$/', $overlay . '</svg>', $svg, 1);
    }
}
