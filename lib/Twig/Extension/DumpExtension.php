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

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DumpExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_dump', [$this, 'dump'], ['is_safe' => ['html']]),
        ];
    }

    public function dump($value)
    {
        $cloner = new VarCloner();
        $dumper = new HtmlDumper(null, null, AbstractDumper::DUMP_LIGHT_ARRAY | AbstractDumper::DUMP_TRAILING_COMMA);

        return $dumper->dump($cloner->cloneVar($value), true);
    }
}
