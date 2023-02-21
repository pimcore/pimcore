<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Image\Optimizer;

use Pimcore\Exception\ImageOptimizationFailedException;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;

final class SpatieImageOptimizer implements \Pimcore\Image\Optimizer\OptimizerInterface
{
    public function optimizeImage(string $input, string $output): string
    {
        $optimizerChain = (new OptimizerChain)
            ->addOptimizer(new Jpegoptim([
                '--strip-all',
                '--all-progressive',
            ]))
            ->addOptimizer(new Pngquant)
            ->addOptimizer(new Optipng)
            ->addOptimizer(new Cwebp([
                '-pass 10',
                '-mt',
            ]));

        $optimizerChain->optimize($input, $output); // To keep original image untouched and create the optimized one as a new image

        if (file_exists($output) && filesize($output) > 0) {
            return $output;
        }

        throw new ImageOptimizationFailedException('Could not create optimized image');
    }

    public function supports(string $mimeType): bool
    {
        //  Implement supports() method.
        return $mimeType === 'image/jpeg' || $mimeType === 'image/png' || $mimeType === 'image/webp';
    }
}
