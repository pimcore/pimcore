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
final class ManualBlockNode extends Node
{
    public function __construct(
        private readonly string $blockName,
        private readonly BlockOptions $options,
        Node $start,
        Node $body,
        Node $end,
        int $lineno,
        string $tag
    ) {
        parent::__construct(
            [
                'start' => $start,
                'body' => $body,
                'end' => $end,
            ],
            [],
            $lineno,
            $tag
        );
    }

    public function compile(Compiler $compiler): void
    {

        $splitChars = uniqid('', true);

        [$part1, $part2, $part3, $part4] = explode($splitChars, $this->getPhpCode($splitChars));

        $compiler
            ->addDebugInfo($this)
            ->write($part1)
            ->subcompile($this->getNode('start'))
            ->write($part2)
            ->subcompile($this->getNode('body'))
            ->write($part3)
            ->subcompile($this->getNode('end'))
            ->write($part4)
        ;
    }

    private function getPhpCode(string $splitChars): string
    {
        $optionsString = $this->options->toString();
        $blockVar = 'block_' . str_replace('.', '_', $splitChars);
        $prevBlockVar = 'prevBlock_' . str_replace('.', '_', $splitChars);

        return <<<PHP
        \$editableExtension = \$this->env->getExtension('Pimcore\Twig\Extension\DocumentEditableExtension');
        \${$blockVar} = \$editableExtension->renderEditable(\$context, 'block', '{$this->blockName}', $optionsString);
        \${$prevBlockVar} = \$context['_block'] ?? null;
        \$context['_block'] = \${$blockVar};
\${$blockVar}->start();
{$splitChars}
        foreach(\${$blockVar}->getIterator() as \$index) {
            \${$blockVar}->blockConstruct();
            \$context['_block'] = \${$blockVar};
            {$splitChars}
            \${$blockVar}->blockDestruct();
        }
{$splitChars}
\${$blockVar}->end();
        \$context['_block'] = \${$prevBlockVar};
        unset(\${$prevBlockVar});
PHP;

    }
}
