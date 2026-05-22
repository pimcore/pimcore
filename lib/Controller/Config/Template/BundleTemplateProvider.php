<?php
declare(strict_types=1);

namespace Pimcore\Controller\Config\Template;

use Pimcore\Controller\Config\Bundle\BundleProvider;

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
                $templates[] = $this->finder->findTemplates($bundlePath, $bundle->getName());
            }
        }

        return array_merge(...$templates);
    }
}
