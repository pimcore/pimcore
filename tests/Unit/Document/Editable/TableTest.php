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

use Pimcore\Model\Document\Editable\Table;
use Pimcore\Tests\Support\Test\TestCase;

class TableTest extends TestCase
{
    public function testFrontendEscapesEventHandlerMarkupInCells(): void
    {
        $table = new Table();
        $table->setDataFromEditmode([
            ['<div onmouseover=alert(document.domain)>hover</div>'],
        ]);

        $html = $table->frontend();

        $this->assertStringNotContainsString('<div onmouseover=', $html);
        $this->assertStringContainsString(
            '&lt;div onmouseover=alert(document.domain)&gt;hover&lt;/div&gt;',
            $html
        );
    }

    public function testFrontendLeavesPlainTextUnchanged(): void
    {
        $table = new Table();
        $table->setDataFromEditmode([
            ['Safe text'],
        ]);

        $this->assertSame(
            '<table border="0" cellpadding="0" cellspacing="0"><tr><td>Safe text</td></tr></table>',
            $table->frontend()
        );
    }

    public function testEmptyTableFrontend(): void
    {
        $table = new Table();

        $this->assertSame('', $table->frontend());
    }
}
