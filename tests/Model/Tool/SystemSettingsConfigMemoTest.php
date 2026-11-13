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

namespace Pimcore\Tests\Model\Tool;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * The system settings are read on hot paths (e.g. the fallback languages of every empty
 * localized value). With the settings store as read target, each read is a database query,
 * so the settings have to be kept for the rest of the request once they are loaded.
 */
class SystemSettingsConfigMemoTest extends ModelTestCase
{
    private const TEST_DOMAIN = 'system-settings-memo.test';

    private const SETTINGS_STORE_ID = 'system_settings';

    private const SETTINGS_STORE_SCOPE = 'pimcore_system_settings';

    private DebugDataHolder $debugDataHolder;

    private ?array $originalSystemSettings = null;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getModule('\\' . Pimcore\Tests\Support\Helper\Pimcore::class)->_getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        $this->debugDataHolder = $container->get('doctrine.debug_data_holder');

        RuntimeCache::clear();

        $systemSettings = SystemSettingsConfig::get();
        unset($systemSettings['writeable']);
        $this->originalSystemSettings = $systemSettings;

        RuntimeCache::clear();
    }

    protected function tearDown(): void
    {
        if (null !== $this->originalSystemSettings) {
            $this->systemSettingsConfig()->testSave($this->originalSystemSettings);
            $this->originalSystemSettings = null;
        }

        RuntimeCache::clear();

        parent::tearDown();
    }

    public function testSettingsStoreIsQueriedOnlyOnce(): void
    {
        $this->debugDataHolder->reset();
        $first = SystemSettingsConfig::get();

        $this->assertGreaterThan(
            0,
            $this->countSettingsStoreQueries(),
            'precondition: the test configuration reads the system settings from the settings store'
        );

        $this->debugDataHolder->reset();

        $this->assertSame($first, SystemSettingsConfig::get());
        $this->assertSame($first, SystemSettingsConfig::get());
        $this->assertSame(0, $this->countSettingsStoreQueries());
    }

    public function testLanguageLookupsDoNotQueryTheSettingsStorePerCall(): void
    {
        SystemSettingsConfig::get();
        $this->debugDataHolder->reset();

        for ($i = 0; $i < 25; $i++) {
            Tool::getFallbackLanguagesFor('de');
            Tool::getDefaultLanguage();
        }

        $this->assertSame(0, $this->countSettingsStoreQueries());
    }

    public function testClearingTheRuntimeCacheReloadsTheSettings(): void
    {
        SystemSettingsConfig::get();
        RuntimeCache::clear();
        $this->debugDataHolder->reset();

        SystemSettingsConfig::get();

        $this->assertGreaterThan(0, $this->countSettingsStoreQueries());
    }

    public function testTestSaveIsVisibleToTheNextRead(): void
    {
        $this->assertNotSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain'] ?? null);

        $changed = $this->originalSystemSettings;
        $changed['general']['domain'] = self::TEST_DOMAIN;
        $this->systemSettingsConfig()->testSave($changed);

        $this->assertSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain']);
    }

    public function testSaveIsVisibleToTheNextRead(): void
    {
        $general = SystemSettingsConfig::get()['general'];
        $this->assertNotSame(self::TEST_DOMAIN, $general['domain'] ?? null);

        $this->systemSettingsConfig()->save([
            'general.domain' => self::TEST_DOMAIN,
            'general.redirect_to_maindomain' => $general['redirect_to_maindomain'] ?? false,
            'general.validLanguages' => implode(',', $general['valid_languages'] ?? []),
            'general.requiredLanguages' => '',
            'general.defaultLanguage' => $general['default_language'] ?? '',
            'general.debug_admin_translations' => $general['debug_admin_translations'] ?? false,
            'documents.error_pages.default' => $this->originalSystemSettings['documents']['error_pages']['default'] ?? '',
        ]);

        $this->assertSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain']);
    }

    public function testDirectSettingsStoreWriteIsVisibleToTheNextRead(): void
    {
        $this->assertNotSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain'] ?? null);

        $entry = SettingsStore::get(self::SETTINGS_STORE_ID, self::SETTINGS_STORE_SCOPE);
        $this->assertNotNull($entry, 'precondition: the system settings are stored in the settings store');

        $data = json_decode($entry->getData(), true);
        $data['general']['domain'] = self::TEST_DOMAIN;
        SettingsStore::set(self::SETTINGS_STORE_ID, json_encode($data), SettingsStore::TYPE_STRING, self::SETTINGS_STORE_SCOPE);

        $this->assertSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain']);
    }

    public function testDeletingSystemSettingsStoreEntryReloadsContainerConfig(): void
    {
        $this->assertNotSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain'] ?? null);

        $entry = SettingsStore::get(self::SETTINGS_STORE_ID, self::SETTINGS_STORE_SCOPE);
        $this->assertNotNull($entry, 'precondition: the system settings are stored in the settings store');

        $data = json_decode($entry->getData(), true);
        $data['general']['domain'] = self::TEST_DOMAIN;
        SettingsStore::set(self::SETTINGS_STORE_ID, json_encode($data), SettingsStore::TYPE_STRING, self::SETTINGS_STORE_SCOPE);
        $this->assertSame(self::TEST_DOMAIN, SystemSettingsConfig::get()['general']['domain']);

        SettingsStore::delete(self::SETTINGS_STORE_ID, self::SETTINGS_STORE_SCOPE);
        $this->debugDataHolder->reset();

        $this->assertSame(
            Config::getSystemConfiguration()['general']['domain'],
            SystemSettingsConfig::get()['general']['domain']
        );
        $this->assertGreaterThan(0, $this->countSettingsStoreQueries());
    }

    public function testWritesToOtherSettingsStoreScopesKeepTheLoadedSettings(): void
    {
        SystemSettingsConfig::get();
        $this->debugDataHolder->reset();

        SettingsStore::set('system-settings-memo-test', 'value', SettingsStore::TYPE_STRING, 'system-settings-memo-test');
        SettingsStore::delete('system-settings-memo-test', 'system-settings-memo-test');
        SystemSettingsConfig::get();

        $this->assertSame(0, $this->countSettingsStoreQueries('SELECT'));
    }

    private function systemSettingsConfig(): SystemSettingsConfig
    {
        return new SystemSettingsConfig(
            Pimcore::getContainer()->get('event_dispatcher'),
            Pimcore::getContainer()->get(LocaleServiceInterface::class)
        );
    }

    private function countSettingsStoreQueries(string $statement = ''): int
    {
        $count = 0;
        foreach ($this->debugDataHolder->getData() as $queries) {
            foreach ($queries as $query) {
                $sql = (string) $query['sql'];
                if (str_contains($sql, 'settings_store') && str_starts_with(ltrim($sql), $statement)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
