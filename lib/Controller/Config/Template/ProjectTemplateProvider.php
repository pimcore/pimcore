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

/**
 * Builds the list of selectable templates by scanning for `*.twig` files in the project's `templates/` directory.
 */
final readonly class ProjectTemplateProvider implements TemplateProviderInterface
{
    public function __construct(
        private TemplateFinder $finder,
    ) {
    }

    public function getTemplates(): array
    {
        if (is_dir($symfonyPath = PIMCORE_PROJECT_ROOT.'/templates')) {
            return $this->finder->findTemplates($symfonyPath);
        }

        return [];
    }
}
