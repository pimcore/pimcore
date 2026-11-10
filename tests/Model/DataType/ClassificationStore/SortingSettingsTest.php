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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Model\Helper\QueryParams;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * @group model.classificationstore.identifier
 */
final class SortingSettingsTest extends ModelTestCase
{
    use IdentifiedRowsFixtureTrait;

    private const FIELD = 'csstore';

    private const TABLE_GROUPS = 'classificationstore_groups';

    private const TABLE_KEYS = 'classificationstore_keys';

    protected function tearDown(): void
    {
        $this->removeIdentifiedRows();

        parent::tearDown();
    }

    public function testTheIntegerTokenIsUnchanged(): void
    {
        $expected = [
            'orderKey' => '~classificationstore~csstore~12-34',
            'fieldname' => self::FIELD,
            'groupId' => 12,
            'keyId' => 34,
            'order' => 'DESC',
            'isFeature' => 1,
        ];

        $params = $this->params('~classificationstore~csstore~12-34', 'DESC');

        $this->assertSame($expected, QueryParams::extractSortingSettings($params));
        $this->assertSame($expected, QueryParams::extractSortingSettings($params, 7));
    }

    public function testAPlainPropertyIsUnchanged(): void
    {
        $this->assertSame(
            ['orderKey' => 'key', 'order' => 'ASC'],
            QueryParams::extractSortingSettings($this->params('key', 'ASC'))
        );
    }

    /**
     * The two early returns the identifier branch was inserted between: a property of fewer than
     * four segments, and a four segment one whose pair does not split on a hyphen, are answered with
     * the order key alone, with and without a store scope.
     */
    public function testAShortOrUnpairedPropertyIsUnchanged(): void
    {
        $short = '~classificationstore~csstore';
        $unpaired = '~classificationstore~csstore~nopair';

        $this->assertSame(
            ['orderKey' => $short, 'order' => 'ASC'],
            QueryParams::extractSortingSettings($this->params($short, 'ASC'))
        );
        $this->assertSame(
            ['orderKey' => $unpaired, 'order' => 'DESC'],
            QueryParams::extractSortingSettings($this->params($unpaired, 'DESC'))
        );
        $this->assertSame(
            ['orderKey' => $unpaired, 'order' => 'DESC'],
            QueryParams::extractSortingSettings($this->params($unpaired, 'DESC'), 7)
        );
    }

    public function testTheIdentifierTokenResolvesWithAStoreScope(): void
    {
        $storeId = $this->seedIdentifiedStore();
        $groupId = $this->seedIdentifiedRow(self::TABLE_GROUPS, $storeId, 'Group', 'the-group');
        $keyId = $this->seedIdentifiedRow(self::TABLE_KEYS, $storeId, 'Key', 'the-key');
        $token = '~classificationstore~csstore~the-group~the-key';

        $this->assertSame(
            [
                'orderKey' => $token,
                'fieldname' => self::FIELD,
                'groupId' => $groupId,
                'keyId' => $keyId,
                'order' => 'ASC',
                'isFeature' => 1,
            ],
            QueryParams::extractSortingSettings($this->params($token, 'ASC'), $storeId)
        );
    }

    public function testTheIdentifierTokenWithoutAStoreScopeReturnsTheIdentifiers(): void
    {
        $token = '~classificationstore~csstore~the-group~the-key';

        $result = QueryParams::extractSortingSettings($this->params($token, 'ASC'));

        $this->assertSame(
            [
                'orderKey' => $token,
                'order' => 'ASC',
                'fieldname' => self::FIELD,
                'groupIdentifier' => 'the-group',
                'keyIdentifier' => 'the-key',
            ],
            $result
        );
        $this->assertArrayNotHasKey('isFeature', $result);
        $this->assertArrayNotHasKey('groupId', $result);
    }

    public function testAnIdentifierTokenThatDoesNotResolveYieldsNoFeature(): void
    {
        $storeId = $this->seedIdentifiedStore();
        $token = '~classificationstore~csstore~missing-group~missing-key';

        $this->assertSame(
            ['orderKey' => $token, 'order' => 'ASC'],
            QueryParams::extractSortingSettings($this->params($token, 'ASC'), $storeId)
        );
    }

    /**
     * The fixture seeds group and key rows and deletes them again, which leaves a gap below the
     * AUTO_INCREMENT of those tables. It must not close that gap: an id given out a second time is
     * answered for by the definition of the configuration that held it before, because the memo in
     * {@see \Pimcore\Model\DataObject\Classificationstore\Service::$definitionsCache} is keyed by key
     * id and survives both a key save and a key delete. The next test class to create a key then
     * reads whatever the previous holder of that id was - and when that was a relation key on an EAV
     * store, writing a value to it is refused.
     */
    public function testTheFixtureNeverHandsAConfigurationIdOutTwice(): void
    {
        $firstStoreId = $this->seedIdentifiedStore();
        $firstGroupId = $this->seedIdentifiedRow(self::TABLE_GROUPS, $firstStoreId, 'Group', 'first-group');
        $firstKeyId = $this->seedIdentifiedRow(self::TABLE_KEYS, $firstStoreId, 'Key', 'first-key');

        $this->removeIdentifiedRows();

        $secondStoreId = $this->seedIdentifiedStore();

        $this->assertGreaterThan(
            $firstGroupId,
            $this->seedIdentifiedRow(self::TABLE_GROUPS, $secondStoreId, 'Group', 'second-group'),
            'a group id that was handed out already must not be handed out again'
        );
        $this->assertGreaterThan(
            $firstKeyId,
            $this->seedIdentifiedRow(self::TABLE_KEYS, $secondStoreId, 'Key', 'second-key'),
            'a key id that was handed out already must not be handed out again'
        );
    }

    private function params(string $property, string $direction): array
    {
        return ['sort' => json_encode([['property' => $property, 'direction' => $direction]])];
    }
}
