<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Model\User\Workspace;

use Pimcore\Model\User\Workspace\DataObject;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * An empty lEdit/lView value is documented to mean "every language is allowed", the same as
 * NULL. Some callers (e.g. a MultiSelect widget with nothing selected) submit '' rather than
 * NULL, so the setters must normalize '' to NULL to keep that contract intact for every reader.
 *
 * @group model.user.workspace.dataobject
 */
class DataObjectTest extends TestCase
{
    public function testSetLEditNormalizesEmptyStringToNull(): void
    {
        $workspace = new DataObject();
        $workspace->setLEdit('');

        self::assertNull($workspace->getLEdit());
    }

    public function testSetLViewNormalizesEmptyStringToNull(): void
    {
        $workspace = new DataObject();
        $workspace->setLView('');

        self::assertNull($workspace->getLView());
    }

    public function testSetLEditKeepsNullAsNull(): void
    {
        $workspace = new DataObject();
        $workspace->setLEdit(null);

        self::assertNull($workspace->getLEdit());
    }

    public function testSetLViewKeepsNullAsNull(): void
    {
        $workspace = new DataObject();
        $workspace->setLView(null);

        self::assertNull($workspace->getLView());
    }

    public function testSetLEditKeepsNonEmptyValueUnchanged(): void
    {
        $workspace = new DataObject();
        $workspace->setLEdit('en,de');

        self::assertSame('en,de', $workspace->getLEdit());
    }

    public function testSetLViewKeepsNonEmptyValueUnchanged(): void
    {
        $workspace = new DataObject();
        $workspace->setLView('en,de');

        self::assertSame('en,de', $workspace->getLView());
    }
}
