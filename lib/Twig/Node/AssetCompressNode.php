<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * @internal
 */
class AssetCompressNode extends Node
{
    public function __construct(Node $body, int $lineno, ?string $tag = 'pimcoreassetcompress')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("\n; echo trim(str_replace(\"\n\", '', ob_get_clean()));\n");
    }
}
