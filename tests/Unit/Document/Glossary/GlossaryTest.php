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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Unit\Document\Glossary;

use Pimcore\Model\Glossary;
use Pimcore\Tests\Helper\Pimcore;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tool\Glossary\Processor;

class GlossaryTest extends TestCase
{
    /** @var Processor $processor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $pimcoreModule = $this->getModule('\\'.Pimcore::class);
        $this->processor = $pimcoreModule->grabService(Processor::class);
    }

    public function testGlossary()
    {
        $entry = new Glossary();
        $entry->setText('Glossary');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<body><p>This is a Test for the Glossary</p></body>', [], 'en', null, null);

        $expect = '<body><p>This is a Test for the <a class="pimcore_glossary" href="/test">Glossary</a></p></body>';

        $this->assertSame($result, $expect);
    }

    public function testGlossaryWithHtmlEntities()
    {
        $entry = new Glossary();
        $entry->setText('Entity');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<body><p>This is a Test for the&nbsp;Entity &copy;</p></body>',
            [],
            'en',
            null,
            null
        );

        $expect = '<body><p>This is a Test for the&nbsp;<a class="pimcore_glossary" href="/test">Entity</a> &copy;</p></body>';

        $this->assertSame($result, html_entity_decode($expect));
    }

    public function testGlossaryWithHtmlEntities2()
    {
        $entry = new Glossary();
        $entry->setText('Eintrag');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<body><p>Test &nbsp; Eintrag ©</p></body>', [], 'en', null, null);

        $expect = '<body><p>Test &nbsp; <a class="pimcore_glossary" href="/test">Eintrag</a> &copy;</p></body>';

        $this->assertSame($result, html_entity_decode($expect));
    }

    public function testGlossaryWithHtml()
    {
        $entry = new Glossary();
        $entry->setText('HTML');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<section class="c-content" id="c-20-content-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="text-content">
                        <div class="text-content__pre h6 text-center">Über uns</div>
                        <h2 class="text-content__title text-center">Seit&nbsp; 1909</h2>
                        <p>Another &nbsp; HTML &copy;</p>
                    </div>
                </div>
            </div>
        </div>
    </section>', [],
            'en',
            null,
            null
        );

        $expect = '<section class="c-content" id="c-20-content-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="text-content">
                        <div class="text-content__pre h6 text-center">Über uns</div>
                        <h2 class="text-content__title text-center">Seit&nbsp; 1909</h2>
                        <p>Another &nbsp; <a class="pimcore_glossary" href="/test">HTML</a> ©</p>
                    </div>
                </div>
            </div>
        </div>
    </section>';

        $this->assertSame($result, $expect);
    }
}
