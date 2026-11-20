<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Asset\Font;

use GdImage;
use Pimcore\Logger;

/**
 * Renders a sample text with a given font file into a PNG image.
 *
 * Uses the FreeType support of the GD extension, so every format FreeType is able to read
 * (TrueType, OpenType, TrueType collections, WOFF and - depending on the FreeType build - WOFF2) is supported.
 *
 * @internal
 */
final class PreviewGenerator
{
    /**
     * Reference glyphs used to calculate a uniform line height (ascenders, descenders and accents)
     */
    private const LINE_METRICS_REFERENCE = 'ÁÉHbdfghjklpqy|';

    private const MAX_DIMENSION = 8000;

    public static function isAvailable(): bool
    {
        return function_exists('imagettfbbox') && function_exists('imagettftext');
    }

    /**
     * @param string $fontFile path to a local font file
     * @param string $targetFile path where the PNG file is written to
     * @param string $text text to render, lines are separated by "\n"
     * @param int $fontSize font size in points
     *
     * @return bool true if the preview image was written successfully
     */
    public function generate(string $fontFile, string $targetFile, string $text, int $fontSize = 48): bool
    {
        if (!self::isAvailable() || !is_file($fontFile) || $fontSize < 1) {
            return false;
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", trim($text)));
        if ($lines === ['']) {
            return false;
        }

        $reference = $this->getBoundingBox($fontFile, $fontSize, self::LINE_METRICS_REFERENCE);
        if ($reference === null) {
            Logger::info(sprintf('Font file %s cannot be read by FreeType', $fontFile));

            return false;
        }

        $ascent = -min($reference[5], $reference[7]);
        $descent = max($reference[1], $reference[3]);
        $lineHeight = (int) ceil(($ascent + $descent) * 1.25);
        $padding = (int) ceil($fontSize / 2);

        $boxes = [];
        $maxWidth = 0;
        foreach ($lines as $i => $line) {
            $box = $this->getBoundingBox($fontFile, $fontSize, $line);
            if ($box === null) {
                return false;
            }

            $boxes[$i] = $box;
            $maxWidth = max($maxWidth, max($box[2], $box[4]) - min($box[0], $box[6]));
        }

        $width = min(self::MAX_DIMENSION, $maxWidth + 2 * $padding);
        $height = min(self::MAX_DIMENSION, count($lines) * $lineHeight + 2 * $padding);

        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        if (!$image instanceof GdImage) {
            return false;
        }

        $background = imagecolorallocate($image, 255, 255, 255);
        $foreground = imagecolorallocate($image, 0, 0, 0);
        if ($background === false || $foreground === false) {
            return false;
        }

        imagefill($image, 0, 0, $background);

        foreach ($lines as $i => $line) {
            if ($line === '') {
                continue;
            }

            $x = $padding - min($boxes[$i][0], $boxes[$i][6]);
            $y = $padding + $ascent + $i * $lineHeight;

            if (@imagettftext($image, $fontSize, 0, $x, $y, $foreground, $fontFile, $line) === false) {
                return false;
            }
        }

        return imagepng($image, $targetFile);
    }

    /**
     * @return int[]|null
     */
    private function getBoundingBox(string $fontFile, int $fontSize, string $text): ?array
    {
        $box = @imagettfbbox($fontSize, 0, $fontFile, $text);

        return is_array($box) ? $box : null;
    }
}
