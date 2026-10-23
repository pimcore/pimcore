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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox;
use Pimcore\Model\DataObject\ClassDefinition\Data\Consent;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * `brickPrefix` arrives ready to concatenate - already quoted where it needs to be, and already
 * carrying its trailing dot. Quoting it a second time splits it on that dot
 * (quoteIdentifier('object_5.') gives `object_5`.``) and yields a malformed column reference that
 * MySQL rejects with "Unknown column 'object_5..flag'".
 *
 * AdminBundle's GridHelperService sends it in two shapes - unquoted `<listing table>.` for a plain
 * field, and quoted `` `<brickType>`. `` for an object brick field - so both are covered here,
 * along with the case where no prefix is supplied at all.
 */
class FilterConditionBrickPrefixTest extends TestCase
{
    private const LISTING_PREFIX = 'object_5.';

    public function testCheckboxConcatenatesThePrefixAsGiven(): void
    {
        $field = new Checkbox();
        $field->setName('flag');

        $this->assertSame(
            'IFNULL(object_5.`flag`, 0) = \'1\' ',
            $field->getFilterCondition(1, '=', ['brickPrefix' => self::LISTING_PREFIX])
        );
        $this->assertSame(
            'IFNULL(`myBrick`.`flag`, 0) = \'1\' ',
            $field->getFilterCondition(1, '=', ['brickPrefix' => $this->brickPrefix()])
        );
        $this->assertSame(
            'IFNULL(`flag`, 0) = \'1\' ',
            $field->getFilterCondition(1, '=', []),
            'An absent brickPrefix must neither be read unguarded nor add a stray qualifier.'
        );
    }

    public function testConsentConcatenatesThePrefixAsGiven(): void
    {
        $field = new Consent();
        $field->setName('optin');

        $this->assertSame(
            'IFNULL(object_5.`optin`, 0) = \'1\' ',
            $field->getFilterCondition(1, '=', ['brickPrefix' => self::LISTING_PREFIX])
        );
        $this->assertSame(
            'IFNULL(`myBrick`.`optin`, 0) = \'1\' ',
            $field->getFilterCondition(1, '=', ['brickPrefix' => $this->brickPrefix()])
        );
        $this->assertSame(
            'IFNULL(`optin`, 0) = \'1\' ',
            $field->getFilterCondition(1, '=', [])
        );
    }

    /**
     * Only the datetime column type reaches the branch that applies the prefix; the default
     * bigint storage takes a BETWEEN path that never qualifies the column.
     */
    public function testDatetimeEqualityConcatenatesThePrefixAsGiven(): void
    {
        $field = new Datetime();
        $field->setName('when');
        $field->setColumnType('datetime');

        $timestamp = mktime(0, 0, 0, 1, 1, 2026);

        $this->assertSame(
            'DATE(object_5.`when`) = \'2026-01-01\'',
            $field->getFilterCondition($timestamp, '=', ['brickPrefix' => self::LISTING_PREFIX])
        );
        $this->assertSame(
            'DATE(`myBrick`.`when`) = \'2026-01-01\'',
            $field->getFilterCondition($timestamp, '=', ['brickPrefix' => $this->brickPrefix()])
        );
        $this->assertSame(
            'DATE(`when`) = \'2026-01-01\'',
            $field->getFilterCondition($timestamp, '=', [])
        );
    }

    /**
     * The shape GridHelperService builds for an object brick field.
     */
    private function brickPrefix(): string
    {
        return Db::get()->quoteIdentifier('myBrick') . '.';
    }
}
