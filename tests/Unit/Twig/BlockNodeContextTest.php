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

namespace Pimcore\Tests\Unit\Twig;

use Generator;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\Editable\BlockInterface;
use Pimcore\Model\Document\Editable\EditableInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\EditableRenderer;
use Pimcore\Twig\Extension\DocumentEditableExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Regression tests for _block context variable scoping in pimcoreblock / pimcoremanualblock.
 *
 * Both node types must restore the _block context variable to its prior state after they
 * finish rendering. The prior state is either:
 *   - absent (no enclosing block)  → _block must be unset, NOT set to null
 *   - an outer block object        → _block must be restored to that object
 *
 * The old bug: the restore code used `$context['_block'] = $prev ?? null`, which
 * would set _block to null when there was no enclosing block. Twig's `is defined`
 * test uses array_key_exists(), so it correctly distinguishes null (key present)
 * from absent (key missing). A null _block passes `_block is defined`, which is
 * the observable difference the tests below assert.
 *
 * @internal
 */
final class BlockNodeContextTest extends TestCase
{
    /**
     * Creates a minimal stub implementing BlockInterface and EditableInterface,
     * yielding a fixed number of iterations.
     *
     * Implementing EditableInterface is required so the stub satisfies the
     * string|EditableInterface return type of EditableRenderer::render(). BlockInterface
     * and EditableInterface are independent — the concrete Block class implements both,
     * but the interfaces themselves do not share a hierarchy.
     *
     * Also includes setCurrent() and getConfig() required by the compiled pimcoreblock
     * template code (they live on the concrete Block/Editable base class, not BlockInterface).
     */
    private static function makeStubBlock(int $iterations): BlockInterface&EditableInterface
    {
        return new class($iterations) implements BlockInterface, EditableInterface {
            private int $current = 0;

            public function __construct(private readonly int $count) {}

            // BlockInterface methods
            public function getIterator(): Generator
            {
                for ($i = 1; $i <= $this->count; $i++) {
                    yield $i => $i;
                }
            }

            public function start(): void {}

            public function end(): void {}

            public function blockConstruct(): void {}

            public function blockDestruct(): void {}

            public function blockStart(): void {}

            public function blockEnd(): void {}

            public function getCount(): int { return $this->count; }

            public function getCurrent(): int { return $this->current; }

            public function getCurrentIndex(): int { return $this->current; }

            public function isEmpty(): bool { return $this->count === 0; }

            // Required by compiled pimcoreblock template code (not in BlockInterface)
            public function setCurrent(int $current): void { $this->current = $current; }

            // Required by compiled pimcoreblock template code (not in BlockInterface)
            public function getConfig(): array { return []; }

            // EditableInterface stubs — needed for return-type compatibility with EditableRenderer
            public function render(): mixed { return ''; }
            public function getData(): mixed { return null; }
            public function getType(): string { return 'block'; }
            public function setDataFromEditmode(mixed $data): static { return $this; }
            public function setDataFromResource(mixed $data): static { return $this; }
        };
    }

    /**
     * Builds a standalone Twig environment using the real DocumentEditableExtension
     * (so it is registered under the correct class name for the compiled template's
     * getExtension() lookup) with a stub EditableRenderer dispatching block lookups
     * by name. A concrete PageSnippet subclass passes the instanceof guard in
     * renderEditable() without requiring the Pimcore container.
     *
     * @param array<string, BlockInterface&EditableInterface> $blocks  block name → stub
     * @param array<string, string> $templates  template name → source
     *
     * @return array{Environment, PageSnippet}
     */
    private function buildTwigEnvironment(array $blocks, array $templates): array
    {
        $renderer = new BlockNodeContextStubRenderer($blocks);
        $extension = new DocumentEditableExtension($renderer);

        $env = new Environment(new ArrayLoader($templates), ['debug' => false, 'cache' => false]);
        $env->addExtension($extension);

        $document = new class extends PageSnippet {};

        return [$env, $document];
    }

