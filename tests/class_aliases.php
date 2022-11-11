<?php

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

//FC layer for Test Support classes. @TODO Remove in Pimcore 11
$classAliases = [
    '\Pimcore\Tests\Support\Helper\DataType\Calculator' => '\Pimcore\Tests\Helper\DataType\Calculator',
    '\Pimcore\Tests\Support\Helper\DataType\TestDataHelper' => '\Pimcore\Tests\Helper\DataType\TestDataHelper',
    '\Pimcore\Tests\Support\Helper\Document\TestDataHelper' => '\Pimcore\Tests\Helper\Document\TestDataHelper',
    '\Pimcore\Tests\Support\Helper\Element\PropertiesTestHelper' => '\Pimcore\Tests\Helper\Element\PropertiesTestHelper',
    '\Pimcore\Tests\Support\Helper\AbstractDefinitionHelper' => '\Pimcore\Tests\Helper\AbstractDefinitionHelper',
    '\Pimcore\Tests\Support\Helper\AbstractTestDataHelper' => '\Pimcore\Tests\Helper\AbstractTestDataHelper',
    '\Pimcore\Tests\Support\Helper\ClassManager' => '\Pimcore\Tests\Helper\ClassManager',
    '\Pimcore\Tests\Support\Helper\Ecommerce' => '\Pimcore\Tests\Helper\Ecommerce',
    '\Pimcore\Tests\Support\Helper\Model' => '\Pimcore\Tests\Helper\Model',
    '\Pimcore\Tests\Support\Helper\Pimcore' => '\Pimcore\Tests\Helper\Pimcore',
    '\Pimcore\Tests\Support\Helper\Unit' => '\Pimcore\Tests\Helper\Unit',
    '\Pimcore\Tests\Support\Test\DataType\AbstractDataTypeTestCase' => '\Pimcore\Tests\Test\DataType\AbstractDataTypeTestCase',
    '\Pimcore\Tests\Support\Test\AbstractPropertiesTest' => '\Pimcore\Tests\Test\AbstractPropertiesTest',
    '\Pimcore\Tests\Support\Test\EcommerceTestCase' => '\Pimcore\Tests\Test\EcommerceTestCase',
    '\Pimcore\Tests\Support\Test\ModelTestCase' => '\Pimcore\Tests\Test\ModelTestCase',
    '\Pimcore\Tests\Support\Test\TestCase' => '\Pimcore\Tests\Test\TestCase',
    '\Pimcore\Tests\Support\Util\Autoloader' => '\Pimcore\Tests\Util\Autoloader',
    '\Pimcore\Tests\Support\Util\TestHelper' => '\Pimcore\Tests\Util\TestHelper',
];

foreach ($classAliases as $alias => $class) {
    if (!class_exists($alias, false)) {
        class_alias($class, $alias);
    }
}
