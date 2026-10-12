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

namespace Pimcore\Tests\Model\Asset;

use Carbon\Carbon;
use DateTimeInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\WebDAV\Folder as WebDavFolder;
use Pimcore\Model\Asset\WebDAV\Service;
use Pimcore\Model\Asset\WebDAV\Tree;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionMethod;

/**
 * Unit coverage for the WebDAV delete-log housekeeping and the security-sensitive property
 * hydration of Tree::restoreProperties(). The end-to-end restore behaviour (Tree::move()
 * reusing the deleted destination id) is covered by WebDavIntegrationTest.
 *
 * @group model.asset.webdav
 */
class WebDavTest extends ModelTestCase
{
    protected function needsDb(): bool
    {
        return false;
    }

    protected function tearDown(): void
    {
        if (file_exists(Service::getDeleteLogFile())) {
            unlink(Service::getDeleteLogFile());
        }

        parent::tearDown();
    }

    /**
     * The delete log stores only scalar identity data (path => [id, timestamp]); this must
     * survive the serialize/save/read round-trip without needing any object instantiation.
     */
    public function testDeleteLogStoresIdAndSurvivesRoundTrip(): void
    {
        Service::saveDeleteLog([
            '/some/path.jpg' => [
                'id' => 42,
                'timestamp' => time(),
            ],
        ]);

        $log = Service::getDeleteLog();

        $this->assertArrayHasKey('/some/path.jpg', $log);
        $this->assertSame(42, $log['/some/path.jpg']['id']);
    }

    /**
     * Old entries (>30s) must be pruned by the delete-log housekeeping so stale ids are never
     * reused during a move.
     */
    public function testDeleteLogPrunesStaleEntries(): void
    {
        Service::saveDeleteLog([
            '/stale' => [
                'id' => 1,
                'timestamp' => time() - 60,
            ],
            '/fresh' => [
                'id' => 2,
                'timestamp' => time(),
            ],
        ]);

        $log = Service::getDeleteLog();

        $this->assertArrayNotHasKey('/stale', $log);
        $this->assertArrayHasKey('/fresh', $log);
    }

    /**
     * A legacy date-property row (see the schema note in WebDavIntegrationTest) holds a
     * serialized datetime; the allowlisted hydration in Tree::restoreProperties() must rebuild
     * it into a real DateTimeInterface value.
     */
    public function testRestorePropertiesHydratesAllowlistedDateRow(): void
    {
        $timestamp = mktime(12, 0, 0, 3, 14, 2026);

        $asset = new Asset();
        $this->invokeRestoreProperties($asset, [
            [
                'name' => 'launch',
                'type' => 'date',
                'data' => serialize(Carbon::createFromTimestamp($timestamp)),
                'inheritable' => 0,
            ],
        ]);

        $data = $asset->getProperty('launch');
        $this->assertInstanceOf(DateTimeInterface::class, $data);
        $this->assertSame($timestamp, $data->getTimestamp());
    }

    /**
     * Security regression for the allowlist: a date row whose payload is not one of the
     * allowlisted datetime classes must be skipped without executing the payload class's
     * magic methods, and must not abort the hydration of the remaining rows.
     */
    public function testRestorePropertiesRejectsNonAllowlistedDatePayload(): void
    {
        // building the payload constructs and destructs a temporary, which flips the flag;
        // reset AFTER the payload exists so only deserialization side effects are observed
        $payload = serialize(new WebDavDeserializeCanary());
        WebDavDeserializeCanary::$fired = false;

        $asset = new Asset();
        $this->invokeRestoreProperties($asset, [
            [
                'name' => 'gadget',
                'type' => 'date',
                'data' => $payload,
                'inheritable' => 0,
            ],
            [
                'name' => 'kept',
                'type' => 'text',
                'data' => 'still here',
                'inheritable' => 0,
            ],
        ]);

        $this->assertFalse(
            WebDavDeserializeCanary::$fired,
            'magic methods of a non-allowlisted class must not run during property hydration'
        );
        $this->assertNull($asset->getProperty('gadget'), 'a non-allowlisted date payload must be skipped');
        $this->assertSame('still here', $asset->getProperty('kept'), 'other rows must still hydrate');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function invokeRestoreProperties(Asset $asset, array $rows): void
    {
        $tree = new Tree(new WebDavFolder(new Asset\Folder()));

        $method = new ReflectionMethod(Tree::class, 'restoreProperties');
        $method->invoke($tree, $asset, $rows);
    }
}

/**
 * Canary "gadget": records whether its magic methods were ever executed.
 */
class WebDavDeserializeCanary
{
    public static bool $fired = false;

    public function __wakeup(): void
    {
        self::$fired = true;
    }

    public function __destruct()
    {
        self::$fired = true;
    }
}
