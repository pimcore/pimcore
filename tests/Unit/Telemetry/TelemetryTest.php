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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Telemetry\EventSanitizer;
use Pimcore\Telemetry\Spool\TelemetrySpoolWriterInterface;
use Pimcore\Telemetry\Telemetry;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use stdClass;

// Records the events handed to the spool instead of persisting them.
final class RecordingSpoolWriter implements TelemetrySpoolWriterInterface
{
    /** @var list<array<string, mixed>> */
    public array $enqueued = [];

    public function enqueue(array $events, ?int $cap = null): void
    {
        foreach ($events as $event) {
            $this->enqueued[] = $event;
        }
    }
}

class TelemetryTest extends TestCase
{
    private const INSTANCE = 'instance-1';

    private const PRODUCT_KEY = 'product-key-1';

    public function testFlushPersistsBufferedEventsToTheSpool(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->capture('object.created', ['element_type' => 'object']);
        $this->assertSame([], $spool->enqueued, 'nothing is spooled before flush');

        $telemetry->flush();

        $this->assertCount(1, $spool->enqueued);
        $this->assertSame('object.created', $spool->enqueued[0]['event']);
        $this->assertSame($this->expectedGroupKey('prod'), $spool->enqueued[0]['groups']['instance']);
    }

    public function testFlushClearsTheBuffer(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->capture('object.created');
        $telemetry->flush();
        $telemetry->flush();

        $this->assertCount(1, $spool->enqueued);
    }

    public function testUnidentifiedInstanceSpoolsNothing(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = new Telemetry('', self::PRODUCT_KEY, $spool, new NullLogger(), $this->sanitizer());

        $this->assertFalse($telemetry->isEnabled());

        $telemetry->capture('object.created');
        $telemetry->flush();

        $this->assertSame([], $spool->enqueued);
    }

    public function testMissingProductKeyDisablesTelemetry(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = new Telemetry(self::INSTANCE, '', $spool, new NullLogger(), $this->sanitizer());

        $this->assertFalse($telemetry->isEnabled());

        $telemetry->capture('object.created');
        $telemetry->flush();

        $this->assertSame([], $spool->enqueued);
    }

    public function testObjectPropertiesNeverReachTheSpoolButScalarArraysDo(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->capture('object.created', [
            'element_type' => 'object',
            'bundles' => ['PimcoreCoreBundle', 'PimcoreDataHubBundle'], // scalar array -> kept
            'leaked_object' => new stdClass(),                          // object -> dropped
        ]);
        $telemetry->flush();

        $this->assertSame(
            ['element_type' => 'object', 'bundles' => ['PimcoreCoreBundle', 'PimcoreDataHubBundle']],
            $spool->enqueued[0]['properties']
        );
    }

    /**
     * Events and the group profile must land on the same group, or the profile would describe a
     * different thing than the events attributed to it.
     */
    public function testEventsAndTheGroupProfileShareOneKey(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool, 'prod', 'www.example.com');

        $telemetry->capture('instance.snapshot', ['core.mode' => 'prod']);
        $telemetry->groupIdentify('instance', self::INSTANCE, ['core.mode' => 'prod']);
        $telemetry->flush();