    /**
     * After a top-level pimcoreblock completes, _block must be unset from the context.
     *
     * Regression: the old restore code set $context['_block'] = null when there was
     * no prior block, causing _block to remain "defined" (array key present, null value)
     * after the tag closed. Twig's `is defined` test uses array_key_exists(), so it
     * distinguishes null (key exists) from absent (key missing).
     */
    public function testBlockNodeUnsets_blockAfterCompletion(): void
    {
        [$env, $document] = $this->buildTwigEnvironment(
            ['myblock' => self::makeStubBlock(1)],
            ['test.html.twig' => <<<TWIG
                before:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% pimcoreblock "myblock" %}inside:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% endpimcoreblock %}
                after:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                TWIG]
        );

        $output = $env->render('test.html.twig', ['document' => $document]);

        $this->assertStringContainsString('before:UNDEFINED', $output, '_block must not be defined before any block tag');
        $this->assertStringContainsString('inside:DEFINED', $output, '_block must be defined inside a pimcoreblock iteration');
        $this->assertStringContainsString('after:UNDEFINED', $output, '_block must be unset (not null) after pimcoreblock closes');
    }

    /**
     * After a top-level pimcoremanualblock completes, _block must be unset from the context.
     */
    public function testManualBlockNodeUnsets_blockAfterCompletion(): void
    {
        [$env, $document] = $this->buildTwigEnvironment(
            ['myblock' => self::makeStubBlock(1)],
            ['test.html.twig' => <<<TWIG
                before:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% pimcoremanualblock "myblock" %}
                start:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% blockiterate %}
                body:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% endblockiterate %}
                end:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% endpimcoremanualblock %}
                after:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                TWIG]
        );

        $output = $env->render('test.html.twig', ['document' => $document]);

        $this->assertStringContainsString('before:UNDEFINED', $output, '_block must not be defined before any block tag');
        $this->assertStringContainsString('start:DEFINED', $output, '_block must be defined in the start section');
        $this->assertStringContainsString('body:DEFINED', $output, '_block must be defined inside a pimcoremanualblock iteration');
        $this->assertStringContainsString('end:DEFINED', $output, '_block must be defined in the end section');
        $this->assertStringContainsString('after:UNDEFINED', $output, '_block must be unset (not null) after pimcoremanualblock closes');
    }

    /**
     * When pimcoreblock is nested inside pimcoremanualblock, the outer block's _block
     * reference must survive the inner block's scope and be correctly restored after
     * the inner block closes. After the outer block closes, _block must be fully unset.
     *
     * Regression: with the old `?? null` restore, after the outermost block closed,
     * $context['_block'] was set to null instead of being unset — so `_block is defined`
     * would incorrectly return true.
     */
    public function testNestedPimcoreblockInsideManualblockRestoresContext(): void
    {
        $outerBlock = self::makeStubBlock(1);
        $innerBlock = self::makeStubBlock(1);

        [$env, $document] = $this->buildTwigEnvironment(
            ['outer' => $outerBlock, 'inner' => $innerBlock],
            ['test.html.twig' => <<<TWIG
                before_outer:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% pimcoremanualblock "outer" %}
                in_outer_start:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% blockiterate %}
                in_outer_body:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% pimcoreblock "inner" %}
                in_inner:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% endpimcoreblock %}
                after_inner_defined:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                after_inner_not_null:{% if _block is not null %}NOT_NULL{% else %}NULL{% endif %}
                {% endblockiterate %}
                in_outer_end:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                {% endpimcoremanualblock %}
                after_outer:{% if _block is defined %}DEFINED{% else %}UNDEFINED{% endif %}
                TWIG]
        );

        $output = $env->render('test.html.twig', ['document' => $document]);

        // Baseline: no _block before any tag
        $this->assertStringContainsString('before_outer:UNDEFINED', $output, '_block must not be set before the outer block');

        // Outer manual block sets _block in all three sections
        $this->assertStringContainsString('in_outer_start:DEFINED', $output, '_block must be defined in the outer start section');
        $this->assertStringContainsString('in_outer_body:DEFINED', $output, '_block must be defined in the outer iterate body');
        $this->assertStringContainsString('in_outer_end:DEFINED', $output, '_block must be defined in the outer end section');

        // Inner pimcoreblock sets _block during its iteration
        $this->assertStringContainsString('in_inner:DEFINED', $output, '_block must be defined inside the inner pimcoreblock');

        // After inner pimcoreblock closes, outer block _block must be restored (not null, not missing)
        $this->assertStringContainsString(
            'after_inner_defined:DEFINED',
            $output,
            '_block must still be defined after inner pimcoreblock closes (outer block restored)'
        );
        $this->assertStringContainsString(
            'after_inner_not_null:NOT_NULL',
            $output,
            '_block must be the outer block object after inner pimcoreblock closes, not null'
        );

        // After outer manual block closes, _block must be fully unset (key absent)
        // Regression: old code set $context['_block'] = null here — null is defined in Twig
        $this->assertStringContainsString(
            'after_outer:UNDEFINED',
            $output,
            '_block must be unset (not null) after outer pimcoremanualblock closes'
        );
    }
}

/**
 * Stub EditableRenderer that dispatches render() calls to a pre-configured block map.
 * Skips parent constructor to avoid requiring container-managed dependencies.
 *
 * @internal
 */
final class BlockNodeContextStubRenderer extends EditableRenderer
{
    /** @param array<string, EditableInterface> $blocks */
    public function __construct(private readonly array $blocks)
    {
        // Intentionally skip parent::__construct() — we override render() completely.
    }

    public function render(
        PageSnippet $document,
        string $type,
        string $name,
        array $options = [],
        ?bool $editmode = null
    ): EditableInterface {
        return $this->blocks[$name];
    }
}
