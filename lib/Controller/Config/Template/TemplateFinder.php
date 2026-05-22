<?php
declare(strict_types=1);

namespace Pimcore\Controller\Config\Template;

use Symfony\Component\Finder\Finder;

final readonly class TemplateFinder
{
    /**
     * Finds templates in a certain path. If bundleName is null, the global notation (templates/) will be used.
     *
     * @return list<string>
     */
    public function findTemplates(string $path, ?string $bundleName = null): array
    {
        $finder = new Finder()
            ->files()
            ->in($path)
            ->name('*.twig');

        if ($bundleName && str_ends_with($bundleName, 'Bundle')) {
            $bundleName = substr($bundleName, 0, -6);
        }

        $templates = [];
        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            $templates[] = $bundleName ? sprintf('@%s/%s', $bundleName, $name) : $name;
        }

        return $templates;
    }
}
