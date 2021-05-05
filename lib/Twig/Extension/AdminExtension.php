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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Tool\Admin;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class AdminExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_minimize_scripts', [$this, 'minimize']),
        ];
    }

    public function minimize(array $paths): array
    {
        $scriptContents = '';
        foreach ($paths as $path) {
            $fullPath = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/js/' . $path;
            if (file_exists($fullPath)) {
                $scriptContents .= file_get_contents($fullPath) . "\n\n\n";
            }
        }

        return Admin::getMinimizedScriptPath($scriptContents);
    }
}
