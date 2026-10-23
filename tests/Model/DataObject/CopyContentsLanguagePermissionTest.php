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

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * "Paste only contents here" must only replace the localized values of the languages the user may edit
 * (https://github.com/pimcore/pimcore/issues/6033).
 *
 * @group model.dataobject.copycontents
 */
class CopyContentsLanguagePermissionTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupFieldcollection_Unittestfieldcollection();
        $this->tester->setupPimcoreClass_Unittest();
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_LazyLoadingTest();
        $this->tester->setupFieldcollection_LazyLoadingLocalizedTest();
        $this->tester->setupPimcoreClass_LazyLoading();
        $this->tester->setupObjectbrick_LazyLoadingTest();
        $this->tester->setupObjectbrick_LazyLoadingLocalizedTest();
    }

    public function testLocalizedFieldsOfNonEditableLanguagesAreKept(): void
    {
        $source = $this->createUnittest(['en' => 'source en', 'de' => 'source de', 'fr' => 'source fr']);
        $target = $this->createUnittest(['en' => 'target en', 'de' => 'target de', 'fr' => 'target fr']);

        $this->copyContents($target, $source, $this->createUserWithEditableLanguages($target, 'en,fr'));

        $result = Unittest::getById($target->getId(), ['force' => true]);
        $this->assertSame('source en', $result->getLinput('en'));
        $this->assertSame('source fr', $result->getLinput('fr'));
        $this->assertSame('target de', $result->getLinput('de'), 'de is not editable and must keep the target value');
    }

    public function testLanguageOnlyPresentInSourceIsNotCopiedIfNotEditable(): void
    {
        $source = $this->createUnittest(['en' => 'source en', 'fr' => 'source fr']);
        $target = $this->createUnittest(['en' => 'target en']);

        $this->copyContents($target, $source, $this->createUserWithEditableLanguages($target, 'en'));

        $result = Unittest::getById($target->getId(), ['force' => true]);
        $this->assertSame('source en', $result->getLinput('en'));
        $this->assertEmpty($result->getLinput('fr'));
    }

    public function testNonLocalizedFieldsAreStillCopied(): void
    {
        $source = $this->createUnittest(['en' => 'source en']);
        $source->setInput('source input');
        $source->save();
        $target = $this->createUnittest(['en' => 'target en']);

        $this->copyContents($target, $source, $this->createUserWithEditableLanguages($target, 'en'));

        $this->assertSame('source input', Unittest::getById($target->getId(), ['force' => true])->getInput());
    }

    public function testAllLanguagesAreCopiedWithoutLanguageRestriction(): void
    {
        $source = $this->createUnittest(['en' => 'source en', 'de' => 'source de']);
        $target = $this->createUnittest(['en' => 'target en', 'de' => 'target de']);

        $this->copyContents($target, $source, $this->createUserWithEditableLanguages($target, null));

        $result = Unittest::getById($target->getId(), ['force' => true]);
        $this->assertSame('source en', $result->getLinput('en'));
        $this->assertSame('source de', $result->getLinput('de'));
    }

    public function testAllLanguagesAreCopiedWithoutUser(): void
    {
        $source = $this->createUnittest(['en' => 'source en', 'de' => 'source de']);
        $target = $this->createUnittest(['en' => 'target en', 'de' => 'target de']);

        $this->copyContents($target, $source, null);

        $result = Unittest::getById($target->getId(), ['force' => true]);
        $this->assertSame('source en', $result->getLinput('en'));
        $this->assertSame('source de', $result->getLinput('de'));
    }

    public function testAllLanguagesAreCopiedForAdmin(): void
    {
        $source = $this->createUnittest(['en' => 'source en', 'de' => 'source de']);
        $target = $this->createUnittest(['en' => 'target en', 'de' => 'target de']);

        $admin = $this->createUserWithEditableLanguages($target, 'en');
        $admin->setAdmin(true);
        $admin->save();

        $this->copyContents($target, $source, $admin);

        $this->assertSame('source de', Unittest::getById($target->getId(), ['force' => true])->getLinput('de'));
    }

    public function testFieldcollectionLocalizedFieldsOfNonEditableLanguagesAreKept(): void
    {
        $source = $this->createUnittest([]);
        $source->setFieldcollection($this->fieldcollection(['en' => 'source en', 'de' => 'source de']));
        $source->save();
        $target = $this->createUnittest([]);
        $target->setFieldcollection($this->fieldcollection(['en' => 'target en', 'de' => 'target de']));
        $target->save();

        $this->copyContents($target, $source, $this->createUserWithEditableLanguages($target, 'en'));

        $item = Unittest::getById($target->getId(), ['force' => true])->getFieldcollection()->getItems()[0];
        $this->assertSame('source en', $item->getLinput('en'));
        $this->assertSame('target de', $item->getLinput('de'));
    }

    public function testObjectbrickLocalizedFieldsOfNonEditableLanguagesAreKept(): void
    {
        $source = $this->createLazyLoading(['en' => 'source en', 'de' => 'source de']);
        $target = $this->createLazyLoading(['en' => 'target en', 'de' => 'target de']);

        $this->copyContents($target, $source, $this->createUserWithEditableLanguages($target, 'en'));

        $brick = LazyLoading::getById($target->getId(), ['force' => true])->getBricks()->getLazyLoadingLocalizedTest();
        $this->assertSame('source en', $brick->getLinput('en'));
        $this->assertSame('target de', $brick->getLinput('de'));
    }

    /**
     * @param array<string, string> $values
     */
    private function createUnittest(array $values): Unittest
    {
        $object = TestHelper::createEmptyObject();
        foreach ($values as $language => $value) {
            $object->setLinput($value, $language);
        }
        $object->save();

        return $object;
    }

    /**
     * @param array<string, string> $values
     */
    private function createLazyLoading(array $values): LazyLoading
    {
        $object = TestHelper::createEmptyObject('', false, true, LazyLoading::class);
        $brick = new LazyLoadingLocalizedTest($object);
        foreach ($values as $language => $value) {
            $brick->getLocalizedfields()->setLocalizedValue('linput', $value, $language);
        }
        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();

        return $object;
    }

    /**
     * @param array<string, string> $values
     */
    private function fieldcollection(array $values): Fieldcollection
    {
        $item = new Fieldcollection\Data\Unittestfieldcollection();
        foreach ($values as $language => $value) {
            $item->setLinput($value, $language);
        }

        return new Fieldcollection([$item], 'fieldcollection');
    }

    private function createUserWithEditableLanguages(Concrete $target, ?string $languages): User
    {
        $user = new User();
        $user->setName('copycontents_' . uniqid());

        $workspace = new User\Workspace\DataObject();
        $workspace->setCid($target->getId());
        $workspace->setCpath($target->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);
        $workspace->setSave(true);
        $workspace->setPublish(true);
        $workspace->setCreate(true);
        $workspace->setLEdit($languages);
        $user->setWorkspacesObject([$workspace]);
        $user->save();

        return $user;
    }

    private function copyContents(Concrete $target, Concrete $source, ?User $user): void
    {
        (new Service($user))->copyContents(
            $target::getById($target->getId(), ['force' => true]),
            $source::getById($source->getId(), ['force' => true])
        );
    }
}
