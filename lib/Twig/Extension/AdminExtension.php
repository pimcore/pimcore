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
use Twig\TwigFunction;

/**
 * @internal
 */
class AdminExtension extends AbstractExtension
{
    public function __construct(private UrlGeneratorInterface $generator)
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_minimize_scripts', [$this, 'minimize']),
            new TwigFunction('pimcore_script_exists', [$this, 'exists']),
        ];
    }

    public function minimize(array $paths): string
    {
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

    private function getScriptTag($url): string
    {
        return '<script src="' . $url . '"></script>' . "\n";
    }
}
