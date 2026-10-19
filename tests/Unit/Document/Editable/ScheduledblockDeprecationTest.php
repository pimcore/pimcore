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

use Pimcore\Model\Document\Editable\Scheduledblock;
use Pimcore\Tests\Support\Test\TestCase;
use Throwable;

/**
 * The scheduled block editable ("time slider") is deprecated since 2026.4 (pimcore/pimcore#18756).
 * Using it in a template must emit a deprecation naming the removal version.
 */
final class ScheduledblockDeprecationTest extends TestCase
{
    public function testRenderingAScheduledblockIsDeprecated(): void
    {
        $scheduledblock = new Scheduledblock();
        $scheduledblock->setName('schedule');
        $scheduledblock->setRealName('schedule');
        $scheduledblock->setDataFromEditmode([['key' => 0, 'date' => 1000]]);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $scheduledblock->start();
        } catch (Throwable) {
            // the markup of start() needs a fully configured editable; only the deprecation is of interest here
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('scheduledblock', $deprecations[0]);
        $this->assertStringContainsString('2026.4', $deprecations[0]);
        $this->assertStringContainsString('2027.1', $deprecations[0]);
    }
}
