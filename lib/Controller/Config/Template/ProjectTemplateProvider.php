<?php
declare(strict_types=1);

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
