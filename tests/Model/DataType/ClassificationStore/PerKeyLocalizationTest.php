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

use Pimcore\Bundle\CoreBundle\Migrations\Version20261105100000;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\User;
use Pimcore\Tool;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * The per key localization flag: the column it lives in, that NULL, true and false survive every
 * hydration path, that a key built with `new` does not clear it, and the four paths that enumerate
 * languages following the key rather than the class definition field.
 *
 * @group model.datatype.classificationstore
 */
class PerKeyLocalizationTest extends AbstractBatchedRelationTest
{
    private const TABLE = KeyConfig\Dao::TABLE_NAME_KEYS;

    private const COLUMN = 'localized';

    private const FIELDNAME = 'csstore';

    private const DEFAULT_LANGUAGE = 'default';

    public function testTheColumnIsNullableWithANullDefault(): void
    {
        $columns = Db::get()->createSchemaManager()->listTableColumns(self::TABLE);

        $this->assertArrayHasKey(self::COLUMN, $columns, self::TABLE . ' must carry ' . self::COLUMN);
        $this->assertFalse($columns[self::COLUMN]->getNotnull(), self::COLUMN . ' must be nullable');

        // the NULL default, read as the behaviour it is: a key created without a flag inherits
        $this->assertNull($this->readFlag($this->createKey('nulldefault')));

        $this->assertContains(
            self::COLUMN,
            (new KeyConfig())->getDao()->getValidTableColumns(self::TABLE),
            'The valid table columns cache must know the new column'
        );
    }

    public function testTheMigrationAddsTheColumnAndTakesItBack(): void
    {
        $db = Db::get();
        $schemaManager = $db->createSchemaManager();
        $copy = self::TABLE . '_premigration';

        $db->executeStatement('DROP TABLE IF EXISTS `' . $copy . '`');
        $db->executeStatement('CREATE TABLE `' . $copy . '` LIKE `' . self::TABLE . '`');

        try {
            // the table as a database created from the previous dump carries it
            $previous = $schemaManager->introspectSchema();
            $previous->getTable(self::TABLE)->dropColumn(self::COLUMN);
            $db->executeStatement('ALTER TABLE `' . $copy . '` DROP COLUMN `' . self::COLUMN . '`');

            $up = new Version20261105100000($db, new NullLogger());
            $up->up($previous);
            $this->executeOnCopy($up, $copy);

            $columns = $schemaManager->listTableColumns($copy);
            $this->assertArrayHasKey(self::COLUMN, $columns, 'up() must add ' . self::COLUMN);
            $this->assertFalse($columns[self::COLUMN]->getNotnull(), self::COLUMN . ' must be nullable');

            $db->executeStatement('INSERT INTO `' . $copy . '` (`name`) VALUES (?)', ['defaultprobe']);
            $this->assertNull(
                $db->fetchOne('SELECT `' . self::COLUMN . '` FROM `' . $copy . '` WHERE `name` = ?', ['defaultprobe']),
                self::COLUMN . ' must default to NULL'
            );

            $down = new Version20261105100000($db, new NullLogger());
            $down->down($schemaManager->introspectSchema());
            $this->executeOnCopy($down, $copy);

            $columns = $schemaManager->listTableColumns($copy);
            $this->assertArrayNotHasKey(self::COLUMN, $columns, 'down() must take back ' . self::COLUMN);
            $this->assertArrayHasKey('enabled', $columns, 'down() must leave enabled alone');
            $this->assertArrayHasKey('identifier', $columns, 'down() must leave the identifier alone');
        } finally {
            $db->executeStatement('DROP TABLE IF EXISTS `' . $copy . '`');
        }
    }

    public function testTheInstallerDumpCarriesTheColumn(): void
    {
        $dump = file_get_contents(dirname(__DIR__, 4) . '/bundles/InstallBundle/dump/install.sql');
        preg_match('/CREATE TABLE `' . self::TABLE . '` \((.*?)\n\)/s', (string) $dump, $matches);

        $this->assertNotEmpty($matches, 'the dump must create ' . self::TABLE);
        $this->assertStringContainsString('`localized` TINYINT(1) NULL DEFAULT NULL', $matches[1]);
    }

