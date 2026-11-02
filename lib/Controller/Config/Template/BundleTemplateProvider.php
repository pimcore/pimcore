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

use Pimcore\Controller\Config\Bundle\BundleProvider;
use function sprintf;

/**
 * Builds the list of selectable templates by scanning for `*.twig` files in the `templates/` (or `Resources/views/`)
 * directory of every registered bundle, except bundles excluded by {@see BundleProvider::isValidNamespace()}.
 */
final readonly class BundleTemplateProvider implements TemplateProviderInterface
{
    public function __construct(
        private BundleProvider $bundleProvider,
        private TemplateFinder $finder,
    ) {
    }

    public function getTemplates(): array
    {
        $templates = [];
        foreach ($this->bundleProvider->getBundles() as $bundle) {
            if (is_dir($bundlePath = $bundle->getPath().'/templates') || is_dir($bundlePath = $bundle->getPath().'/Resources/views')) {
                $bundleName = $bundle->getName();

                if (str_ends_with($bundleName, 'Bundle')) {
                    $bundleName = substr($bundleName, 0, -6);
                }

                foreach ($this->finder->findTemplates($bundlePath) as $template) {
                    $templates[] = sprintf('@%s/%s', $bundleName, $template);
                }
            }
        }

        return $templates;
    }
}
