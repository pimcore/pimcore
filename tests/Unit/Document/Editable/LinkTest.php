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

namespace Pimcore\Tests\Unit\Document\Editable;

use Pimcore\Model\Document\Editable\Link;
use Pimcore\Tests\Support\Test\TestCase;

class LinkTest extends TestCase
{
    public function testGetHrefEscapesAttributeBreakoutCharacters(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => '#x" onclick="alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertSame('#x&quot; onclick=&quot;alert(document.domain)', $link->getHref());
    }

    public function testGetHrefStripsJavascriptScheme(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'javascript:alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testGetHrefStripsJavascriptSchemeWithEmbeddedWhitespace(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => "java\tscript:alert(document.domain)",
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testGetHrefStripsDataScheme(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'data:text/html,<script>alert(document.domain)</script>',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testFrontendDoesNotEmitAttributeBreakoutOrScriptScheme(): void
    {
        $xssAttribute = new Link();
        $xssAttribute->setDataFromResource([
            'path' => '#x" onclick="alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertStringNotContainsString('onclick=', $xssAttribute->frontend());

        $xssScheme = new Link();
        $xssScheme->setDataFromResource([
            'path' => 'javascript:alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $xssScheme->frontend());
    }

    public function testGetHrefKeepsLegitimatePathIntact(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com/some/page',
            'linktype' => 'direct',
        ]);

        $this->assertSame('https://example.com/some/page', $link->getHref());
    }
}
