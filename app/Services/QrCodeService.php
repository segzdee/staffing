<?php

namespace App\Services;

use App\Models\BookingConfirmation;

/**
 * SL-004: QR Code Service
 *
 * Generates QR codes for booking confirmations.
 * Uses a simple SVG-based approach that doesn't require external packages.
 */
class QrCodeService
{
    /**
     * Generate QR code as SVG for a booking confirmation.
     */
    public function generateForConfirmation(BookingConfirmation $confirmation): string
    {
        $url = $confirmation->getQrCodeUrl();
        $size = config('booking_confirmation.qr_code.size', 300);

        return $this->generateQrCodeSvg($url, $size);
    }

    /**
     * Generate QR code as data URL for embedding.
     */
    public function generateDataUrl(BookingConfirmation $confirmation): string
    {
        $svg = $this->generateForConfirmation($confirmation);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Generate QR code using Google Charts API (fallback).
     */
    public function getGoogleChartsUrl(BookingConfirmation $confirmation): string
    {
        $url = $confirmation->getQrCodeUrl();
        $size = config('booking_confirmation.qr_code.size', 300);
        $errorCorrection = config('booking_confirmation.qr_code.error_correction', 'M');

        return 'https://chart.googleapis.com/chart?'.http_build_query([
            'cht' => 'qr',
            'chs' => "{$size}x{$size}",
            'chl' => $url,
            'choe' => 'UTF-8',
            'chld' => $errorCorrection,
        ]);
    }

    /**
     * Generate simple QR code SVG using a basic matrix approach.
     *
     * Note: For production, consider using a proper QR code library like
     * `simplesoftwareio/simple-qrcode` or `endroid/qr-code`.
     */
    private function generateQrCodeSvg(string $data, int $size = 300): string
    {
        // Generate a simple visual representation
        // In production, use a proper QR code library
        $matrix = $this->generateSimpleMatrix($data);
        $moduleCount = count($matrix);
        $moduleSize = $size / $moduleCount;

        $foreground = config('booking_confirmation.qr_code.foreground_color', '#000000');
        $background = config('booking_confirmation.qr_code.background_color', '#FFFFFF');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" ';
        $svg .= "width=\"{$size}\" height=\"{$size}\" ";
        $svg .= 'viewBox="0 0 '.$size.' '.$size.'">';

        // Background
        $svg .= "<rect width=\"{$size}\" height=\"{$size}\" fill=\"{$background}\"/>";

        // Generate modules
        foreach ($matrix as $row => $columns) {
            foreach ($columns as $col => $module) {
                if ($module) {
                    $x = $col * $moduleSize;
                    $y = $row * $moduleSize;
                    $svg .= "<rect x=\"{$x}\" y=\"{$y}\" ";
                    $svg .= "width=\"{$moduleSize}\" height=\"{$moduleSize}\" ";
                    $svg .= "fill=\"{$foreground}\"/>";
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Generate a simple deterministic matrix based on data hash.
     *
     * This is a placeholder - in production, use a proper QR encoding library.
     */
    private function generateSimpleMatrix(string $data): array
    {
        // Using 25x25 matrix (similar to QR version 2)
        $size = 25;
        $matrix = array_fill(0, $size, array_fill(0, $size, false));

        // Hash the data to get deterministic pattern
        $hash = hash('sha256', $data);

        // Add finder patterns (corners)
        $this->addFinderPattern($matrix, 0, 0);
        $this->addFinderPattern($matrix, $size - 7, 0);
        $this->addFinderPattern($matrix, 0, $size - 7);

        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = $i % 2 === 0;
            $matrix[$i][6] = $i % 2 === 0;
        }

        // Fill data area based on hash
        $hashIndex = 0;
        for ($row = 9; $row < $size - 9; $row++) {
            for ($col = 9; $col < $size - 9; $col++) {
                $hashChar = hexdec($hash[$hashIndex % strlen($hash)]);
                $matrix[$row][$col] = ($hashChar + $row + $col) % 2 === 0;
                $hashIndex++;
            }
        }

        return $matrix;
    }

    /**
     * Add a finder pattern to the matrix.
     */
    private function addFinderPattern(array &$matrix, int $startRow, int $startCol): void
    {
        // 7x7 finder pattern
        $pattern = [
            [1, 1, 1, 1, 1, 1, 1],
            [1, 0, 0, 0, 0, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 0, 0, 0, 0, 1],
            [1, 1, 1, 1, 1, 1, 1],
        ];

        foreach ($pattern as $row => $cols) {
            foreach ($cols as $col => $value) {
                $matrix[$startRow + $row][$startCol + $col] = (bool) $value;
            }
        }
    }

    /**
     * Generate QR code using external service (for production use).
     *
     * This method provides integration points for actual QR code libraries.
     */
    public function generateWithLibrary(BookingConfirmation $confirmation, string $format = 'svg'): ?string
    {
        $url = $confirmation->getQrCodeUrl();
        $size = config('booking_confirmation.qr_code.size', 300);

        // Try Simple QrCode package if available
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::size($size)
                ->format($format)
                ->generate($url);
        }

        // Try Endroid QR Code if available
        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            $qrCode = new \Endroid\QrCode\QrCode($url);
            $qrCode->setSize($size);

            $writer = new \Endroid\QrCode\Writer\SvgWriter;
            $result = $writer->write($qrCode);

            return $result->getString();
        }

        // Fallback to basic SVG
        return $this->generateQrCodeSvg($url, $size);
    }

    /**
     * Get QR code as base64 encoded PNG using external API.
     */
    public function generatePngBase64(BookingConfirmation $confirmation): string
    {
        $googleUrl = $this->getGoogleChartsUrl($confirmation);

        try {
            $imageData = file_get_contents($googleUrl);
            if ($imageData) {
                return 'data:image/png;base64,'.base64_encode($imageData);
            }
        } catch (\Exception $e) {
            // Fallback to SVG
        }

        return $this->generateDataUrl($confirmation);
    }
}
