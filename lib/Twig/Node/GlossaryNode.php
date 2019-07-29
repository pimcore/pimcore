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

namespace Pimcore\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

class GlossaryNode extends Node
{
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        $compiler
            ->write('$this->env->getExtension(\'Pimcore\Twig\Extension\GlossaryExtension\')->getGlossaryHelper()->start();')
            ->raw("\n")
            ->subcompile($this->getNode('body'))
            ->raw("\n")
            ->write('$this->env->getExtension(\'Pimcore\Twig\Extension\GlossaryExtension\')->getGlossaryHelper()->stop();')
            ->raw("\n")
        ;
    }
}
