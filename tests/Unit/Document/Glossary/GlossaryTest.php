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

namespace Pimcore\Tests\Unit\Document\Glossary;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Bundle\GlossaryBundle\Tool\Processor;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\TestCase;

class GlossaryTest extends TestCase
{
    protected Processor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $pimcoreModule = $this->getModule('\\'.Pimcore::class);
        $this->processor = $pimcoreModule->grabService(Processor::class);
    }

    public function testGlossary(): void
    {
        $entry = new Glossary();
        $entry->setText('Glossary');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<head></head><body><p>This is a Test for the Glossary</p></body>', [], 'en', null, null);

        $expect = '<head></head><body><p>This is a Test for the <a class="pimcore_glossary" href="/test">Glossary</a></p></body>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryWithHtmlEntities(): void
    {
        $entry = new Glossary();
        $entry->setText('Entity');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<head></head><body><p>This is a Test for the&nbsp;Entity &copy;</p></body>',
            [],
            'en',
            null,
            null
        );

        $expect = '<head></head><body><p>This is a Test for the&nbsp;<a class="pimcore_glossary" href="/test">Entity</a> &copy;</p></body>';

        $this->assertSame(html_entity_decode($expect), $result);
    }

    public function testGlossaryWithHtmlEntities2(): void
    {
        $entry = new Glossary();
        $entry->setText('Eintrag');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<head></head><body><p>Test &nbsp; Eintrag ©</p></body>', [], 'en', null, null);

        $expect = '<head></head><body><p>Test &nbsp; <a class="pimcore_glossary" href="/test">Eintrag</a> &copy;</p></body>';

        $this->assertSame(html_entity_decode($expect), $result);
    }

    public function testGlossaryWithHtml(): void
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

        $this->assertSame(html_entity_decode($expect), html_entity_decode($result));
    }

    public function testGlossaryWithAnotherHtml(): void
    {
        $entry = new Glossary();
        $entry->setText('hans');
        $entry->setLink('/hans');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<p>hans &amp; gretl</p>', [],
            'en',
            null,
            null
        );

        $expect = '<p><a class="pimcore_glossary" href="/hans">hans</a> &amp; gretl</p>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryWithLowerThenAndGreaterThenHtml(): void
    {
        $entry = new Glossary();
        $entry->setText('huber');
        $entry->setLink('/huber');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<p>Huber &lt;&gt; is the best</p>', [],
            'en',
            null,
            null
        );

        $expect = '<p><a class="pimcore_glossary" href="/huber">huber</a> &lt;&gt; is the best</p>';

        $this->assertSame($expect, $result);
    }
}
