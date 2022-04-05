<?php

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

/**
 * @deprecated since 10.4 version and will be removed in Pimcore 11. use the Spatie Image Optimizer instead
 */
final class PngCrushOptimizer extends AbstractCommandOptimizer
{
    /**
     * {@inheritdoc}
     */
    protected function getExecutable(): string
    {
        return 'pngcrush';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArray(string $executable, string $input, string $output): array
    {
        return [$executable, $input, $output];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'image/png';
    }
}