    /**
     * @return array<string, array{0: bool|null}>
     */
    public static function flagProvider(): array
    {
        return [
            'inherit' => [null],
            'localized' => [true],
            'not localized' => [false],
        ];
    }

    /**
     * @dataProvider flagProvider
     */
    public function testTheFlagSurvivesEverySingleHydrationPath(?bool $flag): void
    {
        $keyConfig = $this->createKeyWithFlag('hydration', $flag);
        $storeId = $this->getStoreId();

        $this->assertSame($flag, KeyConfig::getById($keyConfig->getId(), true)?->getLocalized(), 'getById()');
        $this->assertSame($flag, KeyConfig::getByName($keyConfig->getName(), $storeId, true)?->getLocalized(), 'getByName()');
        $this->assertSame(
            $flag,
            KeyConfig::getByIdentifier((string) $keyConfig->getIdentifier(), $storeId)?->getLocalized(),
            'getByIdentifier()'
        );

        $listing = new KeyConfig\Listing();
        $listing->setCondition('id = ?', [$keyConfig->getId()]);
        $this->assertSame($flag, $listing->load()[0]->getLocalized(), 'Listing\Dao::load()');

        DefinitionCache::clear(null);
        DefinitionCache::load([$keyConfig->getId()]);
        $this->assertSame($flag, DefinitionCache::get($keyConfig->getId())?->getLocalized(), 'DefinitionCache::loadKeys()');
    }

    public function testAKeyBuiltWithNewDoesNotClearAStoredFlag(): void
    {
        $keyConfig = $this->createKeyWithFlag('untouched', true);

        $identifying = new KeyConfig();
        $identifying->setId($keyConfig->getId());
        $identifying->setStoreId($keyConfig->getStoreId());
        $identifying->setName($keyConfig->getName());
        $identifying->setType($keyConfig->getType());
        $identifying->setEnabled(true);
        $identifying->setDefinition($keyConfig->getDefinition());
        $identifying->save();

        $this->assertSame(1, (int) $this->readFlag($keyConfig), 'a save that never decided must leave the flag');
    }

    public function testAnExplicitNullReturnsTheKeyToInheriting(): void
    {
        $keyConfig = $this->createKeyWithFlag('cleared', true);

        $loaded = KeyConfig::getById($keyConfig->getId(), true);
        $loaded->setLocalized(null);
        $loaded->save();

        $this->assertNull($this->readFlag($keyConfig), 'setLocalized(null) must write NULL');
        $this->assertNull(KeyConfig::getById($keyConfig->getId(), true)?->getLocalized());
    }

    /**
     * The calculated value pass of the non-localized field: the flagged key is answered for every
     * valid language, the one that inherits only for `default`.
     */
    public function testTheEditModePayloadEnumeratesLanguagesPerKeyInANonLocalizedField(): void
    {
        $group = $this->createGroup('editmode_plain');
        $localizedKey = $this->createKeyWithFlag('editmode_plain_localized', true, new CalculatedValue());
        $inheritingKey = $this->createKeyWithFlag('editmode_plain_inherit', null, new CalculatedValue());
        $this->assignKey($group, $localizedKey, 0);
        $this->assignKey($group, $inheritingKey, 1);

        $object = $this->createObject(Csstore::class, 'editmode-plain', [$group->getId() => true]);

        $data = $this->getEditModePayload($object)['data'];

        foreach ($this->getValidLanguages() as $language) {
            $this->assertArrayHasKey(
                $localizedKey->getId(),
                $data[$language][$group->getId()],
                'the flagged key is part of the payload of language ' . $language
            );
        }

        $this->assertArrayHasKey($inheritingKey->getId(), $data[self::DEFAULT_LANGUAGE][$group->getId()]);
        foreach (Tool::getValidLanguages() as $language) {
            $this->assertArrayNotHasKey(
                $inheritingKey->getId(),
                $data[$language][$group->getId()],
                'a key that inherits a non-localized field appears under default only'
            );
        }
    }

