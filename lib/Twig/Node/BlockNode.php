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

use Pimcore\Twig\Options\BlockOptions;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @internal
 */
final class BlockNode extends Node
{
    public function __construct(
        private readonly string $blockName,
        private readonly BlockOptions $options,
        Node $body,
        int $lineno,
        string $tag
    ) {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $splitChars = uniqid('', true);

        [$part1, $part2] = explode($splitChars, $this->getPhpCode($splitChars));

        $compiler
            ->addDebugInfo($this)
            ->write($part1)
            ->subcompile($this->getNode('body'))
            ->write($part2);
    }

    private function getPhpCode(string $splitChars): string
    {
        $optionsString = $this->options->toString();

        return <<<PHP
        \$editableExtension = \$this->env->getExtension('Pimcore\Twig\Extension\DocumentEditableExtension');
        \$block = \$editableExtension->renderEditable(\$context, 'block', '{$this->blockName}', $optionsString);
        foreach(\$block->getIterator() as \$key => \$index) {
            \$block->setCurrent(\$key);
            \$context['_block'] = \$block;
            \$config = \$block->getConfig();
            {$splitChars}
        }
PHP;

    }
}
