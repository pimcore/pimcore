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

use Codeception\Test\Unit;
use Pimcore\Cache;
use Pimcore\Twig\Extension\CacheTagExtension;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * The key, ttl, tags and force modifiers of the pimcorecache tag accept any Twig expression.
 *
 * @internal
 */
final class CacheTagTest extends Unit
{
    private const KEY_PREFIX = 'pimcore_twigcache_';

    private int $renderCount = 0;

    private string $suffix;

    protected function _before(): void
    {
        Cache::enable();
        $this->renderCount = 0;
        $this->suffix = str_replace('.', '', uniqid('cachetagtest', true));
    }

    protected function _after(): void
    {
        // clearing the shared "in_template" tag would stop later saves of this process, entries use a unique key
        Cache::clearTag('tag_' . $this->suffix);
        Cache::disable();
    }

    private function render(string $template, array $context = []): string
    {
        $twig = new Environment(new ArrayLoader(['t' => $template]), ['cache' => false, 'autoescape' => false]);
        $twig->addExtension(new CacheTagExtension());
        $twig->addFunction(new TwigFunction('render_count', fn (): int => ++$this->renderCount));

        return $twig->render('t', $context + ['suffix' => $this->suffix]);
    }

    public function testLiteralKeyIsCached(): void
    {
        $template = '{% pimcorecache "' . $this->suffix . '_literal" %}{{ render_count() }}{% endpimcorecache %}';

        $this->assertSame('1', $this->render($template));
        $this->assertSame('1', $this->render($template));
        $this->assertSame(1, $this->renderCount);
    }

    public function testKeyFromVariableSeparatesEntries(): void
    {
        $template = '{% pimcorecache site ~ suffix %}{{ site }}-{{ render_count() }}{% endpimcorecache %}';

        $this->assertSame('a-1', $this->render($template, ['site' => 'a']));
        $this->assertSame('b-2', $this->render($template, ['site' => 'b']));
        $this->assertSame('a-1', $this->render($template, ['site' => 'a']));
        $this->assertSame('b-2', $this->render($template, ['site' => 'b']));
        $this->assertSame(2, $this->renderCount);
    }

    public function testKeyFromExpressionWithModifiers(): void
    {
        $template = '{% pimcorecache "nav_" ~ site.id ~ suffix ttl(60) tags(["x"]) %}{{ render_count() }}{% endpimcorecache %}';

        $this->assertSame('1', $this->render($template, ['site' => ['id' => 1]]));
        $this->assertSame('2', $this->render($template, ['site' => ['id' => 2]]));
        $this->assertSame('1', $this->render($template, ['site' => ['id' => 1]]));
        $this->assertSame(2, $this->renderCount);
        $this->assertNotFalse(Cache::load(self::KEY_PREFIX . 'nav_1' . $this->suffix));
    }

    public function testDynamicTtlTagsAndForce(): void
    {
        $template = '{% pimcorecache "dyn" ~ suffix ttl(lifetime) tags(extraTags) force(forced) %}{{ render_count() }}{% endpimcorecache %}';
        $context = ['lifetime' => 120, 'extraTags' => ['tag_' . $this->suffix], 'forced' => true];

        $this->assertSame('1', $this->render($template, $context));
        $this->assertSame('1', $this->render($template, $context));
        $this->assertSame(1, $this->renderCount);

        // the dynamic tag is attached to the entry: clearing it renders the block again
        Cache::clearTag('tag_' . $this->suffix);
        $this->assertSame('2', $this->render($template, $context));
    }

    public function testDynamicTagFromStringVariable(): void
    {
        $template = '{% pimcorecache "str" ~ suffix tags(extraTag) %}{{ render_count() }}{% endpimcorecache %}';
        $context = ['extraTag' => 'tag_' . $this->suffix];

        $this->assertSame('1', $this->render($template, $context));
        Cache::clearTag('tag_' . $this->suffix);
        $this->assertSame('2', $this->render($template, $context));
    }

    public function testDynamicTtlIsStoredWithTheEntry(): void
    {
        $template = '{% pimcorecache "ttl" ~ suffix ttl(lifetime) %}x{% endpimcorecache %}';

        $this->render($template, ['lifetime' => 1]);
        $this->assertNotFalse(Cache::load(self::KEY_PREFIX . 'ttl' . $this->suffix));

        sleep(2);
        $this->assertFalse(Cache::load(self::KEY_PREFIX . 'ttl' . $this->suffix));
    }

    public function testNestedBlocksKeepTheirOwnKey(): void
    {
        $template = '{% pimcorecache "outer" ~ suffix %}O{% pimcorecache "inner" ~ suffix %}I{% endpimcorecache %}{% endpimcorecache %}';

        $this->assertSame('OI', $this->render($template));
        $this->assertSame('I', Cache::load(self::KEY_PREFIX . 'inner' . $this->suffix));
        $this->assertSame('OI', Cache::load(self::KEY_PREFIX . 'outer' . $this->suffix));
    }

    public function testMissingKeyIsSyntaxError(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Please specify a cache key');

        $this->render('{% pimcorecache %}x{% endpimcorecache %}');
    }

    public function testLiteralTtlOfWrongTypeIsSyntaxError(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "ttl" modifier requires an integer or null');

        $this->render('{% pimcorecache "k" ttl("abc") %}x{% endpimcorecache %}');
    }

    public function testDynamicTtlOfWrongTypeFailsAtRuntime(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('requires a non-negative integer or null');

        $this->render('{% pimcorecache "k" ttl(lifetime) %}x{% endpimcorecache %}', ['lifetime' => 'abc']);
    }

    public function testNegativeDynamicTtlFailsAtRuntime(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('requires a non-negative integer or null');

        $this->render('{% pimcorecache "k" ttl(lifetime) %}x{% endpimcorecache %}', ['lifetime' => -5]);
    }

    public function testKeyWithReservedCharactersIsHashed(): void
    {
        $template = '{% pimcorecache "nav_" ~ path ~ suffix %}{{ render_count() }}{% endpimcorecache %}';

        $this->assertSame('1', $this->render($template, ['path' => '/de/news:1']));
        $this->assertSame('1', $this->render($template, ['path' => '/de/news:1']));
        $this->assertSame('2', $this->render($template, ['path' => '/de/news:2']));
        $this->assertSame(2, $this->renderCount);
    }

    public function testEmptyKeyFailsAtRuntime(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('must not be empty');

        $this->render('{% pimcorecache key %}x{% endpimcorecache %}', ['key' => '']);
    }

    public function testTagWithReservedCharactersFailsAtRuntime(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('contains one of the characters');

        $this->render('{% pimcorecache "k" tags(extraTags) %}x{% endpimcorecache %}', ['extraTags' => ['a:b']]);
    }

    public function testDynamicTagsOfWrongTypeFailAtRuntime(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('requires a string or an array of strings');

        $this->render('{% pimcorecache "k" tags(extraTags) %}x{% endpimcorecache %}', ['extraTags' => [1]]);
    }
}
