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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool;

/**
 * Loading localized values from the database must not call dynamic options providers once per
 * language and value: in admin mode every call enriches the field definition, and slow providers
 * then dominate object loads (PEES-1836).
 *
 * @group model.datatype.localizedfield
 */
class LocalizedfieldLoadEnrichmentTest extends ModelTestCase
{
    private const CLASS_NAME = 'LfLoadEnrichment';

    private bool $adminModeBackup = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminModeBackup = Pimcore::inAdmin();
        $this->createClass();
    }

    protected function tearDown(): void
    {
        $this->adminModeBackup ? Pimcore::setAdminMode() : Pimcore::unsetAdminMode();
        ClassDefinition::getByName(self::CLASS_NAME)?->delete();

        parent::tearDown();
    }

    public function testLoadingEnrichesEachLocalizedFieldOncePerObject(): void
    {
        $languages = Tool::getValidLanguages();
        $this->assertGreaterThan(1, count($languages), 'test needs at least two languages');

        $object = $this->createObject($languages);

        Pimcore::setAdminMode();
        RuntimeCache::clear();
        CountingOptionsProvider::$calls = 0;

        $loaded = Concrete::getById($object->getId(), ['force' => true]);

        $this->assertInstanceOf(Concrete::class, $loaded);
        $this->assertSame(
            1,
            CountingOptionsProvider::$calls,
            'the options provider must run once per field while loading, not once per language'
        );
        $this->assertSame('b', $loaded->get('status', $languages[1]));
    }

    /**
     * Localizedfield\Dao::save() also calls setLocalizedValue() with markFieldAsDirty = false and runs
     * preSetData() with the definition it gets. The per-object cache must therefore end with the load,
     * otherwise later calls use definitions that may have been replaced in the meantime.
     */
    public function testLoadCacheEndsWithTheLoad(): void
    {
        $languages = Tool::getValidLanguages();
        $object = $this->createObject($languages);

        Pimcore::setAdminMode();
        RuntimeCache::clear();
        $loaded = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $loaded);

        $localizedfield = $loaded->getObjectVar('localizedfields');
        $this->assertInstanceOf(DataObject\Localizedfield::class, $localizedfield);

        CountingOptionsProvider::$calls = 0;
        $localizedfield->setLocalizedValue('status', 'a', $languages[0], false);
        $localizedfield->setLocalizedValue('status', 'b', $languages[1], false);

        $this->assertSame(
            2,
            CountingOptionsProvider::$calls,
            'after the load, every call resolves the current (enriched) field definition again'
        );
    }

    public function testSettingValuesStillEnrichesOnEveryCall(): void
    {
        $languages = Tool::getValidLanguages();
        $object = $this->createObject($languages);

        Pimcore::setAdminMode();
        CountingOptionsProvider::$calls = 0;

        $object->set('status', 'a', $languages[0]);
        $object->set('status', 'b', $languages[1]);

        $this->assertSame(
            2,
            CountingOptionsProvider::$calls,
            'setting values from outside keeps enriching per call (options are used for validation)'
        );
    }

    /**
     * @param string[] $languages
     */
    private function createObject(array $languages): Concrete
    {
        $className = '\\Pimcore\\Model\\DataObject\\' . self::CLASS_NAME;

        /** @var Concrete $object */
        $object = new $className();
        $object->setParent(DataObject::getById(1));
        $object->setKey(uniqid('lf-load-enrichment-', true));
        foreach ($languages as $index => $language) {
            $object->set('status', $index % 2 === 0 ? 'a' : 'b', $language);
            $object->set('title', 'Title ' . $language, $language);
        }
        $object->save();

        return $object;
    }

    private function createClass(): void
    {
        $status = new Select();
        $status->setName('status');
        $status->setTitle('status');
        $status->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $status->setOptionsProviderClass(CountingOptionsProvider::class);

        $title = new Data\Input();
        $title->setName('title');
        $title->setTitle('title');

        $localizedFields = new Localizedfields();
        $localizedFields->setName('localizedfields');
        $localizedFields->setChildren([$title, $status]);

        $panel = new Panel();
        $panel->setName('pimcore_root');
        $panel->setChildren([$localizedFields]);

        $class = ClassDefinition::getByName(self::CLASS_NAME) ?? new ClassDefinition();
        $class->setName(self::CLASS_NAME);
        $class->setLayoutDefinitions($panel);
        $class->save();
    }
}

/**
 * @internal
 */
final class CountingOptionsProvider implements SelectOptionsProviderInterface
{
    public static int $calls = 0;

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        self::$calls++;

        return [
            ['key' => 'A', 'value' => 'a'],
            ['key' => 'B', 'value' => 'b'],
        ];
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return false;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): string|array|null
    {
        return null;
    }
}
