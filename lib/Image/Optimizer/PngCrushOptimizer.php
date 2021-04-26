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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Image\Optimizer;

use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;

final class PngCrushOptimizer extends AbstractCommandOptimizer
{
    /**
     * @var MimeTypeGuesserInterface
     */
    private $mimeTypeGuesser;

    public function __construct()
    {
        $this->mimeTypeGuesser = MimeTypes::getDefault();
    }

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
