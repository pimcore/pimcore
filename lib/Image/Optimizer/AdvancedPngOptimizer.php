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

final class AdvancedPngOptimizer extends AbstractCommandOptimizer
{
    /**
     * {@inheritdoc}
     */
    protected function getExecutable(): string
    {
        return 'advpng';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArray(string $executable, string $input, string $output): array
    {
        return [$executable, '-z4', $output];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'image/png';
    }
}
