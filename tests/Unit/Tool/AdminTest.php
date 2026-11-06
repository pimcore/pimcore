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
use Pimcore\Tool\Admin;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Admin::getLanguages() is called for every admin translation lookup, so it scans the
 * translations directory once and answers from memory afterwards.
 */
class AdminTest extends TestCase
{
    private ReflectionProperty $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languages = new ReflectionProperty(Admin::class, 'languages');
        $this->languages->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $this->languages->setValue(null, null);

        parent::tearDown();
    }

    public function testReturnsTheLanguagesFoundInTheTranslationsDirectory(): void
    {
        $found = (new ReflectionMethod(Admin::class, 'findLanguages'))->invoke(null);

        $this->assertSame($found, Admin::getLanguages());
    }

    public function testScansTheTranslationsDirectoryOnlyOnce(): void
    {
        $first = Admin::getLanguages();
        $this->assertSame($first, $this->languages->getValue());

        // a second call must answer from memory, not scan again
        $this->languages->setValue(null, ['xx']);

        $this->assertSame(['xx'], Admin::getLanguages());
    }
}
