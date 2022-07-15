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

namespace Pimcore\Twig\Extension;

use Pimcore\Tool\Admin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Pimcore\Bundle\AdminBundle\Helper\AdminJsHelperService;

/**
 * @internal
 */
class AdminExtension extends AbstractExtension
{
    public function __construct(private UrlGeneratorInterface $generator, private AdminJsHelperService $adminJsService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_minimize_scripts', [$this, 'minimize']),
            new TwigFunction('pimcore_process_scripts', [$this, 'processScriptPaths']),
        ];
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('pimcore_inline_icon', [$this, 'inlineIcon']),
        ];
    }

    /**
     * @return string
     */
    public function processScriptPaths(): string
    {
        $returnScriptPath = '';

        //lib script paths
        foreach ($this->adminJsService->getLibScriptPaths() as $libScriptPath) {
            $returnScriptPath .= $this->getScriptTag($libScriptPath);
        }

        if (\Pimcore::disableMinifyJs ()) {
            //internal script paths
            foreach ($this->adminJsService->getInternalScriptPaths() as $scriptPaths) {
                $returnScriptPath .= $this->getScriptTag($scriptPaths);
            }

            //bundle script paths
            foreach ($this->adminJsService->getBundleScriptPaths() as $scriptPaths) {
                $returnScriptPath .= $this->getScriptTag($scriptPaths);
            }
        } else {
            $url = $this->generator->generate ('pimcore_admin_misc_scriptproxy', $this->adminJsService->getInternalScriptPaths(), UrlGeneratorInterface::ABSOLUTE_PATH);
            $returnScriptPath .= $this->getScriptTag($url);

            $url = $this->generator->generate ('pimcore_admin_misc_scriptproxy', $this->adminJsService->getBundleScriptPaths(), UrlGeneratorInterface::ABSOLUTE_PATH);
            $returnScriptPath .= $this->getScriptTag($url);
        }

        return $returnScriptPath;
    }


    public function minimize(array $paths): string
    {
        trigger_deprecation (
            'pimcore/pimcore',
            '10.5',
            sprintf ('%s is deprecated and it will be removed in Pimcore 11.', __METHOD__)
        );
        $returnHtml = '';
        $scriptContents = '';
        foreach ($paths as $path) {
            $found = false;
            foreach ([PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/js/' . $path,
                        PIMCORE_WEB_ROOT . $path,
                    ] as $fullPath) {
                if (file_exists($fullPath)) {
                    $scriptContents .= file_get_contents($fullPath) . "\n\n\n";
                    $found = true;
                }
            }

            if (!$found) {
                $returnHtml .= $this->getScriptTag($path);
            }
        }

        $parameters = Admin::getMinimizedScriptPath($scriptContents);
        $url = $this->generator->generate('pimcore_admin_misc_scriptproxy', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);

        $returnHtml .= $this->getScriptTag($url);

        return $returnHtml;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getScriptTag($url): string
    {
        return '<script src="' . $url . '"></script>' . "\n";
    }

    /**
     * @param string $icon
     *
     * @return string
     */
    public function inlineIcon(string $icon)
    {
        $content = file_get_contents($icon);

        return sprintf('<img src="data:%s;base64,%s" title="%s" data-imgpath="%s" />',
            mime_content_type($icon),
            base64_encode($content),
            basename($icon),
            str_replace(PIMCORE_WEB_ROOT, '', $icon)
        );
    }
}
