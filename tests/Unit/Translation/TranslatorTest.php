<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Translation;

use Pimcore\Model\Translation\AbstractTranslation;
use Pimcore\Model\Translation\Website;
use Pimcore\Translation\Translator;
use Pimcore\Tests\Test\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorTest extends TestCase
{
    /** @var Translator */
    protected $translator;

    /**
     * ['locale' => 'fallback']
     *
     * @var array
     */
    protected $locales = [
        'en' => '',
        'de' => 'en',
        'fr' => '',
    ];

    protected $translations = [
        'en' => [
            'simple_key' => 'EN Text',
            'Text As Key' => 'EN Text',
        ],
        'de' => [
            'simple_key' => 'DE Text',
            'Text As Key' => '',
        ],
        'fr' => [
            'simple_key' => 'FR Text',
            'Text As Key' => '',
        ]
    ];

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translator = \Pimcore::getContainer()->get(TranslatorInterface::class);
        $this->addTranslations();
    }

    protected function tearDown()
    {
        $this->removeTranslations();
        parent::tearDown();
    }

    private function addTranslations()
    {
        foreach ($this->locales as $locale => $fallback) {
            foreach ($this->translations[$locale] as $transKey => $trans) {
                $t = new Website();
                $t->setKey($transKey);
                $t->addTranslation($locale, $trans ?? '');
                $t->save();
            }
        }
    }

    private function removeTranslations()
    {
        foreach ($this->locales as $locale => $fallback) {
            foreach ($this->translations[$locale] as $transKey => $trans) {
                $t = Website::getByKey($transKey);
                if ($t instanceof Website) {
                   $t->delete();
                }
            }
        }
    }

    public function testTranslateSimpleText()
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
    }

    public function testTranslateTextAsKey()
    {
        //Returns Translated value
        $this->translator->setLocale('en');
        $this->assertEquals($this->translations['en']['Text As Key'], $this->translator->trans('Text As Key'));

        //Returns Fallback value
        $this->translator->setLocale('de');
        $this->assertEquals($this->translations['en']['simple_key'], $this->translator->trans('Text As Key'));

        //Returns Key value (no translation + no fallback)
        $this->translator->setLocale('fr');
        $this->assertEquals('Text As Key', $this->translator->trans('Text As Key'));
    }
}
