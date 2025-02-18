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

use Twig\Compiler;
use Twig\Node\Node;

/**
 * @internal
 */
final class CacheNode extends Node
{
    public function __construct(
        private string $key,
        private ?int $ttl,
        private array $tags,
        private bool $force,
        Node $body,
        int $lineno,
        ?string $tag = 'pimcorecache'
    ) {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $splitChars = uniqid('', true);

        [$before, $after] = explode($splitChars, $this->getPhpCode($splitChars));

        $compiler
            ->addDebugInfo($this)
            ->write($before)
            ->subcompile($this->getNode('body'))
            ->write($after)
        ;
    }

    private function getPhpCode(string $splitChars): string
    {

        $key = $this->key;
        $tags = json_encode($this->tags);
        $ttl = $this->ttl ?? 'null';
        $force = json_encode($this->force);

        return <<<PHP

    \$cacheExtension = \$this->env->getExtension('Pimcore\Twig\Extension\CacheTagExtension');
    \$key = '{$key}';
    \$tags = {$tags};
    \$ttl = {$ttl};
    \$force = {$force};
    \$content = \$cacheExtension->getContentFromCache(\$key, \$force);
    if (!\$content) {
        \$cacheExtension->startBuffering();
        {$splitChars}

        \$content = \$cacheExtension->endBuffering(\$key, \$tags, \$ttl, \$force);
    }
    echo \$content;
PHP;

    }
}
