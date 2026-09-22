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

namespace Pimcore\Controller\Config\Template;

use Symfony\Component\Finder\Finder;

final readonly class TemplateFinder
{
    /**
     * Finds templates in a certain path.
     *
     * @return list<string>
     */
    public function findTemplates(string $path): array
    {
        $finder = new Finder()
            ->files()
            ->in($path)
            ->name('*.twig');

        $templates = [];
        foreach ($finder as $file) {
            $templates[] = $file->getRelativePathname();
        }

        return $templates;
    }
}
