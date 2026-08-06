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

namespace Pimcore\Tests\Unit\Translation;

use Pimcore\Db;
use Pimcore\Model\Translation;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression tests for PEES-1281 / pimcore/service-operations#937: Translation::isAValidDomain()
 * ran an uncached "SELECT ... FROM translations_<domain> LIMIT 1" existence probe on every call,
 * which is executed repeatedly (once per translation lookup) for domains without a table, most
 * notably the deprecated "admin" domain that stays registered but never gets a table anymore.
 */
class TranslationValidDomainCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropAdminDomainTable();
    }

    protected function tearDown(): void
    {
        $this->dropAdminDomainTable();

        parent::tearDown();
    }

    private function dropAdminDomainTable(): void
    {
        Db::get()->executeStatement('DROP TABLE IF EXISTS translations_admin');
    }

    private function saveAdminDomainTranslation(string $key): void
    {
        $translation = new Translation();
        $translation->setDomain(Translation::DOMAIN_ADMIN);
        $translation->setKey($key);
        $translation->setTranslations(['en' => 'test']);
        $translation->save();
    }

    public function testIsAValidDomainResultIsMemoizedForTheRestOfTheRequest(): void
    {
        $this->saveAdminDomainTranslation('pees_1281_memoized_key');

        self::assertTrue(
            Translation::isAValidDomain(Translation::DOMAIN_ADMIN),
            'The table was just created by save(), so the domain must be recognized as valid.'
        );

        // Simulate the table disappearing without going through Pimcore's own API (e.g. a
        // manual DROP). If isAValidDomain() re-queries instead of trusting the memoized
        // result from the call above, it will now (incorrectly) see the table as gone.
        $this->dropAdminDomainTable();

        self::assertTrue(
            Translation::isAValidDomain(Translation::DOMAIN_ADMIN),
            'A second call within the same request must not re-query the database - it must '
            .'trust the previously memoized result instead of repeating the existence check.'
        );
    }

    public function testIsAValidDomainCacheIsInvalidatedOnceTheDomainTableIsCreated(): void
    {
        self::assertFalse(
            Translation::isAValidDomain(Translation::DOMAIN_ADMIN),
            'A fresh install has no translations_admin table, so the domain must be invalid yet.'
        );

        $this->saveAdminDomainTranslation('pees_1281_invalidation_key');

        self::assertTrue(
            Translation::isAValidDomain(Translation::DOMAIN_ADMIN),
            'Once the table has been created, the earlier memoized "invalid" result must not '
            .'stick around for the rest of the request.'
        );
    }
}
