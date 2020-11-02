<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Tool\Admin;
use Pimcore\Twig\TokenParser\AssetCompressParser;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
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

    /**
     * @deprecated
     */
    public function minimize(string $value) : array
    {
        return Admin::getMinimizedScriptPath($value);
    }
}
