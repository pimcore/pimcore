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

use Pimcore\Twig\Extension\CacheTagExtension;
use Twig\Compiler;
use Twig\Node\Node;
use function sprintf;

/**
 * @internal
 */
final class CacheNode extends Node
{
    public function __construct(
        Node $key,
        ?Node $ttl,
        ?Node $tags,
        ?Node $force,
        Node $body,
        int $lineno
    ) {
        $nodes = ['key' => $key, 'body' => $body];
        foreach (['ttl' => $ttl, 'tags' => $tags, 'force' => $force] as $name => $node) {
            if ($node !== null) {
                $nodes[$name] = $node;
            }
        }

        parent::__construct($nodes, [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        // unique variable names, so nested pimcorecache blocks do not overwrite each other
        $extension = $compiler->getVarName();
        $key = $compiler->getVarName();
        $tags = $compiler->getVarName();
        $ttl = $compiler->getVarName();
        $force = $compiler->getVarName();
        $content = $compiler->getVarName();

        $compiler
            ->addDebugInfo($this)
            ->write(sprintf("\$%s = \$this->env->getExtension('%s');\n", $extension, CacheTagExtension::class))
            ->write(sprintf('$%s = ', $key))
            ->raw(sprintf('$%s->normalizeKey(', $extension))
            ->subcompile($this->getNode('key'))
            ->raw(");\n")
            ->write(sprintf('$%s = ', $tags))
            ->raw(sprintf('$%s->normalizeTags(', $extension));
        $this->compileOptional($compiler, 'tags', '[]');
        $compiler
            ->raw(");\n")
            ->write(sprintf('$%s = ', $ttl))
            ->raw(sprintf('$%s->normalizeTtl(', $extension));
        $this->compileOptional($compiler, 'ttl', 'null');
        $compiler
            ->raw(");\n")
            ->write(sprintf('$%s = (bool) (', $force));
        $this->compileOptional($compiler, 'force', 'false');
        $compiler
            ->raw(");\n")
            ->write(sprintf("\$%s = \$%s->getContentFromCache(\$%s, \$%s);\n", $content, $extension, $key, $force))
            ->write(sprintf("if (!\$%s) {\n", $content))
            ->indent()
            ->write(sprintf("\$%s->startBuffering();\n", $extension))
            ->subcompile($this->getNode('body'))
            ->write(sprintf(
                "\$%s = \$%s->endBuffering(\$%s, \$%s, \$%s, \$%s);\n",
                $content,
                $extension,
                $key,
                $tags,
                $ttl,
                $force
            ))
            ->outdent()
            ->write("}\n")
            ->write(sprintf("echo \$%s;\n", $content))
        ;
    }

    private function compileOptional(Compiler $compiler, string $name, string $default): void
    {
        if ($this->hasNode($name)) {
            $compiler->subcompile($this->getNode($name));
        } else {
            $compiler->raw($default);
        }
    }
}
