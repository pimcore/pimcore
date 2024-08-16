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

namespace Pimcore\Twig\Node;

use Pimcore\Model\Document;
use Pimcore\Twig\Extension\DocumentEditableExtension;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Node;

/**
 * @internal
 */
final class ManualBlockNode extends Node
{
    public function __construct(

        private DocumentEditableExtension $documentEditableExtension,
        private string $blockName,
        private bool $manual,
        Node $start,
        Node $body,
        Node $end,
        int $lineno,
        ?string $tag = 'pimcoreblock'
    ) {
        parent::__construct([
            'start' => $start,
            'body' => new CaptureNode($body, $body->getTemplateLine()),
            'end' => $end
        ], [], $lineno, $tag);
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

        $manual = $this->manual ? 'true' : 'false';

        return <<<PHP

        \$editableExtension = \$this->env->getExtension('Pimcore\Twig\Extension\DocumentEditableExtension');
        \$block = \$editableExtension->renderEditable(\$context, 'block', '{$this->blockName}', ['manual' =>true]);
\$block->start();
{$splitChars}
        foreach(\$block->getIterator() as \$index) {

            \$block->blockConstruct();

            \$context['_block'] = \$block;
            {$splitChars}

            \$config = \$block->getConfig();
            \$config['template']['html'] = \$tmp;

            \$block->setConfig(\$config);

            if (\$index >= 1000000) {
              continue;
            }
            yield \$tmp;

            \$block->blockDestruct();
        }
{$splitChars}
\$block->end();
PHP;

    }
}
