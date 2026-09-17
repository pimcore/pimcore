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

namespace Pimcore\Tests\Unit\DataObject\ClassificationstoreDataMarshaller;

use Pimcore\DataObject\ClassificationstoreDataMarshaller\Table;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Probe class used to detect whether __wakeup is invoked during a restricted unserialize.
 */
class TableObjectInjectionProbe
{
    public static bool $wakeupCalled = false;

    public function __wakeup(): void
    {
        self::$wakeupCalled = true;
    }
}

/**
 * @group unit.classificationstore.marshaller.table
 */
class TableTest extends TestCase
{
    /**
     * Verifies that a serialized payload embedding an arbitrary object is not instantiated
     * (no __wakeup) when the Table marshaller unmarshals classificationstore data.
     *
     * Regression test for: Serialize::unserialize($value['value'], false)
     */
    public function testUnmarshalDoesNotInstantiateArbitraryObjects(): void
    {
        TableObjectInjectionProbe::$wakeupCalled = false;

        $value = ['value' => serialize([new TableObjectInjectionProbe()])];

        (new Table())->unmarshal($value);

        $this->assertFalse(
            TableObjectInjectionProbe::$wakeupCalled,
            '__wakeup() must not be called: the table marshaller must deserialize with allowed_classes => false'
        );
    }
}
