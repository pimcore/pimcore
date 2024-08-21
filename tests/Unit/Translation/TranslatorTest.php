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

namespace Pimcore\Tests\Unit\Translation;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\Translation;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorTest extends TestCase
{
    protected Translator $translator;

    /**
     * ['locale' => 'fallback']
     *
     */
    protected array $locales = [
        'en' => '',
        'de' => 'en',
        'fr' => '',
    ];

    protected array $translations = [
        'en' => [
            'simple_key' => 'EN Text',
            'fallback_key' => 'EN Fallback',
            'Text As Key' => 'EN Text',
            'text_params' => 'Text with %Param1% and %Param2%',
            'count_key' => '%count% Count',
            'count_key_190' => 'This is a translated text generated from translator service, using count parameter to be replaced from passed parameters and having %count% characters to test text greater than 190 characters.',
            'count_plural_1' => '1 Item',
            'count_plural_n' => '%count% Items',
            'case_key' => 'Lower Case Key',
            'CASE_KEY' => 'Upper Case Key',
        ],
        'de' => [
            'simple_key' => 'DE Text',
            'fallback_key' => '',
            'Text As Key' => '',
            'text_params' => '',
            'count_key' => '',
        ],
        'fr' => [
            'simple_key' => 'FR Text',
            'Text As Key' => '',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = Pimcore::getContainer()->get(TranslatorInterface::class);
        $this->addTranslations();
    }

    protected function tearDown(): void
    {
        $this->removeTranslations();
        parent::tearDown();
    }

    private function addTranslations(): void
    {
        foreach ($this->locales as $locale => $fallback) {
            foreach ($this->translations[$locale] as $transKey => $trans) {
                $t = Translation::getByKey($transKey, Translation::DOMAIN_DEFAULT, true);
                $t->addTranslation($locale, $trans ?? '');
                $t->save();
            }
        }
    }

    private function removeTranslations(): void
    {
        foreach ($this->locales as $locale => $fallback) {
            foreach ($this->translations[$locale] as $transKey => $trans) {
                $t = Translation::getByKey($transKey);
                if ($t instanceof Translation) {
                    $t->delete();
                }
            }
        }
    }

    public function testTranslateSimpleText(): void
    {
        //Translate en
        $this->translator->setLocale('en');
        $this->assertEquals($this->translations['en']['simple_key'], $this->translator->trans('simple_key'));

        //Translate de
        $this->translator->setLocale('de');
        $this->assertEquals($this->translations['de']['simple_key'], $this->translator->trans('simple_key'));

        //Translate fr
        $this->translator->setLocale('fr');
        $this->assertEquals($this->translations['fr']['simple_key'], $this->translator->trans('simple_key'));

        //Returns Fallback("en") value
        $this->translator->setLocale('de');
        $this->assertEquals($this->translations['en']['fallback_key'], $this->translator->trans('fallback_key'));
    }

    public function testTranslateTextAsKey(): void
    {
        //Returns Translated value
        $this->translator->setLocale('en');
        $this->assertEquals($this->translations['en']['Text As Key'], $this->translator->trans('Text As Key'));

        //Returns Fallback("en") value
        $this->translator->setLocale('de');
        $this->assertEquals($this->translations['en']['simple_key'], $this->translator->trans('Text As Key'));

        //Returns Key value (no translation + no fallback)
        //        $this->translator->setLocale('fr');
        //        $this->assertEquals('Text As Key', $this->translator->trans('Text As Key'));
    }

    public function testTranslateTextWithParams(): void
    {
        //Returns Translated value with params value
        $this->translator->setLocale('en');
        $this->assertEquals(
            strtr($this->translations['en']['text_params'],
                [   '%Param1%' => 'First Parameter',
                    '%Param2%' => 'Second Parameter',
                ]
            ),
            $this->translator->trans('text_params',
                [   '%Param1%' => 'First Parameter',
                    '%Param2%' => 'Second Parameter',
                ]
            )
        );

        //Returns Fallback("en") value with params value
        $this->translator->setLocale('de');
        $this->assertEquals(
            strtr($this->translations['en']['text_params'],
                [   '%Param1%' => 'First Parameter',
                    '%Param2%' => 'Second Parameter',
                ]
            ),
            $this->translator->trans('text_params',
                [   '%Param1%' => 'First Parameter',
                    '%Param2%' => 'Second Parameter',
                ]
            )
        );
    }

    public function testTranslateWithCountParam(): void
    {
        $this->translator->setLocale('en');
        $this->assertEquals('2 Count', $this->translator->trans('count_key', ['%count%' => 2]));

        //fallback
        $this->translator->setLocale('de');
        $this->assertEquals('2 Count', $this->translator->trans('count_key', ['%count%' => 2]));
    }

    public function testTranslateLongerTextWithCountParam(): void
    {
        $this->translator->setLocale('en');
        $this->assertEquals(strtr($this->translations['en']['count_key_190'], ['%count%' => 192]), $this->translator->trans('count_key_190', ['%count%' => 192]));
    }

    public function testTranslatePluralizationWithCountParam(): void
    {
        $this->translator->setLocale('en');
        $this->assertEquals($this->translations['en']['count_plural_1'], $this->translator->trans('count_plural_1|count_plural_n', ['%count%' => 1]));
        $this->assertEquals(strtr($this->translations['en']['count_plural_n'], ['%count%' => 5]), $this->translator->trans('count_plural_1|count_plural_n', ['%count%' => 5]));
    }

    public function testTranslateCaseSensitive(): void
    {
        // Case sensitive
        $this->translator->setLocale('en');
        //Lower case key
        $this->assertEquals($this->translations['en']['case_key'], $this->translator->trans('case_key'));

        //Upper case key
        $this->assertEquals($this->translations['en']['CASE_KEY'], $this->translator->trans('CASE_KEY'));
    }

    public function testLoadingTranslationList(): void
    {
        $translations = new Translation\Listing();
        $translations->setDomain('messages');

        $translations = $translations->getTranslations();
        $this->assertCount(count($this->translations['en']), $translations);

        $translations = new Translation\Listing();
        $translations->setDomain('messages');
        $translations->addConditionParam('`key` like :key', ['key' => 'simple%']);

        $translations = $translations->getTranslations();
        $this->assertCount(1, $translations);

        //test: Filter by languages
        $translations = new Translation\Listing();
        $translations->setDomain('messages');
        $translations->setLanguages(['en', 'de']);

        $translations = $translations->getTranslations();
        $translationValues = $translations[0]->getTranslations();
        $this->assertArrayNotHasKey('fr', $translationValues);
    }

    public function testCacheGetsInvalidatedOnSave(): void
    {
        $translationsListing = new Translation\Listing();
        $translationsListing->setDomain('messages');
        $beforeAdd = $translationsListing->load();

        $translation = new Translation();
        $translation->setDomain('messages');
        $translation->setKey('test');
        $translation->setTranslations(['en' => 'test']);
        $translation->save();

        $afterAdd = $translationsListing->load();

        $this->assertCount(count($beforeAdd) + 1, $afterAdd);
    }

    public function testSanitizedTranslation(): void
    {
        $translation = new Translation();
        $key = 'sanitizerTest';
        $translation->setDomain('messages');
        $translation->setKey($key);
        $translation->setTranslations(['en' => '!@#$%^abc\'"<script>console.log("ops");</script> 测试&lt; edf &gt; "']);
        $translation->save();

        RuntimeCache::clear();

        $translation = Translation::getByKey($key);
        $getter = $translation->getTranslation('en');
        $this->assertEquals('!@#$%^abc\'" 测试< edf > "', html_entity_decode($getter), 'Asserting translation is properly sanitized');

        $db = Db::get();
        $dbValue = $db->fetchOne(
            sprintf(
                'SELECT `text` FROM translations_messages WHERE `key` = %s AND `language` = %s',
                $db->quote($key),
                $db->quote('en')
            )
        );
        $this->assertEquals('!@#$%^abc\'" 测试< edf > "', html_entity_decode($dbValue), 'Asserting translation is persisted as sanitized');
    }
}
