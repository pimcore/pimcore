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

use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Messenger\ElementDependenciesMessage;
use Pimcore\Messenger\Handler\ElementDependenciesHandler;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\Dependency;
use Pimcore\Model\Element\Service;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreModule;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * An element embedded in a classification store value is a reference like any other: it has to
 * register a dependency on the owning object and contribute its cache tag to the object's cache
 * entry. Neither walk resolves inherited values - those belong to the parent object.
 *
 * @group model.datatype.classificationstore.dependencies
 */
class DependenciesTest extends AbstractClassificationStoreTest
{
    private const STORE_NAME = 'teststore';

    private const GROUP_NAME = 'testgroup2';

    private const WYSIWYG_KEY_NAME = 'wysiwyg';

    private const IMAGE_KEY_NAME = 'image';

    private const DISABLED_KEY_NAME = 'wysiwygDisabledForDependencies';

    private const ASSET_TYPE = 'asset';

    private const OBJECT_TYPE = 'object';

    private const DEFAULT_LANGUAGE = 'default';

    private const CLASS_NAME = 'csstore';

    private const KEY_PREFIX = 'cs-dependency-';

    private ?KeyConfig $disabledKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->disabledKey?->delete();
        $this->disabledKey = null;

        TestHelper::cleanUp();

        parent::tearDown();
    }

    public function testResolveDependenciesReturnsElementsEmbeddedInAWysiwygKey(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $object = $this->createObjectWithEmbeddedAsset($asset);

        $dependencies = $this->getCsstoreFieldDefinition()->resolveDependencies($object->getCsstore());

        $this->assertContains(
            ['id' => $asset->getId(), 'type' => self::ASSET_TYPE],
            $dependencies
        );
    }

    public function testSavingWritesTheDependencyRowAndClearingRemovesItAgain(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $object = $this->createObjectWithEmbeddedAsset($asset);

        $this->saveDependenciesOf($object);
        $this->assertSame(1, $this->countDependencyRows($object->getId(), $asset->getId()));

        $this->setWysiwygValue($object, null);
        $this->saveDependenciesOf($object);
        $this->assertSame(0, $this->countDependencyRows($object->getId(), $asset->getId()));
    }

    public function testTheReferencedAssetListsTheOwningObjectAsRequiredBy(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $object = $this->createObjectWithEmbeddedAsset($asset);

        $this->saveDependenciesOf($object);

        $requiredBy = Service::getRequiredByDependenciesForFrontend(
            Dependency::getBySourceId($asset->getId(), self::ASSET_TYPE),
            null,
            null
        );

        $this->assertContains($object->getId(), array_column($requiredBy['requiredBy'], 'id'));
    }

    public function testGetCacheTagsCarriesTheEmbeddedAssetsTag(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $object = $this->createObjectWithEmbeddedAsset($asset);

        $this->assertArrayHasKey(
            Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()),
            $object->getCacheTags()
        );
    }

    /**
     * An asset referenced by an image key is reached by the same walk, through the field
     * definition of the key - the type is new to the classification store, the walk is not.
     */
    public function testAnAssetInAnImageKeyRegistersItsDependencyAndContributesItsCacheTag(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $object = $this->createCsObject(self::KEY_PREFIX . 'image');
        $object->getCsstore()->setLocalizedKeyValue(
            $this->getGroup()->getId(),
            $this->getImageKey()->getId(),
            $asset
        );
        $object->save();

        $this->assertContains(
            ['id' => $asset->getId(), 'type' => self::ASSET_TYPE],
            $this->getCsstoreFieldDefinition()->resolveDependencies($object->getCsstore())
        );
        $this->assertArrayHasKey(
            Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()),
            $object->getCacheTags()
        );

        $this->saveDependenciesOf($object);
        $this->assertSame(1, $this->countDependencyRows($object->getId(), $asset->getId()));

        $requiredBy = Service::getRequiredByDependenciesForFrontend(
            Dependency::getBySourceId($asset->getId(), self::ASSET_TYPE),
            null,
            null
        );

        $this->assertContains($object->getId(), array_column($requiredBy['requiredBy'], 'id'));
    }

    public function testSavingTheReferencedAssetEvictsTheOwningObjectsCacheEntry(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $object = $this->createObjectWithEmbeddedAsset($asset);

        $objectTag = Service::getElementCacheTag(self::OBJECT_TYPE, $object->getId());
        $withAssetTag = self::KEY_PREFIX . 'entry-with-asset-tag';
        $withoutAssetTag = self::KEY_PREFIX . 'entry-without-asset-tag';

        $cacheEnabled = Cache::isEnabled();
        Cache::enable();
        Cache::getHandler()->setHandleCli(true);

        try {
            Cache::getHandler()->removeClearedTags($object->getCacheTags());

            // the entry as it is tagged now, and the entry as it was tagged before this change -
            // the payload is the id, because an element is stored under its own tags, not the given ones
            Cache::save($object->getId(), $withAssetTag, $object->getCacheTags(), null, 9999, true);
            Cache::save($object->getId(), $withoutAssetTag, [$objectTag], null, 9999, true);
            $this->assertNotFalse(Cache::load($withAssetTag));
            $this->assertNotFalse(Cache::load($withoutAssetTag));

            $asset->save();

            $this->assertFalse(Cache::load($withAssetTag));
            $this->assertNotFalse(Cache::load($withoutAssetTag));
        } finally {
            Cache::remove($withAssetTag);
            Cache::remove($withoutAssetTag);
            Cache::getHandler()->setHandleCli(false);
            if (!$cacheEnabled) {
                Cache::disable();
            }
        }
    }

    public function testGetCacheTagsOfAChildDoesNotReadTheInheritedValuesOfItsParent(): void
    {
        $debugDataHolder = $this->grabDebugDataHolder();

        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $class->setAllowInherit(true);
        $class->save();

        try {
            $parent = $this->createObjectWithEmbeddedAsset($asset, self::KEY_PREFIX . 'parent');
            $child = $this->createCsObject(self::KEY_PREFIX . 'child', $parent->getId());
            $child = Csstore::getById($child->getId(), ['force' => true]);
            $parentCacheKey = Service::getElementCacheTag(self::OBJECT_TYPE, $parent->getId());

            // the parent is resolved through the runtime cache, so it has to be evicted for a
            // parent load to be observable as a query at all
            RuntimeCache::clear();
            $debugDataHolder->reset();
            $tags = $child->getCacheTags();
            $queries = $this->grabClassificationStoreQueries($debugDataHolder);

            $this->assertSame(
                0,
                count($queries),
                'queries attributable to the classification store walk: ' . implode(', ', $queries)
            );
            $this->assertFalse(
                RuntimeCache::isRegistered($parentCacheKey),
                'the walk must not load the parent object'
            );
            $this->assertArrayNotHasKey(Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()), $tags);

            // precondition: resolving the inherited values is what issues the queries counted
            // above, so the pattern and the eviction together have to catch it
            RuntimeCache::clear();
            $debugDataHolder->reset();
            $this->assertNotSame([], $child->getCsstore()->getItems());
            $this->assertNotSame(
                [],
                $this->grabClassificationStoreQueries($debugDataHolder),
                'precondition: reading the inherited values loads the parent'
            );
            $this->assertTrue(
                RuntimeCache::isRegistered($parentCacheKey),
                'precondition: reading the inherited values loads the parent object'
            );

            // the parent owns the value, so it owns the tag
            $this->assertArrayHasKey(
                Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()),
                $parent->getCacheTags()
            );
        } finally {
            $class->setAllowInherit(false);
            $class->save();
        }
    }

    public function testOnlyActiveGroupsAreWalkedAndDisabledKeysAreIncluded(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $groupId = $this->getGroup()->getId();
        $fieldDefinition = $this->getCsstoreFieldDefinition();

        $inactiveGroup = $this->buildStore(
            [$groupId => [$this->getWysiwygKey()->getId() => $this->wysiwygItemFor($asset)]],
            []
        );
        $this->assertSame([], $fieldDefinition->resolveDependencies($inactiveGroup));
        $this->assertSame([], $fieldDefinition->getCacheTags($inactiveGroup));

        $disabledKey = $this->buildStore(
            [$groupId => [$this->createDisabledWysiwygKey()->getId() => $this->wysiwygItemFor($asset)]],
            [$groupId => true]
        );
        $this->assertContains(
            ['id' => $asset->getId(), 'type' => self::ASSET_TYPE],
            $fieldDefinition->resolveDependencies($disabledKey)
        );
        $this->assertArrayHasKey(
            Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()),
            $fieldDefinition->getCacheTags($disabledKey)
        );
    }

    public function testAnUnresolvableKeyIsSkipped(): void
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $groupId = $this->getGroup()->getId();
        $fieldDefinition = $this->getCsstoreFieldDefinition();

        // PHP_INT_MAX has no row in classificationstore_keys, so its key configuration cannot be resolved
        $store = $this->buildStore(
            [$groupId => [
                $this->getWysiwygKey()->getId() => $this->wysiwygItemFor($asset),
                PHP_INT_MAX => $this->wysiwygItemFor($asset),
            ]],
            [$groupId => true]
        );

        $this->assertContains(
            ['id' => $asset->getId(), 'type' => self::ASSET_TYPE],
            $fieldDefinition->resolveDependencies($store)
        );
        $this->assertArrayHasKey(
            Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()),
            $fieldDefinition->getCacheTags($store)
        );
    }

    private function createObjectWithEmbeddedAsset(Asset $asset, string $key = self::KEY_PREFIX . 'object'): Csstore
    {
        $object = $this->createCsObject($key);
        $this->setWysiwygValue($object, $this->wysiwygMarkupFor($asset));

        return $object;
    }

    private function createCsObject(string $key, int $parentId = 1): Csstore
    {
        $object = new Csstore();
        $object->setParentId($parentId);
        $object->setKey($key);
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    private function setWysiwygValue(Csstore $object, ?string $value): void
    {
        $object->getCsstore()->setLocalizedKeyValue(
            $this->getGroup()->getId(),
            $this->getWysiwygKey()->getId(),
            $value
        );
        $object->save();
    }

    private function buildStore(array $items, array $activeGroups): Classificationstore
    {
        $store = new Classificationstore();
        $store->setItems($items);
        $store->setActiveGroups($activeGroups);

        return $store;
    }

    private function wysiwygItemFor(Asset $asset): array
    {
        return [self::DEFAULT_LANGUAGE => $this->wysiwygMarkupFor($asset)];
    }

    private function wysiwygMarkupFor(Asset $asset): string
    {
        return '<a href="' . $asset->getFullPath() . '" pimcore_id="' . $asset->getId()
            . '" pimcore_type="' . self::ASSET_TYPE . '">the asset</a>';
    }

    private function createDisabledWysiwygKey(): KeyConfig
    {
        $definition = new Wysiwyg();
        $definition->setName(self::DISABLED_KEY_NAME);

        $this->disabledKey = new KeyConfig();
        $this->disabledKey->setStoreId($this->getStore()->getId());
        $this->disabledKey->setName(self::DISABLED_KEY_NAME);
        $this->disabledKey->setType(self::WYSIWYG_KEY_NAME);
        $this->disabledKey->setDefinition(json_encode($definition));
        $this->disabledKey->setEnabled(false);
        $this->disabledKey->save();

        return $this->disabledKey;
    }

    private function saveDependenciesOf(Csstore $object): void
    {
        (new ElementDependenciesHandler(new NullLogger()))(
            new ElementDependenciesMessage(self::OBJECT_TYPE, $object->getId())
        );
    }

    private function countDependencyRows(int $objectId, int $assetId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT count(*) FROM dependencies WHERE sourceType = ? AND sourceId = ?'
            . ' AND targetType = ? AND targetId = ?',
            [self::OBJECT_TYPE, $objectId, self::ASSET_TYPE, $assetId]
        );
    }

    private function getStore(): StoreConfig
    {
        return StoreConfig::getByName(self::STORE_NAME);
    }

    private function getGroup(): GroupConfig
    {
        return GroupConfig::getByName(self::GROUP_NAME, $this->getStore()->getId());
    }

    private function getWysiwygKey(): KeyConfig
    {
        return KeyConfig::getByName(self::WYSIWYG_KEY_NAME, $this->getStore()->getId());
    }

    private function getImageKey(): KeyConfig
    {
        return KeyConfig::getByName(self::IMAGE_KEY_NAME, $this->getStore()->getId());
    }

    private function getCsstoreFieldDefinition(): ClassificationstoreField
    {
        return ClassDefinition::getByName(self::CLASS_NAME)->getFieldDefinition(self::CLASS_NAME);
    }

    private function grabDebugDataHolder(): DebugDataHolder
    {
        $container = $this->getModule('\\' . PimcoreModule::class)->_getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        return $container->get('doctrine.debug_data_holder');
    }

    /**
     * @return string[]
     */
    private function grabClassificationStoreQueries(DebugDataHolder $debugDataHolder): array
    {
        $pattern = '/object_classificationstore_|classificationstore_keys|object_store_|FROM `?objects`?\b/i';
        $queries = [];

        foreach ($debugDataHolder->getData() as $connectionQueries) {
            foreach ($connectionQueries as $query) {
                $sql = preg_replace('/\s+/', ' ', (string)$query['sql']);
                if (preg_match($pattern, $sql)) {
                    $queries[] = $sql;
                }
            }
        }

        return $queries;
    }
}
