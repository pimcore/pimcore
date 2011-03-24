<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class Element_CopyAndDeleteTest extends PHPUnit_Framework_TestCase {


    public function testCopyAndDeleteObject() {

        $objectList = new Object_List();
        $objectList->setCondition("o_key like '%_data%' and o_type = 'object'");
        $objects = $objectList->load();
        $parent = $objects[0];
        $this->assertTrue($parent instanceof Object_Unittest);

        //remove childs if there are some
        if ($parent->hasChilds()) {
            foreach ($parent->getChilds() as $child) {
                $child->delete();
            }
        }

        $this->assertFalse($parent->hasChilds());

        $service = new Object_Service(User::getById(1));

        //copy as child
        $service->copyAsChild($parent, $parent);

        $this->assertTrue($parent->hasChilds());
        $this->assertTrue(count($parent->getChilds()) == 1);

        //copy as child no. 2
        $service->copyAsChild($parent, $parent);

        $this->assertTrue($parent->hasChilds());
        $this->assertTrue(count($parent->getChilds()) == 2);

        $childs = $parent->getChilds();

        //load all in case of lazy loading fields
        Object_Service::loadAllObjectFields($parent);
        Object_Service::loadAllObjectFields($childs[0]);
        Object_Service::loadAllObjectFields($childs[1]);

        $this->assertTrue(Test_Tool::objectsAreEqual($parent, $childs[0], true));
        $this->assertTrue(Test_Tool::objectsAreEqual($parent, $childs[1], true));

        //copy recursivley
        $rootNode = Object_Abstract::getById(1);

        $copy = $service->copyRecursive($rootNode, $parent);

        $this->assertTrue($copy->hasChilds());

        Object_Service::loadAllObjectFields($copy);

        $this->assertTrue(count($copy->getChilds()) == 2);
        $this->assertTrue(Test_Tool::objectsAreEqual($parent, $copy, true));


        //create empty object
        $emptyObject = new Object_Unittest();
        $emptyObject->setParentId(1);
        $emptyObject->setUserOwner(1);
        $emptyObject->setUserModification(1);
        $emptyObject->setCreationDate(time());
        $emptyObject->setKey(uniqid() . rand(10, 99));
        $emptyObject->save();

        $this->assertFalse(Test_Tool::objectsAreEqual($emptyObject, $copy, true));

        //copy contents
        $emptyObject = $service->copyContents($emptyObject, $copy);

        $this->assertTrue(Test_Tool::objectsAreEqual($emptyObject, $copy, true));

        //todo copy contents must fail if types differ

        //delete recusively
        $shouldBeDeleted[] = $copy->getId();
        $childs = $copy->getChilds();
        foreach ($childs as $child) {
            $shouldBeDeleted[] = $child->getId();
        }

        $copy->delete();

        foreach ($shouldBeDeleted as $id) {
            $o = Object_Abstract::getById($id);
            $this->assertFalse($o instanceof Object_Abstract);
        }


    }


    public function testCopyAndDeleteDocument() {

        $documentList = new Document_List();
        $documentList->setCondition("`key` like '%_data%' and `type` = 'page'");
        $documents = $documentList->load();
        $parent = $documents[0];
        $this->assertTrue($parent instanceof Document_Page);

        //remove childs if there are some
        if ($parent->hasChilds()) {
            foreach ($parent->getChilds() as $child) {
                $child->delete();
            }
        }

        $this->assertFalse($parent->hasChilds());

        $service = new Document_Service(User::getById(1));

        //copy as child
        $service->copyAsChild($parent, $parent);

        $this->assertTrue($parent->hasChilds());
        $this->assertTrue(count($parent->getChilds()) == 1);

        //copy as child no. 2
        $service->copyAsChild($parent, $parent);

        $this->assertTrue($parent->hasChilds());
        $this->assertTrue(count($parent->getChilds()) == 2);

        $childs = $parent->getChilds();

        $this->assertTrue(Test_Tool::documentsAreEqual($parent, $childs[0], true));
        $this->assertTrue(Test_Tool::documentsAreEqual($parent, $childs[1], true));

        //copy recursivley
        $rootNode = Document::getById(1);

        $copy = $service->copyRecursive($rootNode, $parent);

        $this->assertTrue($copy->hasChilds());

        $this->assertTrue(count($copy->getChilds()) == 2);

        $this->assertTrue(Test_Tool::documentsAreEqual($parent, $copy, true));


        //create empty document
        $emptyDoc = Document_Page::create(1, array(
            "userOwner" => 1,
            "key" => uniqid() . rand(10, 99)
        ));


        $this->assertFalse(Test_Tool::documentsAreEqual($emptyDoc, $copy, true));

        //copy contents
        $emptyDoc = $service->copyContents($emptyDoc, $copy);

        $this->assertTrue(Test_Tool::documentsAreEqual($emptyDoc, $copy, true));

        //todo copy contents must fail if types differ

        //delete recusively
        $shouldBeDeleted[] = $copy->getId();
        $childs = $copy->getChilds();
        foreach ($childs as $child) {
            $shouldBeDeleted[] = $child->getId();
        }

        $copy->delete();

        foreach ($shouldBeDeleted as $id) {
            $o = Document::getById($id);
            $this->assertFalse($o instanceof Document);
        }


    }

    public function testCopyAndDeleteAsset() {

        $assetList = new Asset_List();
        $assetList->setCondition("`filename` like '%_data%' and `type` = 'folder'");
        $assets = $assetList->load();
        $parent = $assets[0];
        $this->assertTrue($parent instanceof Asset_Folder);

        //remove childs if there are some
        if ($parent->hasChilds()) {
            foreach ($parent->getChilds() as $child) {
                $child->delete();
            }
        }

        $assetList = new Asset_List();
        $assetList->setCondition("`filename` like '%_data%' and `type` != 'folder'");
        $assets = $assetList->load();
        $image = $assets[0];

        $this->assertTrue($image instanceof Asset_Image);

        $this->assertFalse($parent->hasChilds());

        $service = new Asset_Service(User::getById(1));

        //copy as child
        $service->copyAsChild($parent, $parent);

        $this->assertTrue($parent->hasChilds());
        $this->assertTrue(count($parent->getChilds()) == 1);

        $childs = $parent->getChilds();
        $this->assertTrue(Test_Tool::assetsAreEqual($parent, $childs[0], true));

        //copy as child no. 2
        $service->copyAsChild($parent, $image);

        $this->assertTrue($parent->hasChilds());
        $this->assertTrue(count($parent->getChilds()) == 2);

        //copy recursivley
        $rootNode = Asset::getById(1);

        $copy = $service->copyRecursive($rootNode, $parent);

        $this->assertTrue($copy->hasChilds());

        $this->assertTrue(count($copy->getChilds()) == 2);

        $this->assertTrue(Test_Tool::assetsAreEqual($parent, $copy, true));


        //create unequal assets
         $asset1 = Asset_Image::create(1, array(
            "filename" => uniqid() . rand(10, 99) . ".jpg",
            "data" => file_get_contents(TESTS_PATH . "/resources/assets/images/image1" . ".jpg"),
            "userOwner" => 1
        ));

         $asset2 = Asset_Image::create(1, array(
            "filename" => uniqid() . rand(10, 99) . ".jpg",
            "data" => file_get_contents(TESTS_PATH . "/resources/assets/images/image2" . ".jpg"),
            "userOwner" => 1
        ));


        $this->assertFalse(Test_Tool::assetsAreEqual($asset1, $asset2, true));

        //copy contents
        $asset1 = $service->copyContents($asset1, $asset2);

        $this->assertTrue(Test_Tool::assetsAreEqual($asset1, $asset2, true));

        //todo copy contents must fail if types differ

        //delete recusively
        $shouldBeDeleted[] = $copy->getId();
        $childs = $copy->getChilds();
        foreach ($childs as $child) {
            $shouldBeDeleted[] = $child->getId();
        }

        $copy->delete();

        foreach ($shouldBeDeleted as $id) {
            $o = Asset::getById($id);
            $this->assertFalse($o instanceof Asset);
        }


    }


    

}
