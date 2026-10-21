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
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool;
use ReflectionProperty;

class RequiredLanguagesTest extends ModelTestCase
{
    private ?array $originalSystemSettings = null;

    protected function setUp(): void
    {
        parent::setUp();

        $systemSettings = SystemSettingsConfig::get();
        unset($systemSettings['writeable']);
        $this->originalSystemSettings = $systemSettings;

        $this->resetRequiredLanguagesCache();
    }

    protected function tearDown(): void
    {
        if (null !== $this->originalSystemSettings) {
            $this->systemSettingsConfig()->testSave($this->originalSystemSettings);
            $this->originalSystemSettings = null;
        }

        $this->resetRequiredLanguagesCache();

        parent::tearDown();
    }

    public function testNoRequiredLanguagesFallBackToAllValidLanguages(): void
    {
        $this->storeRequiredLanguages([]);

        $this->assertSame(Tool::getValidLanguages(), Tool::getRequiredLanguages());
    }

    public function testStoredEmptyStringIsNotARequiredLanguage(): void
    {
        // what older versions persisted when no language was marked as mandatory
        $this->storeRequiredLanguages(['']);

        $this->assertSame(Tool::getValidLanguages(), Tool::getRequiredLanguages());
    }

    public function testSavingWithoutMandatoryLanguageStoresEmptyList(): void
    {
        $this->saveSystemSettingsFormValues(['general.requiredLanguages' => '']);

        $this->assertSame([], SystemSettingsConfig::get()['general']['required_languages']);
    }

    public function testSavingKeepsSelectedMandatoryLanguages(): void
    {
        $languages = Tool::getValidLanguages();
        $this->saveSystemSettingsFormValues(['general.requiredLanguages' => implode(',', $languages)]);

        $this->assertSame($languages, SystemSettingsConfig::get()['general']['required_languages']);
    }

    private function storeRequiredLanguages(array $requiredLanguages): void
    {
        $systemSettings = $this->originalSystemSettings;
        $systemSettings['general']['required_languages'] = $requiredLanguages;
        $this->systemSettingsConfig()->testSave($systemSettings);

        $this->resetRequiredLanguagesCache();
    }

    private function saveSystemSettingsFormValues(array $overrides): void
    {
        $general = $this->originalSystemSettings['general'];
        $values = [
            'general.domain' => $general['domain'] ?? '',
            'general.redirect_to_maindomain' => $general['redirect_to_maindomain'] ?? false,
            'general.validLanguages' => implode(',', $general['valid_languages'] ?? []),
            'general.requiredLanguages' => '',
            'general.defaultLanguage' => $general['default_language'] ?? '',
            'general.debug_admin_translations' => $general['debug_admin_translations'] ?? false,
            'documents.error_pages.default' => $this->originalSystemSettings['documents']['error_pages']['default'] ?? '',
        ];

        $this->systemSettingsConfig()->save(array_merge($values, $overrides));

        $this->resetRequiredLanguagesCache();
    }

    private function systemSettingsConfig(): SystemSettingsConfig
    {
        return new SystemSettingsConfig(
            Pimcore::getContainer()->get('event_dispatcher'),
            Pimcore::getContainer()->get(LocaleServiceInterface::class)
        );
    }

    private function resetRequiredLanguagesCache(): void
    {
        $property = new ReflectionProperty(Tool::class, 'requiredLanguages');
        $property->setValue(null, []);

        RuntimeCache::clear();
    }
}
