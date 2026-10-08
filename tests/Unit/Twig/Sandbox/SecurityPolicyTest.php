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

namespace Pimcore\Tests\Unit\Twig\Sandbox;

use PDO;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Consent;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Listing as DataObjectListing;
use Pimcore\Model\Dependency;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Element\Editlock;
use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Property;
use Pimcore\Model\Translation;
use Pimcore\Model\User;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use ReflectionClassConstant;
use stdClass;
use Symfony\Component\Yaml\Yaml;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

/**
 * @internal
 */
final class SecurityPolicyTest extends TestCase
{
    /**
     * The built-in denylists (blocked_classes/blocked_functions/hard_blocked_methods)
     * live entirely in bundles/CoreBundle/config/pimcore/default.yaml, not in
     * SecurityPolicy itself - read them from there so the "*ByDefault" tests below
     * exercise the actual shipped defaults instead of a duplicated PHP fixture.
     *
     * @return array{blocked_classes: string[], blocked_functions: string[], hard_blocked_methods: array<string, string[]>, hard_blocked_method_patterns: array<string, string[]>}
     */
    private static function defaultSandboxSecurityPolicyConfig(): array
    {
        static $config = null;

        if (null !== $config) {
            return $config;
        }

        $path = __DIR__ . '/../../../../bundles/CoreBundle/config/pimcore/default.yaml';
        $lines = file($path);

        // Isolate the `templating_engine` block by indentation rather than parsing the
        // whole file - default.yaml also contains an `!php/const` mapping key elsewhere
        // (Doctrine connection options) that Symfony\Yaml cannot parse as a plain value.
        $start = null;
        $end = count($lines);
        foreach ($lines as $i => $line) {
            if (null === $start && str_starts_with($line, '    templating_engine:')) {
                $start = $i;

                continue;
            }
            if (null !== $start && $i > $start && preg_match('/^ {4}\S/', $line)) {
                $end = $i;

                break;
            }
        }

        $fragment = implode('', array_map(
            static fn (string $line): string => str_starts_with($line, '    ') ? substr($line, 4) : $line,
            array_slice($lines, $start, $end - $start),
        ));

        $config = Yaml::parse($fragment)['templating_engine']['twig']['sandbox_security_policy'];

        return $config;
    }

    public function testBuiltInDenylistBlocksInfrastructureClassesByDefault(): void
    {
        $policy = new SecurityPolicy(blockedClasses: self::defaultSandboxSecurityPolicyConfig()['blocked_classes']);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($this->createStub(PDO::class), 'query');
    }

    public function testDenylistModeAllowsArbitraryObjectsNotOnTheList(): void
    {
        $policy = new SecurityPolicy(blockedClasses: self::defaultSandboxSecurityPolicyConfig()['blocked_classes']);

        // no exception expected
        $policy->checkMethodAllowed(new stdClass(), 'anything');
        $policy->checkPropertyAllowed(new stdClass(), 'anything');
        $this->addToAssertionCount(2);
    }

    public function testBlockedClassesExtendsTheBuiltInDenylist(): void
    {
        $policy = new SecurityPolicy(blockedClasses: [
            ...self::defaultSandboxSecurityPolicyConfig()['blocked_classes'],
            stdClass::class,
        ]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'anything');
    }

    public function testAllowlistModeDeniesEverythingNotOnTheAllowlist(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [PDO::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'anything');
    }

    public function testAllowlistModeAllowsListedClasses(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [stdClass::class]);

