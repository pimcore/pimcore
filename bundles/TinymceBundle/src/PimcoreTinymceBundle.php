<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\TinymceBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;


class PimcoreTinymceBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return $this->getBuildPaths($this->getPath() . '/public/build/tinymce/entrypoints.json', ['tinymce'], 'css');
    }

    public function getEditmodeCssPaths(): array
    {
        return $this->getBuildPaths($this->getPath() . '/public/build/tinymce/entrypoints.json', ['tinymce'], 'css');
    }

    public function getJsPaths(): array
    {
        return $this->getAllJsPaths();
    }

    public function getEditmodeJsPaths(): array
    {
        return $this->getAllJsPaths();
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    private function getAllJsPaths(): array
    {
        $paths = $this->getBuildPaths($this->getPath() . '/public/build/tinymce/entrypoints.json', ['tinymce']);
        $paths []= '/bundles/pimcoretinymce/js/editor.js';
        return $paths;
    }

    //TODO move to core
    private function getBuildPaths(string $entrypointsFile, array $entrypoints, string $type = 'js'): array
    {
        $entrypointsContent = file_get_contents($entrypointsFile);
        $entrypointsJson = json_decode($entrypointsContent,true)['entrypoints'];

        $jsPaths = [];
        foreach($entrypoints as $entrypoint){
            $jsPaths = array_merge($jsPaths, $entrypointsJson[$entrypoint][$type]);
        }
        return $jsPaths;
    }
}
