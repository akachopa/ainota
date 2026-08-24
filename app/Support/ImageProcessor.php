<?php

namespace App\Support;

use Imagick;
use ImagickPixel;
use Throwable;

class ImageProcessor
{
    public function perceptualHash(string $absolutePath): ?string
    {
        try {
            $image = new Imagick($absolutePath.'[0]');
            $image->thumbnailImage(8, 8, true);
            $image->transformImageColorspace(Imagick::COLORSPACE_GRAY);

            $pixels = [];
            $sum = 0;
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    /** @var ImagickPixel $pixel */
                    $pixel = $image->getImagePixelColor($x, $y);
                    $value = $pixel->getColor()['r'] ?? 0;
                    $pixels[] = $value;
                    $sum += $value;
                }
            }

            $avg = $sum / 64;
            $bits = '';
            foreach ($pixels as $value) {
                $bits .= $value >= $avg ? '1' : '0';
            }

            return $bits;
        } catch (Throwable) {
            return null;
        }
    }

    public function hammingDistance(string $left, string $right): int
    {
        $length = min(strlen($left), strlen($right));
        $distance = abs(strlen($left) - strlen($right));

        for ($i = 0; $i < $length; $i++) {
            if ($left[$i] !== $right[$i]) {
                $distance++;
            }
        }

        return $distance;
    }

    public function pageCount(string $absolutePath, string $mime): int
    {
        if ($mime !== 'application/pdf') {
            return 1;
        }

        try {
            $image = new Imagick;
            $image->pingImage($absolutePath);

            return max(1, $image->getNumberImages());
        } catch (Throwable) {
            return 1;
        }
    }

    public function isEncryptedPdf(string $absolutePath, string $mime): bool
    {
        if ($mime !== 'application/pdf') {
            return false;
        }

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            return false;
        }

        $chunk = (string) fread($handle, 2048);
        fclose($handle);

        return str_contains($chunk, '/Encrypt');
    }

    public function writeJpegPreview(string $source, string $destination, int $maxWidth = 1600): bool
    {
        try {
            $image = new Imagick($source.'[0]');
            $image->autoOrient();
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(82);
            if ($image->getImageWidth() > $maxWidth) {
                $image->thumbnailImage($maxWidth, 0);
            }

            $dir = dirname($destination);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            return $image->writeImage($destination);
        } catch (Throwable) {
            return false;
        }
    }
}
