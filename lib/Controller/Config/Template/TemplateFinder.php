<?php
declare(strict_types=1);

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
