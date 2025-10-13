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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\ClassUtils;
use SplFileInfo;

class ClassUtilsTest extends TestCase
{
    public function testFindClassName(): void
    {
        $file = new SplFileInfo(__FILE__);
        $className = ClassUtils::findClassName($file);

        $this->assertEquals($className, self::class);
    }

    public function testFindNamespaceClassName(): void
    {
        //find classname for DummyNamespace/ClassX
        $file = new SplFileInfo(__DIR__ . '/../../Support/Resources/dummyfiles/ClassX.php');
        $className = ClassUtils::findClassName($file);

        $this->assertEquals('DummyNamespace\\ClassX', $className);

        //find classname for DummyNamespace/ClassY
        $file = new SplFileInfo(__DIR__ . '/../../Support/Resources/dummyfiles/ClassY.php');
        $className = ClassUtils::findClassName($file);

        $this->assertEquals('Pimcore\\DummyNamespace\\ClassY', $className);
    }
}