    /**
     * The same pass in a localized field: a key flagged false is answered for `default` only.
     */
    public function testTheEditModePayloadKeepsAKeyFlaggedFalseOnDefaultOnly(): void
    {
        $group = $this->createGroup('editmode_localized');
        $plainKey = $this->createKeyWithFlag('editmode_localized_plain', false, new CalculatedValue());
        $inheritingKey = $this->createKeyWithFlag('editmode_localized_inherit', null, new CalculatedValue());
        $this->assignKey($group, $plainKey, 0);
        $this->assignKey($group, $inheritingKey, 1);

        $object = $this->createObject(Csstorelocalized::class, 'editmode-localized', [$group->getId() => true]);

        $data = $this->getEditModePayload($object)['data'];

        $this->assertArrayHasKey($plainKey->getId(), $data[self::DEFAULT_LANGUAGE][$group->getId()]);
        foreach (Tool::getValidLanguages() as $language) {
            $this->assertArrayNotHasKey(
                $plainKey->getId(),
                $data[$language][$group->getId()],
                'a key flagged false appears under default only'
            );
            $this->assertArrayHasKey(
                $inheritingKey->getId(),
                $data[$language][$group->getId()],
                'while a key that inherits the localized field keeps language ' . $language
            );
        }
    }

    /**
     * The inheritance walk, which reports the parent per language, follows the key as well.
     */
    public function testTheInheritanceWalkEnumeratesLanguagesPerKey(): void
    {
        $group = $this->createGroup('inheritance_languages');
        $plainKey = $this->createKeyWithFlag('inheritance_plain', false);
        $inheritingKey = $this->createKeyWithFlag('inheritance_inherit', null);
        $this->assignKey($group, $plainKey, 0);
        $this->assignKey($group, $inheritingKey, 1);

        $parent = $this->createObject(Csstorelocalized::class, 'inheritance-parent', [$group->getId() => true]);
        $parent->getCsstore()->setLocalizedKeyValue($group->getId(), $plainKey->getId(), 'parent', self::DEFAULT_LANGUAGE);
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey('inheritance-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$group->getId() => true]);
        $child->save();

        $metaData = $this->getEditModePayload(Csstorelocalized::getById($child->getId(), ['force' => true]))['metaData'];

        $this->assertArrayHasKey($plainKey->getId(), $metaData[self::DEFAULT_LANGUAGE][$group->getId()]);
        foreach (Tool::getValidLanguages() as $language) {
            $this->assertArrayNotHasKey(
                $plainKey->getId(),
                $metaData[$language][$group->getId()] ?? [],
                'a key flagged false is walked for default only'
            );
            $this->assertArrayHasKey(
                $inheritingKey->getId(),
                $metaData[$language][$group->getId()],
                'while a key that inherits the localized field is walked for language ' . $language
            );
        }
    }

    public function testTheMandatoryCheckValidatesAFlaggedKeyInEveryLanguageOfANonLocalizedField(): void
    {
        $group = $this->createGroup('mandatory_plain');
        $keyConfig = $this->createKeyWithFlag('mandatory_plain', true);
        $this->assignKey($group, $keyConfig, 0, true);

        $object = $this->buildObject(Csstore::class, 'mandatory-plain', [$group->getId() => true]);

        $messages = $this->grabValidationMessages($object);

        $this->assertCount(count($this->getValidLanguages()), $messages);
        foreach ($this->getValidLanguages() as $language) {
            $this->assertStringContainsString('(' . $language . ')', implode(' | ', $messages));
        }
    }

    public function testTheMandatoryCheckValidatesAKeyFlaggedFalseUnderDefaultOnly(): void
    {
        $group = $this->createGroup('mandatory_localized');
        $keyConfig = $this->createKeyWithFlag('mandatory_localized', false);
        $this->assignKey($group, $keyConfig, 0, true);

        $object = $this->buildObject(Csstorelocalized::class, 'mandatory-localized', [$group->getId() => true]);

        $messages = $this->grabValidationMessages($object);

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('(' . self::DEFAULT_LANGUAGE . ')', $messages[0]);
    }

    public function testTheGridReadFollowsTheKey(): void
    {
        $group = $this->createGroup('grid');
        $localizedKey = $this->createKeyWithFlag('grid_localized', true);
        $inheritingKey = $this->createKeyWithFlag('grid_inherit', null);
        $this->assignKey($group, $localizedKey, 0);
        $this->assignKey($group, $inheritingKey, 1);

        $object = $this->createObject(Csstore::class, 'grid', [$group->getId() => true]);
        $store = $object->getCsstore();
        $store->setLocalizedKeyValue($group->getId(), $localizedKey->getId(), 'localized default', self::DEFAULT_LANGUAGE);
        $store->setLocalizedKeyValue($group->getId(), $localizedKey->getId(), 'localized en', 'en');
        $store->setLocalizedKeyValue($group->getId(), $inheritingKey->getId(), 'inherit default', self::DEFAULT_LANGUAGE);
        $store->setLocalizedKeyValue($group->getId(), $inheritingKey->getId(), 'inherit en', 'en');
        $object->save();

        $object = Csstore::getById($object->getId(), ['force' => true]);

        $this->assertSame(
            'localized en',
            $this->readGridValue($object, $group, $localizedKey),
            'the requested language answers for a key flagged localized'
        );
        $this->assertSame(
            'inherit default',
            $this->readGridValue($object, $group, $inheritingKey),
            'a key that inherits a non-localized field still answers with the default language'
        );
    }

    /**
     * The per language view and edit restriction of a user follows the key as well: a field that is
     * not localized while one key of its store is answers that key per language, so the languages
     * the user may not see or edit have to reach the editor with it. Before the gate followed the
     * key, permissionView and permissionEdit stayed unset for this field and the editor offered
     * every language of the flagged key to a restricted user.
     */
    public function testTheLayoutPermissionsFollowTheKeyInANonLocalizedField(): void
    {
        $group = $this->createGroup('permissions');
        $keyConfig = $this->createKeyWithFlag('permissions', null);
        $this->assignKey($group, $keyConfig, 0);

        $object = $this->createObject(Csstore::class, 'permissions', [$group->getId() => true]);
        $user = $this->createUserWithLanguagePermissions($object, 'en,de', 'en');

        try {
            // no key of the store is kept per language yet, so the field is restricted by nothing
            $layout = $this->enrichFieldDefinition($object, $user);
            $this->assertFalse(
                isset($layout->permissionView),
                'a field holding no localized key must not be restricted per language'
            );
            $this->assertFalse(isset($layout->permissionEdit));

            $keyConfig->setLocalized(true);
            $keyConfig->save();
            DefinitionCache::clear(null);

            $layout = $this->enrichFieldDefinition($object, $user);
            $this->assertSame(['en', 'de'], $layout->getPermissionView());
            $this->assertSame(['en'], $layout->getPermissionEdit());
        } finally {
            $user->delete();
        }
    }

    /**
     * The same gate, through the shape grid configuration uses: it cannot hand over a Concrete
     * object - field enrichment needs one - and passes the permission subject in the context
     * instead. The grid has to restrict the languages of a flagged key exactly as the editor does.
     */
    public function testTheGridConfigurationPermissionsFollowTheKeyInANonLocalizedField(): void
    {
        $group = $this->createGroup('gridpermissions');
        $keyConfig = $this->createKeyWithFlag('gridpermissions', true);
        $this->assignKey($group, $keyConfig, 0);

        $object = $this->createObject(Csstore::class, 'grid-permissions', [$group->getId() => true]);
        $user = $this->createUserWithLanguagePermissions($object, 'en,de', 'en');

        try {
            $layout = $this->enrichFieldDefinition($object, $user, true);

            $this->assertSame(['en', 'de'], $layout->getPermissionView());
            $this->assertSame(['en'], $layout->getPermissionEdit());
        } finally {
            $user->delete();
        }
    }

    /**
     * One fixture, both predictors: the payload answers a key with the languages it is kept in, and
     * the save accepts exactly those. A value stored in a language the key lost is neither answered
     * nor rewritten - it is only left alone, which is what {@see GeneralTest} asserts.
     */
    public function testTheEditModePayloadAndTheSaveAgreeOnTheLanguagesOfAKey(): void
    {
        $group = $this->createGroup('agreement');
        $localizedKey = $this->createKeyWithFlag('agreement_localized', true);
        $plainKey = $this->createKeyWithFlag('agreement_plain', false);
        $this->assignKey($group, $localizedKey, 0);
        $this->assignKey($group, $plainKey, 1);

        $groupId = $group->getId();
        $object = $this->createObject(Csstorelocalized::class, 'agreement', [$groupId => true]);

        // written through the model, which no enumeration passes through, so that both keys hold a
        // value in every language before either path is asked about them
        $store = $object->getCsstore();
        foreach ([$localizedKey, $plainKey] as $keyConfig) {
            foreach ($this->getValidLanguages() as $language) {
                $store->setLocalizedKeyValue($groupId, $keyConfig->getId(), 'stored ' . $language, $language);
            }
        }
        $object->save();

        $object = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $answered = [];
        foreach ($this->getEditModePayload($object)['data'] as $language => $groups) {
            foreach (array_keys($groups[$groupId] ?? []) as $keyId) {
                $answered[$keyId][] = $language;
            }
        }

        $saved = $object->getClass()->getFieldDefinition(self::FIELDNAME)->getDataFromEditmode(
            $this->buildEditModeData($groupId, [$localizedKey, $plainKey]),
            $object
        );

        $written = [];
        foreach ([$localizedKey, $plainKey] as $keyConfig) {
            foreach ($this->getValidLanguages() as $language) {
                $value = $saved->getLocalizedKeyValue($groupId, $keyConfig->getId(), $language, true, true);
                if ($value === 'posted ' . $language) {
                    $written[$keyConfig->getId()][] = $language;
                }
            }
        }

        $expected = [
            $localizedKey->getId() => $this->getValidLanguages(),
            $plainKey->getId() => [self::DEFAULT_LANGUAGE],
        ];

        $this->assertSame(
            $this->normaliseLanguageSets($expected),
            $this->normaliseLanguageSets($answered),
            'the payload answers each key with the languages that key is kept in'
        );
        $this->assertSame(
            $this->normaliseLanguageSets($answered),
            $this->normaliseLanguageSets($written),
            'the save must accept exactly the languages the payload answered'
        );
    }

    /**
     * The upgrade window, for the hydration paths and for them alone: they read a row of a table
     * that does not carry the column as a key that decides nothing, rather than failing on it. The
     * probe of `LocalizationResolver::hasLocalizedKey()` is deliberately not part of that claim -
     * it names the column in its `WHERE`, as `KeyConfig\Dao::getByIdentifier()` names `identifier`,
     * so it wants the migration the way every query of a new column does.
     */
    public function testTheHydrationPathsSurviveATableWithoutTheColumn(): void
    {
        $keyConfig = $this->createKeyWithFlag('premigration', true);

        try {
            $this->dropTheColumn();

            DefinitionCache::clear(null);

            $this->assertNull(KeyConfig::getById($keyConfig->getId(), true)?->getLocalized(), 'getById()');

            $listing = new KeyConfig\Listing();
            $listing->setCondition('id = ?', [$keyConfig->getId()]);
            $this->assertNull($listing->load()[0]->getLocalized(), 'Listing\Dao::load()');

            DefinitionCache::clear(null);
            DefinitionCache::load([$keyConfig->getId()]);
            $this->assertNull(
                DefinitionCache::get($keyConfig->getId())?->getLocalized(),
                'DefinitionCache::loadKeys()'
            );
        } finally {
            $this->restoreTheColumn();
            DefinitionCache::clear(null);
        }
    }

    /**
     * The upgrade case: with no key flagged, both fixtures enumerate exactly what they do today.
     */
    public function testAKeyWithoutAFlagKeepsTodaysLanguages(): void
    {
        $group = $this->createGroup('unflagged');
        $keyConfig = $this->createKeyWithFlag('unflagged', null, new CalculatedValue());
        $this->assignKey($group, $keyConfig, 0);

        $plain = $this->createObject(Csstore::class, 'unflagged-plain', [$group->getId() => true]);
        $plainData = $this->getEditModePayload($plain)['data'];

        $this->assertSame(
            [self::DEFAULT_LANGUAGE],
            array_keys($plainData),
            'the non-localized field answers default only'
        );

        $localized = $this->createObject(Csstorelocalized::class, 'unflagged-localized', [$group->getId() => true]);
        $localizedData = $this->getEditModePayload($localized)['data'];

        $this->assertSame(
            $this->getValidLanguages(),
            array_keys($localizedData),
            'the localized field answers every valid language'
        );
    }

    /**
     * The field definition of the object's class, enriched the way the object editor enriches it -
     * or, as grid configuration does it, with the permission subject handed over in the context
     * because that caller has no object to enrich the fields with.
     *
     * The definition is cloned first: the class definition hands out the one instance it holds,
     * and the enrichment writes the permissions onto it.
     */
    private function enrichFieldDefinition(
        Csstore|Csstorelocalized $object,
        User $user,
        bool $asGridConfiguration = false
    ): Data\Classificationstore {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);
        $this->assertInstanceOf(Data\Classificationstore::class, $fieldDefinition);

        $layout = clone $fieldDefinition;
        Service::enrichLayoutDefinition(
            $layout,
            $asGridConfiguration ? null : $object,
            $asGridConfiguration ? ['purpose' => 'gridconfig', 'object' => $object] : [],
            $user
        );

        return $layout;
    }