        $policy->checkMethodAllowed(new stdClass(), 'anything');
        $policy->checkPropertyAllowed(new stdClass(), 'anything');
        $this->addToAssertionCount(2);
    }

    public function testAllowlistModeDeactivatesTheDenylistEvenForBuiltInBlockedClasses(): void
    {
        // A non-empty allow list must take over completely: an infra class normally
        // blocked by the built-in denylist is allowed once it is itself allowlisted.
        $policy = new SecurityPolicy(allowedClasses: [PDO::class]);

        $policy->checkMethodAllowed($this->createStub(PDO::class), 'query');
        $this->addToAssertionCount(1);
    }

    public function testAllowlistModeIsPropertyAware(): void
    {
        $policy = new SecurityPolicy(allowedClasses: [stdClass::class]);

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed($this->createStub(PDO::class), 'secret');
    }

    public function testSetAllowedClassesSwitchesModeAtRuntime(): void
    {
        $policy = new SecurityPolicy();

        // starts in denylist mode: unrelated object is reachable
        $policy->checkMethodAllowed(new stdClass(), 'anything');

        $policy->setAllowedClasses([PDO::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'anything');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function userSecretMethodsProvider(): iterable
    {
        yield 'getPassword' => ['getPassword'];
        yield 'getPasswordRecoveryToken' => ['getPasswordRecoveryToken'];
        yield 'getTwoFactorAuthentication' => ['getTwoFactorAuthentication'];
    }

    /**
     * @dataProvider userSecretMethodsProvider
     */
    public function testUserIsBlockedByDefault(string $method): void
    {
        // GHSA-7gfm-v2fx-xrxm: User::getPassword()/getPasswordRecoveryToken() and
        // getTwoFactorAuthentication() (returns the MFA secret, models/User.php:679) must
        // not be template-reachable. The whole class is blocked by default (defense in depth).
        $policy = new SecurityPolicy(blockedClasses: self::defaultSandboxSecurityPolicyConfig()['blocked_classes']);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new User(), $method);
    }

    public function testUserPropertyAccessIsBlockedByDefault(): void
    {
        $policy = new SecurityPolicy(blockedClasses: self::defaultSandboxSecurityPolicyConfig()['blocked_classes']);

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed(new User(), 'password');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function assetContentMethodsProvider(): iterable
    {
        yield 'getData' => ['getData'];
        yield 'getStream' => ['getStream'];
        yield 'getLocalFile' => ['getLocalFile'];
        yield 'getTemporaryFile' => ['getTemporaryFile'];
    }

    /**
     * @dataProvider assetContentMethodsProvider
     */
    public function testAssetContentMethodsAreHardBlockedByDefault(string $method): void
    {
        // GHSA-7gfm-v2fx-xrxm: Asset stays reachable (needed for filename/thumbnail access
        // in templates), but its content-returning methods must never be callable.
        $policy = new SecurityPolicy(hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods']);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new Asset(), $method);
    }

    public function testAssetIsOtherwiseReachableByDefault(): void
    {
        $policy = new SecurityPolicy(hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods']);

        // no exception expected: only the content-returning methods are blocked
        $policy->checkMethodAllowed(new Asset(), 'getId');
        $policy->checkMethodAllowed(new Asset(), 'getFilename');
        $this->addToAssertionCount(2);
    }

    /**
     * GHSA-w9v9-v3mj-g4cp: none of the content-model classes stayed reachable-but-
     * mutable - `delete`/`save`/`saveVersion` must be unreachable regardless of which
     * of the three classes the instance belongs to.
     *
     * @return iterable<string, array{object, string}>
     */
    public static function contentModelMutationMethodsProvider(): iterable
    {
        foreach (['delete', 'save', 'saveVersion'] as $method) {
            yield "Asset::{$method}" => [new Asset(), $method];
            yield "DataObject\\Concrete::{$method}" => [new Concrete(), $method];
            yield "DataObject\\Folder::{$method}" => [new Folder(), $method];
            yield "Document::{$method}" => [new Document(), $method];
        }
    }

    /**
     * @dataProvider contentModelMutationMethodsProvider
     */
    public function testContentModelMutationMethodsAreHardBlockedByDefault(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods']);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * @return iterable<string, array{object, string}>
     */
    public static function contentModelSetterMethodsProvider(): iterable
    {
        yield 'Asset::setFilename' => [new Asset(), 'setFilename'];
        yield 'Asset::setData' => [new Asset(), 'setData'];
        yield 'DataObject\Concrete::setKey' => [new Concrete(), 'setKey'];
        // the generic `set($fieldName, $value)` accessor also mutates and must be caught
        yield 'DataObject\Concrete::set' => [new Concrete(), 'set'];
        yield 'DataObject\Folder::setKey' => [new Folder(), 'setKey'];
        yield 'Document::setKey' => [new Document(), 'setKey'];
        // PHP method names are case-insensitive and `__call`-dispatched setters reach the
        // policy with the casing used in the template
        yield 'DataObject\Concrete::SETKEY' => [new Concrete(), 'SETKEY'];
        // direct-write methods outside the exact save/delete/saveVersion names: they write
        // through the DAO without a later `save()` call
        yield 'DataObject\Concrete::saveIndex' => [new Concrete(), 'saveIndex'];
        yield 'DataObject\Folder::saveIndex' => [new Folder(), 'saveIndex'];
        yield 'Document::saveIndex' => [new Document(), 'saveIndex'];
        yield 'Asset::deleteAutoSaveVersions' => [new Asset(), 'deleteAutoSaveVersions'];
        yield 'DataObject\Concrete::deleteAutoSaveVersions' => [new Concrete(), 'deleteAutoSaveVersions'];
        yield 'Document::deleteAutoSaveVersions' => [new Document(), 'deleteAutoSaveVersions'];
        yield 'DataObject\Folder::SAVEINDEX' => [new Folder(), 'SAVEINDEX'];
        // static factories that persist immediately can be invoked through an instance
        yield 'Asset::create' => [new Asset(), 'create'];
        yield 'Document::create' => [new Document(), 'create'];
        yield 'DataObject\Folder::create' => [new Folder(), 'create'];
        // direct tree-lock / property / metadata / thumbnail mutations
        yield 'Asset::unlockPropagate' => [new Asset(), 'unlockPropagate'];
        yield 'DataObject\Concrete::unlockPropagate' => [new Concrete(), 'unlockPropagate'];
        yield 'Document::unlockPropagate' => [new Document(), 'unlockPropagate'];
        yield 'Asset::removeProperty' => [new Asset(), 'removeProperty'];
        yield 'Asset::addMetadata' => [new Asset(), 'addMetadata'];
        yield 'Asset::removeMetadata' => [new Asset(), 'removeMetadata'];
        yield 'Asset::removeCustomSetting' => [new Asset(), 'removeCustomSetting'];
        yield 'Asset::clearThumbnails' => [new Asset(), 'clearThumbnails'];
        // chained calls: mutable models that are reachable through allowed getters
        // (`Concrete::getClass()`, `getDependencies()`, `getProperties()`)
        yield 'ClassDefinition::delete' => [new ClassDefinition(), 'delete'];
        yield 'ClassDefinition::save' => [new ClassDefinition(), 'save'];
        yield 'ClassDefinition::rename' => [new ClassDefinition(), 'rename'];
        yield 'ClassDefinition::generateClassFiles' => [new ClassDefinition(), 'generateClassFiles'];
        yield 'Dependency::cleanAllForElement' => [new Dependency(), 'cleanAllForElement'];
        yield 'Dependency::clean' => [new Dependency(), 'clean'];
        // models reachable through functions/getters whose persisting methods are not named like a setter
        // (static methods can also be invoked through an instance)
        yield 'Element\\Tag::batchAssignTagsToElement' => [new Tag(), 'batchAssignTagsToElement'];
        yield 'Element\\Tag::BATCHASSIGNTAGSTOELEMENT' => [new Tag(), 'BATCHASSIGNTAGSTOELEMENT'];
        yield 'Element\\Editlock::lock' => [new Editlock(), 'lock'];
        yield 'Element\\Recyclebin::flush' => [new Recyclebin(), 'flush'];
        yield 'Translation::importTranslationsFromFile' => [new Translation(), 'importTranslationsFromFile'];
        yield 'Element\\Recyclebin\\Item::restore' => [new Recyclebin\Item(), 'restore'];
        yield 'Property::setData' => [new Property(), 'setData'];
        // persistence gateways reached through allowed getters: dump*/update* write through the
        // DAO or the definition files without a later `save()` call
        yield 'ClassDefinition::dumpClass' => [new ClassDefinition(), 'dumpClass'];
        yield 'ClassDefinition::DUMPCLASS' => [new ClassDefinition(), 'DUMPCLASS'];
        yield 'Asset::updateCustomSettings' => [new Asset(), 'updateCustomSettings'];
        // URL slug values (generated getters return them) are persisted but not AbstractModel
        yield 'Data\\UrlSlug::delete' => [new UrlSlug('x'), 'delete'];
        yield 'Data\\UrlSlug::DELETE' => [new UrlSlug('x'), 'DELETE'];
        yield 'Data\\UrlSlug::setSlug' => [new UrlSlug('x'), 'setSlug'];
        yield 'Data\\UrlSlug::createFromDataRow' => [new UrlSlug('x'), 'createFromDataRow'];
        yield 'Data\\UrlSlug::handleClassDeleted' => [new UrlSlug('x'), 'handleClassDeleted'];
        // read-looking admin-UI deserialisers that persist (Consent field definition writes Notes)
        yield 'ClassDefinition\\Data\\Consent::getDataFromEditmode' => [new Consent(), 'getDataFromEditmode'];
        yield 'ClassDefinition\\Data\\Consent::getDiffDataFromEditmode' => [new Consent(), 'getDiffDataFromEditmode'];
        yield 'ClassDefinition\\Data\\Consent::GETDATAFROMEDITMODE' => [new Consent(), 'GETDATAFROMEDITMODE'];
        yield 'ClassDefinition\\Data\\Input::getDataForEditmode' => [new Input(), 'getDataForEditmode'];
        // field definitions persist immediately through save($object)/delete($object)/classSaved($class)
        yield 'ClassDefinition\\Data\\Fieldcollections::save' => [new Fieldcollections(), 'save'];
        yield 'ClassDefinition\\Data\\Fieldcollections::delete' => [new Fieldcollections(), 'delete'];
        yield 'ClassDefinition\\Data\\Localizedfields::save' => [new Localizedfields(), 'save'];
        yield 'ClassDefinition\\Data\\Localizedfields::DELETE' => [new Localizedfields(), 'DELETE'];
        yield 'ClassDefinition\\Data\\Classificationstore::classSaved' => [new Classificationstore(), 'classSaved'];
        yield 'ClassDefinition\\Data\\Classificationstore::CLASSDELETED' => [new Classificationstore(), 'CLASSDELETED'];
        // verifyPassword() rehashes and saves the object (and is a password oracle); calculateDelta() inserts rows
        yield 'ClassDefinition\\Data\\Password::verifyPassword' => [new Password(), 'verifyPassword'];
        yield 'ClassDefinition\\Data\\Password::VERIFYPASSWORD' => [new Password(), 'VERIFYPASSWORD'];
        yield 'ClassDefinition\\Data\\ManyToManyRelation::calculateDelta' => [new ManyToManyRelation(), 'calculateDelta'];
        yield 'ClassDefinition\\Data\\ManyToManyRelation::CalculateDelta' => [new ManyToManyRelation(), 'CalculateDelta'];
        yield 'ClassDefinition\\Data::setName' => [new Input(), 'setName'];
        yield 'ClassDefinition\\Data::setMandatory' => [new Input(), 'setMandatory'];
    }

    /**
     * `get*` methods that persist as a side effect cannot be told apart by name, so they are
     * hard-blocked by exact name.
     */
    public function testImageGetDimensionsIsHardBlockedByDefault(): void
    {
        // getDimensions($path, true) stores dimensions read from a caller-chosen path
        $policy = new SecurityPolicy(
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new Image(), 'getDimensions');
    }

    /**
     * PHP method names are case-insensitive, so the exact-name hard blocks must be too.
     *
     * @return iterable<string, array{object, string}>
     */
    public static function differentlyCasedHardBlockedMethodsProvider(): iterable
    {
        yield 'Image::GETDIMENSIONS' => [new Image(), 'GETDIMENSIONS'];
        yield 'Image::getdimensions' => [new Image(), 'getdimensions'];
        yield 'Asset::GetData' => [new Asset(), 'GetData'];
        yield 'Asset::GETLOCALFILE' => [new Asset(), 'GETLOCALFILE'];
        yield 'User::GETPASSWORD' => [new User(), 'GETPASSWORD'];
        yield 'User::getpasswordrecoverytoken' => [new User(), 'getpasswordrecoverytoken'];
        yield 'Asset::SAVE' => [new Asset(), 'SAVE'];
        yield 'Document::Delete' => [new Document(), 'Delete'];
    }

    /**
     * @dataProvider differentlyCasedHardBlockedMethodsProvider
     */
    public function testHardBlockedMethodsAreMatchedCaseInsensitively(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * @dataProvider differentlyCasedHardBlockedMethodsProvider
     */
    public function testCaseInsensitiveHardBlocksSurviveAllowlistMode(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(
            allowedClasses: [$instance::class],
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * `UrlSlug::getAction()` deletes the slug row when its field definition is gone, so a
     * read-looking getter can still destroy data.
     */
    public function testUrlSlugGetActionIsHardBlockedWhileOtherReadsStayReachable(): void
    {
        $policy = new SecurityPolicy(
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        $policy->checkMethodAllowed(new UrlSlug('x'), 'getSlug');
        $policy->checkMethodAllowed(new UrlSlug('x'), 'getSiteId');
        $this->addToAssertionCount(2);

        foreach (['getAction', 'GETACTION'] as $method) {
            try {
                $policy->checkMethodAllowed(new UrlSlug('x'), $method);
                $this->fail($method . ' must be blocked');
            } catch (SecurityNotAllowedMethodError) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testImageGetDimensionsSurvivesAllowlistMode(): void
    {
        $policy = new SecurityPolicy(
            allowedClasses: [Image::class],
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new Image(), 'getDimensions');
    }

    /**
     * End-to-end chain: every hop of a template such as
     * `object.getClass().getFieldDefinition('x').setName('y')` or
     * `object.getClass().dumpClass()` is checked individually by Twig, so the getters
     * must stay reachable while the final mutating call must not.
     */
    public function testChainedClassDefinitionPersistenceIsBlockedWhileReadsStayReachable(): void
    {
        $policy = new SecurityPolicy(
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        // hops that must keep working
        $policy->checkMethodAllowed(new Concrete(), 'getClass');
        $policy->checkMethodAllowed(new ClassDefinition(), 'getFieldDefinition');
        $policy->checkMethodAllowed(new Input(), 'getName');
        $policy->checkMethodAllowed(new Input(), 'isMandatory');
        $policy->checkMethodAllowed(new Consent(), 'getName');
        $this->addToAssertionCount(5);

        // hops that persist or mutate
        foreach ([[new ClassDefinition(), 'dumpClass'], [new Input(), 'setName']] as [$instance, $method]) {
            try {
                $policy->checkMethodAllowed($instance, $method);
                $this->fail(sprintf('%s::%s must be blocked', $instance::class, $method));
            } catch (SecurityNotAllowedMethodError) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * @dataProvider contentModelSetterMethodsProvider
     */
    public function testContentModelSettersAreHardBlockedByDefault(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    public function testContentModelReadMethodsRemainReachableByDefault(): void
    {
        // the fix must not turn the content-model classes into infrastructure-style
        // blocked classes - safe read access (needed by Email/Dynamic Text rendering)
        // must keep working.
        $policy = new SecurityPolicy(
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        $policy->checkMethodAllowed(new Asset(), 'getId');
        $policy->checkMethodAllowed(new Asset(), 'getFilename');
        $policy->checkMethodAllowed(new Concrete(), 'getId');
        $policy->checkMethodAllowed(new Concrete(), 'getKey');
        $policy->checkMethodAllowed(new Folder(), 'getKey');
        $policy->checkMethodAllowed(new Document(), 'getId');
        $policy->checkMethodAllowed(new Document(), 'getKey');
        $policy->checkMethodAllowed(new ClassDefinition(), 'getId');
        $policy->checkMethodAllowed(new Dependency(), 'getRequires');
        // is*/has* and the generic `get($fieldName)` accessor stay reachable
        $policy->checkMethodAllowed(new Document(), 'isPublished');
        $policy->checkMethodAllowed(new Document(), 'hasChildren');
        $policy->checkMethodAllowed(new Concrete(), 'get');
        $policy->checkMethodAllowed(new Image(), 'getWidth');
        // established read operations of listings and editables on models
        $policy->checkMethodAllowed($this->createStub(DataObjectListing::class), 'count');
        $policy->checkMethodAllowed($this->createStub(DataObjectListing::class), 'load');
        $policy->checkMethodAllowed($this->createStub(Editable\Input::class), 'render');
        $policy->checkMethodAllowed($this->createStub(Editable\Areablock::class), 'renderIndex');
        $this->addToAssertionCount(17);
    }

    /**
     * @dataProvider contentModelMutationMethodsProvider
     */
    public function testHardBlockedMethodsSurviveAllowlistModeForContentModelClasses(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(
            allowedClasses: [Asset::class, Concrete::class, Folder::class, Document::class],
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * @dataProvider contentModelSetterMethodsProvider
     */
    public function testHardBlockedMethodPatternsSurviveAllowlistMode(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(
            allowedClasses: [Asset::class, Concrete::class, Folder::class, Document::class],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * `AbstractModel::__call()` forwards undeclared methods to the DAO, which would otherwise
     * reach the database layer under the model's class name.
     *
     * @return iterable<string, array{object, string}>
     */
    public static function daoDelegatedMethodsProvider(): iterable
    {
        yield 'Asset::beginTransaction' => [new Asset(), 'beginTransaction'];
        yield 'Asset::commit' => [new Asset(), 'commit'];
        yield 'Asset::rollBack' => [new Asset(), 'rollBack'];
        yield 'Asset::moveThumbnailCache' => [new Asset(), 'moveThumbnailCache'];
        yield 'Asset::BEGINTRANSACTION' => [new Asset(), 'BEGINTRANSACTION'];
        // the magic methods are public: calling __call directly would forward any name to the DAO
        yield 'Asset::__call' => [new Asset(), '__call'];
        yield 'Asset::__CALL' => [new Asset(), '__CALL'];
        yield 'DataObject\\Concrete::__call' => [new Concrete(), '__call'];
        yield 'DataObject\\Folder::__Call' => [new Folder(), '__Call'];
        yield 'Document::__call' => [new Document(), '__call'];
        yield 'Asset::__get' => [new Asset(), '__get'];
        yield 'Asset::__set' => [new Asset(), '__set'];
        yield 'Asset::__clone' => [new Asset(), '__clone'];
        yield 'Document::updateChildPaths' => [new Document(), 'updateChildPaths'];
        yield 'DataObject\\Concrete::beginTransaction' => [new Concrete(), 'beginTransaction'];
        yield 'DataObject\\Folder::moveSomethingInTheDao' => [new Folder(), 'moveSomethingInTheDao'];
    }

    /**
     * @dataProvider daoDelegatedMethodsProvider
     */
    public function testDaoDelegatedMethodsAreDeniedOnModels(object $instance, string $method): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * @dataProvider daoDelegatedMethodsProvider
     */
    public function testDaoDelegatedMethodsAreDeniedInAllowlistMode(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(allowedClasses: [$instance::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    public function testDeclaredMethodsAndMagicAccessorsAreNotAffectedByDaoDelegationCheck(): void
    {
        $policy = new SecurityPolicy(
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
            hardBlockedMethodPatterns: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
        );

        // declared methods outside the blocked verbs and outside get*/is*/has* stay callable
        $policy->checkMethodAllowed($this->createStub(DataObjectListing::class), 'load');
        $policy->checkMethodAllowed($this->createStub(DataObjectListing::class), 'count');
        // undeclared get*/is*/has* calls are the magic accessors (generated or DAO-backed reads)
        $policy->checkMethodAllowed(new Asset(), 'getSomethingMagic');
        $policy->checkMethodAllowed(new Concrete(), 'getByMagicField');
        $policy->checkMethodAllowed(new Concrete(), 'hasSomethingMagic');
        // Listing::load() is not declared on the class: it is delegated to the DAO
        $policy->checkMethodAllowed(new Concrete(), 'loadSomethingDelegated');
        $policy->checkMethodAllowed(new Concrete(), 'countSomethingDelegated');
        $this->addToAssertionCount(7);
    }

    /**
     * Consumers that build their own policy (e.g. a bundle with its own sandbox) never pass the
     * patterns; they must still get the mutation blocks.
     *
     * @dataProvider contentModelSetterMethodsProvider
     */
    public function testBuiltInPatternsApplyWhenNoPatternsArePassed(object $instance, string $method): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    /**
     * A site that allowlists a field-definition class must not get its persistence entry points back.
     *
     * @dataProvider contentModelSetterMethodsProvider
     */
    public function testBuiltInPatternsSurviveAllowlistModeForTheInstanceClass(object $instance, string $method): void
    {
        $policy = new SecurityPolicy(allowedClasses: [$instance::class]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($instance, $method);
    }

    public function testExplicitEmptyPatternListDisablesTheBuiltInPatterns(): void
    {
        $policy = new SecurityPolicy(hardBlockedMethodPatterns: []);

        $policy->checkMethodAllowed(new Asset(), 'setKey');
        $this->addToAssertionCount(1);
    }

    /**
     * The built-in defaults and the shipped configuration defaults are two copies of the same
     * list: keep them from drifting apart.
     */
    public function testBuiltInPatternsMirrorTheConfigurationDefaults(): void
    {
        $builtIn = (new ReflectionClassConstant(SecurityPolicy::class, 'DEFAULT_HARD_BLOCKED_METHOD_PATTERNS'))->getValue();

        $this->assertSame(
            self::defaultSandboxSecurityPolicyConfig()['hard_blocked_method_patterns'],
            $builtIn,
        );
    }

    public function testMalformedHardBlockedMethodPatternIsRejectedByConstructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SecurityPolicy(hardBlockedMethodPatterns: [stdClass::class => ['/^set(/']]);
    }

    public function testMalformedHardBlockedMethodPatternIsRejectedBySetter(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(\InvalidArgumentException::class);
        $policy->setHardBlockedMethodPatterns([stdClass::class => ['not-a-pattern']]);
    }

    public function testScalarPatternListIsRejectedByConstructor(): void
    {
        // easy-to-make mistake: a bare pattern instead of a list of patterns must not fail open
        $this->expectException(\InvalidArgumentException::class);
        new SecurityPolicy(hardBlockedMethodPatterns: [stdClass::class => '/^set/']);
    }

    public function testScalarPatternListIsRejectedBySetter(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(\InvalidArgumentException::class);
        $policy->setHardBlockedMethodPatterns([stdClass::class => '/^set/']);
    }

    public function testRejectedPatternsLeaveThePreviousPatternsInEffect(): void
    {
        $policy = new SecurityPolicy(hardBlockedMethodPatterns: [stdClass::class => ['/^set/']]);

        try {
            $policy->setHardBlockedMethodPatterns([stdClass::class => '/^get/']);
            $this->fail('a scalar pattern list must be rejected');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'setAnything');
    }

    public function testHardBlockedMethodPatternsCanBeSetAtRuntime(): void
    {
        $policy = new SecurityPolicy();

        // starts with no patterns configured: reachable
        $policy->checkMethodAllowed(new stdClass(), 'setAnything');

        $policy->setHardBlockedMethodPatterns([stdClass::class => ['/^set/']]);

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new stdClass(), 'setAnything');
    }

    /**
     * @dataProvider userSecretMethodsProvider
     */
    public function testHardBlockedMethodsSurviveAllowlistModeForUser(string $method): void
    {
        // Even if a site explicitly allowlists User (e.g. to expose getFirstname()), the
        // secret-returning methods - including getTwoFactorAuthentication(), which returns
        // the MFA secret - must remain unreachable.
        $policy = new SecurityPolicy(
            allowedClasses: [User::class],
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
        );

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new User(), $method);
    }

    public function testHardBlockedMethodsSurviveAllowlistModeForAsset(): void
    {
        $policy = new SecurityPolicy(
            allowedClasses: [Asset::class],
            hardBlockedMethods: self::defaultSandboxSecurityPolicyConfig()['hard_blocked_methods'],
        );

        // getId remains reachable via the allowlist ...
        $policy->checkMethodAllowed(new Asset(), 'getId');
        $this->addToAssertionCount(1);

        // ... but getData is hard-blocked regardless.
        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new Asset(), 'getData');
    }

    /**
     * All `pimcore_*` id/path lookup functions - each hands back a live model instance
     * looked up by an arbitrary id/path (GHSA-7gfm-v2fx-xrxm remediation #3). Only
     * `pimcore_user` ships blocked by default (see testPimcoreUserIsNotAutoAllowedByDefault());
     * the rest are shipped commented out in default.yaml's `blocked_functions` and are
     * covered here to confirm they still block correctly once a site re-adds them for a
     * high-security setup (doc/26_Best_Practice/80_Twig_Sandbox_Object_Access.md).
     *
     * @return iterable<string, array{string}>
     */
    public static function allIdLookupPimcoreFunctionsProvider(): iterable
    {
        yield 'pimcore_asset' => ['pimcore_asset'];
        yield 'pimcore_asset_by_path' => ['pimcore_asset_by_path'];
        yield 'pimcore_document' => ['pimcore_document'];
        yield 'pimcore_document_by_path' => ['pimcore_document_by_path'];
        yield 'pimcore_document_wrap_hardlink' => ['pimcore_document_wrap_hardlink'];
        yield 'pimcore_object' => ['pimcore_object'];
        yield 'pimcore_object_by_path' => ['pimcore_object_by_path'];
        yield 'pimcore_object_classificationstore_group' => ['pimcore_object_classificationstore_group'];
        yield 'pimcore_object_brick_definition_key' => ['pimcore_object_brick_definition_key'];
        yield 'pimcore_site' => ['pimcore_site'];
        yield 'pimcore_site_by_root_id' => ['pimcore_site_by_root_id'];
        yield 'pimcore_site_by_domain' => ['pimcore_site_by_domain'];
        yield 'pimcore_site_current' => ['pimcore_site_current'];
        yield 'pimcore_user' => ['pimcore_user'];
    }

    /**
     * Same list as allIdLookupPimcoreFunctionsProvider() minus pimcore_user, which is
     * the only one still blocked by default.
     *
     * @return iterable<string, array{string}>
     */
    public static function idLookupPimcoreFunctionsAutoAllowedByDefaultProvider(): iterable
    {
        yield 'pimcore_asset' => ['pimcore_asset'];
        yield 'pimcore_asset_by_path' => ['pimcore_asset_by_path'];
        yield 'pimcore_document' => ['pimcore_document'];
        yield 'pimcore_document_by_path' => ['pimcore_document_by_path'];
        yield 'pimcore_document_wrap_hardlink' => ['pimcore_document_wrap_hardlink'];
        yield 'pimcore_object' => ['pimcore_object'];
        yield 'pimcore_object_by_path' => ['pimcore_object_by_path'];
        yield 'pimcore_object_classificationstore_group' => ['pimcore_object_classificationstore_group'];
        yield 'pimcore_object_brick_definition_key' => ['pimcore_object_brick_definition_key'];
        yield 'pimcore_site' => ['pimcore_site'];
        yield 'pimcore_site_by_root_id' => ['pimcore_site_by_root_id'];
        yield 'pimcore_site_by_domain' => ['pimcore_site_by_domain'];
        yield 'pimcore_site_current' => ['pimcore_site_current'];
    }

    public function testPimcoreUserIsNotAutoAllowedByDefault(): void
    {
        // GHSA-7gfm-v2fx-xrxm remediation #3: pimcore_user(1) hands back a live User
        // instance looked up by id - this is the one id/path lookup function still
        // blocked out of the box.
        $policy = new SecurityPolicy(blockedFunctions: self::defaultSandboxSecurityPolicyConfig()['blocked_functions']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['pimcore_user']);
    }

    public function testPimcoreFileExistsIsNotAutoAllowedByDefault(): void
    {
        // GHSA-7m33-xgw9-j3g7: pimcore_file_exists() calls PHP's is_file() directly on
        // its argument, letting a sandboxed template use the boolean result as a
        // filesystem-existence oracle for any path reachable by the PHP process.
        $policy = new SecurityPolicy(blockedFunctions: self::defaultSandboxSecurityPolicyConfig()['blocked_functions']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['pimcore_file_exists']);
    }

    /**
     * @dataProvider idLookupPimcoreFunctionsAutoAllowedByDefaultProvider
     */
    public function testOtherIdLookupPimcoreFunctionsAreAutoAllowedByDefault(string $function): void
    {
        // These are shipped commented out in default.yaml's blocked_functions for a
        // lower-friction default - a high-security setup should re-add them (see
        // doc/26_Best_Practice/80_Twig_Sandbox_Object_Access.md).
        $policy = new SecurityPolicy(blockedFunctions: self::defaultSandboxSecurityPolicyConfig()['blocked_functions']);

        // no exception expected - not blocked by default
        $policy->checkSecurity([], [], [$function]);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider allIdLookupPimcoreFunctionsProvider
     */
    public function testIdLookupPimcoreFunctionsCanBeBlockedForHighSecurity(string $function): void
    {
        // Confirms the high-security config recommended in
        // doc/26_Best_Practice/80_Twig_Sandbox_Object_Access.md - re-adding every
        // id/path lookup function to blocked_functions - still blocks correctly.
        $policy = new SecurityPolicy(blockedFunctions: array_column(
            iterator_to_array(self::allIdLookupPimcoreFunctionsProvider()),
            0,
        ));

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], [$function]);
    }

    public function testIdLookupPimcoreFunctionCanBeExplicitlyAllowed(): void
    {
        $policy = new SecurityPolicy(allowedFunctions: ['pimcore_user']);

        // no exception expected
        $policy->checkSecurity([], [], ['pimcore_user']);
        $this->addToAssertionCount(1);
    }

    public function testOtherPimcoreFunctionsRemainAutoAllowedByDefault(): void
    {
        $policy = new SecurityPolicy(blockedFunctions: self::defaultSandboxSecurityPolicyConfig()['blocked_functions']);

        // a rendering/helper pimcore_* function not on the explicit-allow list
        $policy->checkSecurity([], [], ['pimcore_dump']);
        $this->addToAssertionCount(1);
    }

    public function testNonPimcoreFunctionsStillRequireExplicitAllow(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['file_get_contents']);
    }

    public function testBlockedFunctionsExtendsTheBuiltInDenylist(): void
    {
        $policy = new SecurityPolicy(blockedFunctions: [
            ...self::defaultSandboxSecurityPolicyConfig()['blocked_functions'],
            'pimcore_dump',
        ]);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $policy->checkSecurity([], [], ['pimcore_dump']);
    }
}