        $expected = $this->expectedGroupKey('prod', 'www.example.com');
        $this->assertSame($expected, $spool->enqueued[0]['groups']['instance']);
        $this->assertSame($expected, $spool->enqueued[1]['groupKey']);
    }

    /**
     * The whole point: one product key installed twice - staging being a restore of production - must
     * not share a group, because a group profile is last-write-wins and the two would overwrite each
     * other's snapshot.
     */
    public function testTwoDeploymentsOfOneInstallationGetDifferentGroups(): void
    {
        $prodSpool = new RecordingSpoolWriter();
        $prod = $this->telemetry($prodSpool, 'prod', 'www.example.com');
        $prod->capture('instance.snapshot');
        $prod->flush();

        $stagingSpool = new RecordingSpoolWriter();
        // same identifier, same product key - only the domain differs, as in a restored clone
        $staging = $this->telemetry($stagingSpool, 'prod', 'staging.example.com');
        $staging->capture('instance.snapshot');
        $staging->flush();

        $this->assertNotSame(
            $prodSpool->enqueued[0]['groups']['instance'],
            $stagingSpool->enqueued[0]['groups']['instance'],
            'a staging clone must not share production\'s group'
        );
        // both still resolve to the same installation, which is the prefix
        $this->assertStringStartsWith(self::INSTANCE . ':', $prodSpool->enqueued[0]['groups']['instance']);
        $this->assertStringStartsWith(self::INSTANCE . ':', $stagingSpool->enqueued[0]['groups']['instance']);
    }

    /**
     * The domain separates the deployments but must never be readable in analytics: only an HMAC of
     * it, keyed with the product key, ever leaves - so the dataset stays pseudonymous rather than
     * being attributable to a named company.
     */
    public function testTheDomainIsHashedAndNeverEmitted(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool, 'prod', 'shop.acme-gmbh.example');

        $telemetry->groupIdentify('instance', self::INSTANCE, []);
        $telemetry->flush();

        $encoded = json_encode($spool->enqueued, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('acme-gmbh', $encoded);
        $this->assertStringNotContainsString('shop.', $encoded);
        $this->assertStringContainsString($this->expectedGroupKey('prod', 'shop.acme-gmbh.example'), $encoded);
    }

    /**
     * `pimcore.general.domain` is optional config, so an empty domain is the normal case. The key stays
     * well-formed and still separates prod from dev/test/other.
     */
    public function testAnUnconfiguredDomainStillYieldsAWellFormedKey(): void
    {
        $prodSpool = new RecordingSpoolWriter();
        $prod = $this->telemetry($prodSpool, 'prod');
        $prod->capture('instance.snapshot');
        $prod->flush();

        $devSpool = new RecordingSpoolWriter();
        $dev = $this->telemetry($devSpool, 'dev');
        $dev->capture('instance.snapshot');
        $dev->flush();

        // no domain to hash, but the mode segment still separates the two
        $this->assertSame(self::INSTANCE . ':prod:unknown', $prodSpool->enqueued[0]['groups']['instance']);
        $this->assertSame(self::INSTANCE . ':dev:unknown', $devSpool->enqueued[0]['groups']['instance']);
    }

    /**
     * The default configuration: `pimcore.general.domain` unset, so there is no domain hash and the
     * mode segment is the only thing separating a staging restore from the production instance it was
     * cloned from. This is the case that justifies `staging` being a recognised mode rather than
     * collapsing into `other` with every other unrecognised environment.
     */
    public function testStagingSeparatesFromProductionWithNoDomainConfigured(): void
    {
        $prodSpool = new RecordingSpoolWriter();
        $prod = $this->telemetry($prodSpool, 'prod');
        $prod->capture('instance.snapshot');
        $prod->flush();

        $stagingSpool = new RecordingSpoolWriter();
        $staging = $this->telemetry($stagingSpool, 'staging');
        $staging->capture('instance.snapshot');
        $staging->flush();

        $this->assertSame(self::INSTANCE . ':prod:unknown', $prodSpool->enqueued[0]['groups']['instance']);
        $this->assertSame(self::INSTANCE . ':staging:unknown', $stagingSpool->enqueued[0]['groups']['instance']);
    }

    /**
     * The environment reaches the key verbatim, matching core.environment_name and the statistics
     * endpoint so all three line up. The consequence is deliberate: the key inherits whatever APP_ENV
     * holds, and every distinct value is a distinct group.
     */
    public function testTheEnvironmentReachesTheKeyVerbatim(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool, 'prod_acme-gmbh');

        $telemetry->capture('instance.snapshot');
        $telemetry->flush();

        $this->assertSame(
            self::INSTANCE . ':prod_acme-gmbh:unknown',
            $spool->enqueued[0]['groups']['instance']
        );
    }

    /**
     * Cardinality is unbounded, so two spellings of the same environment are two groups. Pinned so the
     * consequence is visible in the suite rather than discovered in analytics.
     */
    public function testEveryDistinctEnvironmentIsADistinctGroup(): void
    {
        $keys = [];
        foreach (['prod', 'Prod', 'production', 'prod '] as $environment) {
            $spool = new RecordingSpoolWriter();
            $telemetry = $this->telemetry($spool, $environment);
            $telemetry->capture('instance.snapshot');
            $telemetry->flush();
            $keys[] = $spool->enqueued[0]['groups']['instance'];
        }

        $this->assertSame($keys, array_unique($keys), 'no two spellings may collapse into one group');
    }

    /**
     * Only the instance group is owned by the class; a bundle identifying its own group type must still
     * have its key respected.
     */
    public function testAForeignGroupTypeKeepsItsOwnKey(): void
    {
        $spool = new RecordingSpoolWriter();
        $telemetry = $this->telemetry($spool);

        $telemetry->groupIdentify('customer', 'customer-42', []);
        $telemetry->flush();

        $this->assertSame('customer-42', $spool->enqueued[0]['groupKey']);
    }

    private function telemetry(
        TelemetrySpoolWriterInterface $spool,
        string $environment = 'prod',
        string $mainDomain = '',
    ): Telemetry {
        return new Telemetry(
            self::INSTANCE,
            self::PRODUCT_KEY,
            $spool,
            new NullLogger(),
            $this->sanitizer(),
            $environment,
            $mainDomain,
        );
    }

    /**
     * The composed key for the fixture instance, so the expectations below read as intent rather than
     * as a copy of the implementation.
     */
    private function expectedGroupKey(string $environment, string $mainDomain = ''): string
    {
        $hash = $mainDomain === '' ? 'unknown' : substr(hash_hmac('sha256', $mainDomain, self::PRODUCT_KEY), 0, 16);

        return self::INSTANCE . ':' . $environment . ':' . $hash;
    }

    private function sanitizer(): EventSanitizer
    {
        return new EventSanitizer(new NullLogger());
    }
}
