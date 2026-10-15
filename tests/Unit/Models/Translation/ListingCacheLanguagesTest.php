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

namespace Pimcore\Tests\Unit\Models\Translation;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Translation;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for pimcore/platform-version#594: Translation\Listing\Dao::load() built its cache
 * key from the SQL only, but the listing's languages are applied afterwards when hydrating each
 * translation. Two listings differing only in their languages therefore shared one cache entry.
 */
final class ListingCacheLanguagesTest extends TestCase
{
    private const TRANSLATION_KEY = 'pv_594_listing_cache_languages';

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $translation = new Translation();
        $translation->setKey(self::TRANSLATION_KEY);
        $translation->setTranslations(['en' => 'English text', 'de' => 'Deutscher Text']);
        $translation->save();

        Cache::enable();
        Cache::clearTags(['translator', 'translate']);

        // make Cache::save() write immediately within this CLI test process, also for the tags that
        // were just cleared by Translation::save() / Cache::clearTags() above
        Cache::getHandler()
            ->setHandleCli(true)
            ->setForceImmediateWrite(true)
            ->removeClearedTags(['translator', 'translate']);
    }

    protected function tearDown(): void
    {
        // delete the fixture and invalidate its cache entries first (both mark the tags as cleared) ...
        Translation::getByKey(self::TRANSLATION_KEY)?->delete();
        Cache::clearTags(['translator', 'translate']);

        // ... then restore the shared handler state, so later tests can write these tags again
        Cache::getHandler()
            ->removeClearedTags(['translator', 'translate'])
            ->setHandleCli(false)
            ->setForceImmediateWrite(false);
        Cache::disable();

        parent::tearDown();
    }

    public function testListingsWithDifferentLanguagesDoNotShareTheCacheEntry(): void
    {
        $this->assertSame(['en'], array_keys($this->loadTranslationsFor(['en'])));

        // start of a new request: only the persistent cache is shared
        RuntimeCache::clear();

        $this->assertSame(
            ['de'],
            array_keys($this->loadTranslationsFor(['de'])),
            'A listing for [de] must not be served the cached result of a listing for [en].'
        );
    }

    /**
     * @param string[] $languages
     *
     * @return array<string, string>
     */
    private function loadTranslationsFor(array $languages): array
    {
        $listing = new Translation\Listing();
        $listing->setLanguages($languages);

        // no condition on purpose: listings with condition variables bypass the cache entirely
        foreach ($listing->load() as $translation) {
            if ($translation->getKey() === self::TRANSLATION_KEY) {
                return $translation->getTranslations();
            }
        }

        $this->fail('Translation "' . self::TRANSLATION_KEY . '" not found in the listing.');
    }
}