    /**
     * A user whose workspace on the object lists the languages they may see and edit.
     */
    private function createUserWithLanguagePermissions(
        Csstore|Csstorelocalized $object,
        string $viewable,
        string $editable
    ): User {
        $user = new User();
        $user->setName('perkeylocalization_' . uniqid());
        $user->setAdmin(false);

        $workspace = new User\Workspace\DataObject();
        $workspace->setCid($object->getId());
        $workspace->setCpath($object->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);
        $workspace->setSave(true);
        $workspace->setLView($viewable);
        $workspace->setLEdit($editable);
        $user->setWorkspacesObject([$workspace]);
        $user->save();

        return $user;
    }

    private function createKeyWithFlag(string $label, ?bool $localized, ?Data $definition = null): KeyConfig
    {
        if ($definition instanceof CalculatedValue) {
            $definition->setCalculatorClass('@test.calculatorservice');
        }

        $keyConfig = $this->createKey($label, true, $definition);
        $keyConfig->setLocalized($localized);
        $keyConfig->save();

        return $keyConfig;
    }

    /**
     * @param class-string<Csstore|Csstorelocalized> $class
     * @param array<int, bool> $activeGroups
     */
    private function createObject(string $class, string $key, array $activeGroups): Csstore|Csstorelocalized
    {
        $object = $this->buildObject($class, $key, $activeGroups);
        $object->save();

        return $object;
    }

    /**
     * An object that is not saved, for the validity checks: a save of an object whose mandatory key
     * is empty raises the very exception they are about to inspect.
     *
     * @param class-string<Csstore|Csstorelocalized> $class
     * @param array<int, bool> $activeGroups
     */
    private function buildObject(string $class, string $key, array $activeGroups): Csstore|Csstorelocalized
    {
        $object = new $class();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups($activeGroups);

        return $object;
    }

    /**
     * @return array<string, array>
     */
    private function getEditModePayload(Csstore|Csstorelocalized $object): array
    {
        return $object->getClass()->getFieldDefinition(self::FIELDNAME)
            ->getDataForEditmode($object->getCsstore(), $object);
    }

    /**
     * @return string[]
     */
    private function grabValidationMessages(Csstore|Csstorelocalized $object): array
    {
        try {
            $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());
            $this->fail('an empty mandatory key must not validate');
        } catch (ValidationException $validationException) {
            return array_map(
                static fn (ValidationException $subItem): string => $subItem->getMessage(),
                $validationException->getSubItems()
            );
        }
    }

    private function readGridValue(Csstore $object, GroupConfig $group, KeyConfig $keyConfig): mixed
    {
        $method = new ReflectionMethod(Service::class, 'getStoreValueForObject');

        return $method->invoke(
            null,
            $object,
            '~classificationstore~' . self::FIELDNAME . '~' . $group->getId() . '-' . $keyConfig->getId(),
            'en'
        );
    }

    private function readFlag(KeyConfig $keyConfig): ?int
    {
        $stored = Db::get()->fetchOne(
            'SELECT `' . self::COLUMN . '` FROM ' . self::TABLE . ' WHERE id = ?',
            [$keyConfig->getId()]
        );

        return $stored === null ? null : (int) $stored;
    }

    /**
     * @return string[]
     */
    private function getValidLanguages(): array
    {
        $validLanguages = Tool::getValidLanguages();
        array_unshift($validLanguages, self::DEFAULT_LANGUAGE);

        return $validLanguages;
    }

    /**
     * An edit mode payload holding one value per key and valid language, whatever the keys are
     * kept in, so that the save is asked to write more than it may.
     *
     * @param KeyConfig[] $keyConfigs
     *
     * @return array<string, array>
     */
    private function buildEditModeData(int $groupId, array $keyConfigs): array
    {
        $data = [];
        foreach ($this->getValidLanguages() as $language) {
            foreach ($keyConfigs as $keyConfig) {
                $data[$language][$groupId][$keyConfig->getId()] = 'posted ' . $language;
            }
        }

        return ['activeGroups' => [$groupId => true], 'groupCollectionMapping' => [], 'data' => $data];
    }

    /**
     * The language sets of two paths are the same set however each of them happened to order it.
     *
     * @param array<int, string[]> $languageSets
     *
     * @return array<int, string[]>
     */
    private function normaliseLanguageSets(array $languageSets): array
    {
        foreach ($languageSets as &$languages) {
            sort($languages);
        }
        unset($languages);

        ksort($languageSets);

        return $languageSets;
    }

    /**
     * The column taken off the table, through the migration itself so that the valid table columns
     * cache is reset the way an installation resets it.
     */
    private function dropTheColumn(): void
    {
        $migration = new Version20261105100000(Db::get(), new NullLogger());
        $schema = Db::get()->createSchemaManager()->introspectSchema();

        $migration->down($schema);
        $this->executeMigration($migration);
        $migration->postDown($schema);
    }

    private function restoreTheColumn(): void
    {
        $migration = new Version20261105100000(Db::get(), new NullLogger());
        $schema = Db::get()->createSchemaManager()->introspectSchema();

        $migration->up($schema);
        $this->executeMigration($migration);
        $migration->postUp($schema);
    }

    private function executeMigration(Version20261105100000 $migration): void
    {
        foreach ($migration->getSql() as $query) {
            Db::get()->executeStatement($query->getStatement());
        }
    }

    private function executeOnCopy(Version20261105100000 $migration, string $table): void
    {
        foreach ($migration->getSql() as $query) {
            Db::get()->executeStatement(
                str_replace('`' . self::TABLE . '`', '`' . $table . '`', $query->getStatement())
            );
        }
    }
}
