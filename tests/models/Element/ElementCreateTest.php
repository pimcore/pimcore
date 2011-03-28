<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class Element_ElementCreateTest extends PHPUnit_Framework_TestCase
{


    /**
     * makes sure that objects with invalid keys cannot be created
     * @return void
     * @expectedException Exception
     */
    public function testInvalidObjectKey()
    {
        $folder = Object_Folder::create(array(
                                             "o_parentId" => 1,
                                             "o_creationDate" => time(),
                                             "o_key" => "invalid name"
                                        ));

        $folder->setCreationDate(time());
        $folder->setUserOwner(1);
        $folder->setUserModification(1);

        $folder->save();
    }

    /**
     * creates a object folder "/unittestobjects" to contain objects for coming tests
     */
    public function testObjectFolderCreate()
    {

        $folder = $this->createRandomObject("folder");
        $this->assertTrue($folder->getId() > 0);

        $folder->setKey($folder->getKey() . "_data");
        $folder->setProperties($this->getRandomProperties("object"));

        $folder->save();

        $refetch = Object_Folder::getById($folder->getId());
        //$this->assertTrue($refetch instanceof Object_Folder);

        $this->assertTrue(Test_Tool::objectsAreEqual($folder, $refetch, false));

    }

    /**
     * makes sure, that the creation of objects with duplicate paths is not possible
     * @expectedException Exception
     * @depends testObjectFolderCreate
     */
    public function testDuplicateObjectPath()
    {

        $id = uniqid();

        $folder = Object_Folder::create(array(
                                             "o_parentId" => 1,
                                             "o_creationDate" => time(),
                                             "o_key" => $id
                                        ));

        $folder->save();

        $folder = Object_Folder::create(array(
                                             "o_parentId" => 1,
                                             "o_creationDate" => time(),
                                             "o_key" => $id
                                        ));

        $folder->save();
    }


    /**
     * @expectedException Exception
     */
    public function testInvalidAssetKey()
    {
        Asset::create(1, array(
                              "filename" => "invalid name",
                              "type" => "folder",
                              "userOwner" => 1,
                              "userModification" => 1
                         ));
    }


    /**
     * creates an asset folder "unittestassets" to hold assets used later in tests
     * @return void
     */
    public function testAssetFolderCreate()
    {
        $asset = Asset_Folder::create(1, array(
                                              "filename" => uniqid() . "_data",
                                              "type" => "folder",
                                              "userOwner" => 1
                                         ));


        $this->assertTrue($asset->getId() > 0);

        //properties
        $asset->setProperties($this->getRandomProperties("asset"));
        $asset->save();

        $refetch = Asset_Folder::getById($asset->getId());

        $this->assertTrue(Test_Tool::assetsAreEqual($asset, $refetch, false));
    }

    /**
     * makes sure, that the creation of assets with duplicate paths is not possible
     * @expectedException Exception
     * @depends testAssetFolderCreate
     */
    public function testDuplicateAssetPath()
    {

        $key = uniqid();

        Asset::create(1, array(
                              "filename" => $key,
                              "type" => "folder",
                              "userOwner" => 1,
                              "userModification" => 1
                         ));

        Asset::create(1, array(
                              "filename" => $key,
                              "type" => "folder",
                              "userOwner" => 1,
                              "userModification" => 1
                         ));
    }


    /**
     * adds an asset image to the unittestassets folder
     * @depends  testAssetFolderCreate
     */
    public function testAssetImageCreate()
    {

        $asset = $this->createRandomAssetImage();

        $this->assertTrue($asset->getId() > 0);
        $asset->setFilename($asset->getKey() . "_data");
        $asset->setProperties($this->getRandomProperties("asset"));
        $asset->save();

        $refetch = Asset::getById($asset->getId());

        //$this->assertTrue($refetch instanceof Asset_Image);

        $this->assertTrue(Test_Tool::assetsAreEqual($asset, $refetch, false));


    }


    //TODO other asset types - does that make sense? if image works, the rest should too.

    /**
     * @expectedException Exception
     */
    public function testInvalidDocumentKey()
    {
        $createValues = array(
            "userOwner" => 1,
            "userModification" => 1,
            "key" => "invalid name"
        );

        Document_Folder::create(1, $createValues);
    }

    /**
     * creates a document folder to hold all documents for later tests
     * @return void
     */
    public function testDocumentFolderCreate()
    {

        $document = $this->createRandomDocument("folder");

        $this->assertTrue($document->getId() > 0);

        $document->setKey($document->getKey() . "_data");
        $document->setProperties($this->getRandomProperties("document"));
        $document->save();

        $refetch = Document_Folder::getById($document->getId());
        //$this->assertTrue($refetch instanceof Document_Folder);

        $this->assertTrue(Test_Tool::documentsAreEqual($document, $refetch, false));

    }

    /**
     * makes sure, that the creation of assets with duplicate paths is not possible
     * @expectedException Exception
     * @depends testDocumentFolderCreate
     */
    public function testDuplicateDocumentPath()
    {


        $createValues = array(
            "userOwner" => 1,
            "userModification" => 1,
            "key" => uniqid()
        );
        Document_Folder::create(1, $createValues);
        Document_Folder::create(1, $createValues);
    }

    /**
     * creates a new document
     * @return void
     */
    public function testDocumentPageCreate()
    {


        $document = $this->createRandomDocument("page");

        $this->assertTrue($document->getId() > 0);

        $document->setKey($document->getKey() . "_data");
        $document->setProperties($this->getRandomProperties("document"));

        $document->setName("My document name");
        $document->setKeywords("document unittest");
        $document->setTitle("My unittest document title");
        $document->setDescription("This is a test document description");

        $this->addDataToDocument($document);

        $document->save();

        $refetch = Document_Page::getById($document->getId());
        //$this->assertTrue($refetch instanceof Document_Page);

        $this->assertTrue(Test_Tool::documentsAreEqual($document, $refetch, false));

    }

    /**
     * creates a new document
     * @return void
     */
    public function testDocumentSnippetCreate()
    {
        $document = $this->createRandomDocument("snippet");

        $this->assertTrue($document->getId() > 0);

        $document->setKey($document->getKey() . "_data");
        $document->setProperties($this->getRandomProperties("document"));
        $document->save();

        $refetch = Document_Snippet::getById($document->getId());
        //$this->assertTrue($refetch instanceof Document_Snippet);

        //todo data

        $this->assertTrue(Test_Tool::documentsAreEqual($document, $refetch, false));
    }

    /**
     * creates a new document
     * @return void
     * @depends testDocumentPageCreate
     */
    public function testDocumentLinkCreate()
    {
        $document = $this->createRandomDocument("link");

        $this->assertTrue($document->getId() > 0);

        $document->setKey($document->getKey() . "_data");
        $document->setProperties($this->getRandomProperties("document"));

        $refetch = Document_Link::getById($document->getId());
        //$this->assertTrue($refetch instanceof Document_Link);

        $linkedDoc = $this->createRandomDocument("page");
        $data["linktype"] = "internal";
        $data["internalType"] = "document";
        $data["internal"] = $linkedDoc->getId();
        $document->setObject($linkedDoc);

        $document->setValues($data);
        $document->save();
        $this->assertTrue(Test_Tool::documentsAreEqual($document, $refetch, false));
    }

    /**
     * creates 5 empty objects of the type "unittest"
     * @depends  testObjectFolderCreate
     */
    public function testObjectConcreteCreate()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createObjectConcrete("unittest", "_data", 3);

        }

    }

    public function testObjectConcreteManyCreate()
    {

    }

    /**
     * @param  $fieldDefintion
     * @param string $language
     * @param Asset $refAsset
     * @return void
     */
    protected function getLocalizedFieldDataFor($fieldDefintion,$language,$refAsset){
            $name = $fieldDefintion->getName();
            $type = $fieldDefintion->getFieldType();

            $class = "Object_Class_Data_" . ucfirst($type);

            $this->assertTrue(class_exists($class));

            $data = new $class();


            if ($data instanceof Object_Class_Data_Checkbox) {
                return true;
            } else if ($data instanceof Object_Class_Data_Time) {
                return "18:00";
            } else if ($data instanceof Object_Class_Data_Password) {
                return "verySecret_".$language;
            } else if ($data instanceof Object_Class_Data_Input) {
                return "simple input ".$language;
            } else if ($data instanceof Object_Class_Data_Date) {
                return new Zend_Date();
            } else if ($data instanceof Object_Class_Data_Datetime) {
                return new Zend_Date();
            } else if ($data instanceof Object_Class_Data_Languagemultiselect){
                return array("de","en");
            }  else if ($data instanceof Object_Class_Data_Countrymultiselect){
               return array("AT","AU");
            } else if ($data instanceof Object_Class_Data_Multiselect) {
                return array(1,2);
            } else if ($data instanceof Object_Class_Data_Select) {
                return 2;
            }  else if ($data instanceof Object_Class_Data_Image) {
                return $refAsset;
            } else if ($data instanceof Object_Class_Data_Slider) {
                return 6;
            } else if ($data instanceof Object_Class_Data_Numeric) {
                return 1000;
            } else if ($data instanceof Object_Class_Data_Table) {
                return array(array("eins ".$language, "zwei ".$language, "drei ".$language), array(1, 2, 3), array("a", "b", "c"));
            } else if ($data instanceof Object_Class_Data_Textarea) {
                return $language. " Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.";
            } else if ($data instanceof Object_Class_Data_Wysiwyg) {
                return $language. "<p>This is some <strong>HTML</strong> content</p><ul><li>list one</li><li>list two</li></ul>";
            } else if ($data instanceof Object_Class_Data_Link) {
                $l = new Object_Data_Link();
                $l->setPath("http://www.pimcore.org");
                $l->setTitle($language. " pimcore.org");
                $l->setText($language. " pimcore.org");
                return $l;
            }

    }


    /**
     * @param  $fd
     * @return void
     */
    protected function setFieldData($object, $fd, $refDocument, $refAsset, $minAmountLazyRelations)
    {
        foreach ($fd as $field) {

            $name = $field->getName();
            $type = $field->getFieldType();

            $class = "Object_Class_Data_" . ucfirst($type);

            $this->assertTrue(class_exists($class));

            $data = new $class();

            $setter = "set" . ucfirst($name);

            if(!$field->isRelationType() or !$field->isRemoteOwner()){
                $this->assertTrue(method_exists($object, $setter));
            }


            if ($data instanceof Object_Class_Data_Checkbox) {
                $object->$setter(true);
            } else if ($data instanceof Object_Class_Data_Time) {
                $object->$setter("18:00");
            } else if ($data instanceof Object_Class_Data_Password) {
                $object->$setter("verySecret");
            } else if ($data instanceof Object_Class_Data_Input) {
                $object->$setter("simple input");
            } else if ($data instanceof Object_Class_Data_Date) {
                $object->$setter(new Zend_Date());
            } else if ($data instanceof Object_Class_Data_Datetime) {
                $object->$setter(new Zend_Date());
            } else if ($data instanceof Object_Class_Data_Href) {
                $object->$setter(Object_Abstract::getById($object->getId() - 1));
            } else if ($data instanceof Object_Class_Data_Objects and !$data->isRemoteOwner()) {
                $data = array();
                $o = Object_Abstract::getById($object->getId() - 1);
                if (!$o instanceof Object_Folder) {
                    $data[] = $o;
                }
                $data[] = Object_Abstract::getById($object->getId());
                $object->$setter($data);
            } else if ($data instanceof Object_Class_Data_Fieldcollections) {



                $items = new Object_Fieldcollection();
                $collectionA = Object_Fieldcollection_Definition::getByKey("collectionA");
                $itemDefinitions = $collectionA->getFieldDefinitions();

                    $item = new Object_Fieldcollection_Data_CollectionA();

                    $this->setFieldData($item, $itemDefinitions, $refDocument, $refAsset, $minAmountLazyRelations);
                    $items->add($item);


                $object->$setter($items);


            } else if ($data instanceof Object_Class_Data_Localizedfields) {

                $getter = "get" . ucfirst($name);
                $data = $object->getO_class()->getFieldDefinition("localizedfields");

                $localizedData = array();
                $validLanguages = Pimcore_Tool::getValidLanguages();
                foreach($validLanguages as $language){

                    foreach ($data->getFieldDefinitions() as $fd) {
                        $fieldData = $this->getLocalizedFieldDataFor($fd,$language,$refAsset);
                        $localizedData[$language][$fd->getName()] = $fieldData;
                    }
                }

                $object->$setter(new Object_Localizedfield($localizedData));


            } else if ($data instanceof Object_Class_Data_Multihref) {
                $data = array();
                $data[] = Object_Abstract::getById($object->getId() - 1);
                $data[] = $refAsset;
                //dummy for checking if relation is saved twice
                $data[] = Asset::getById($refAsset->getId());
                $data[] = $refDocument;

                $fd = $object->geto_Class()->getFieldDefinition($name);
                if ($fd->getLazyLoading()) {
                    for ($i = 1; $i <= $minAmountLazyRelations; $i++) {
                        $data[] = $this->createRandomObject("unittest");
                    }
                }

                $object->$setter($data);
            } else if ($data instanceof Object_Class_Data_Languagemultiselect){
                $object->$setter(array("de","en"));
            }  else if ($data instanceof Object_Class_Data_Countrymultiselect){
                $object->$setter(array("AT","AU"));   
            } else if ($data instanceof Object_Class_Data_Multiselect) {
                $object->$setter(array("cat", "cow"));
            } else if ($data instanceof Object_Class_Data_User) {
                //create a user to set
                $user = new User();
                $user->setUsername(uniqid());
                $user->setParentId(0);
                $user->setHasCredentials(true);
                $user->setPassword(md5("unitTestUser"));
                $user->save();
                $object->$setter($user->getId());
            } else if ($data instanceof Object_Class_Data_Language) {
                $object->$setter("en");
            } else if ($data instanceof Object_Class_Data_Country) {
                $object->$setter("AU");
            } else if ($data instanceof Object_Class_Data_Select) {
                $object->$setter(2);
            } else if ($data instanceof Object_Class_Data_Geobounds) {
                $object->$setter(new Object_Data_Geobounds(new Object_Data_Geopoint(150.96588134765625, -33.704920213014425), new Object_Data_Geopoint(150.60333251953125, -33.893217379440884)));
            } else if ($data instanceof Object_Class_Data_Geopoint) {
                $object->$setter(new Object_Data_Geopoint(151.2111111, -33.8599722));
            } else if ($data instanceof Object_Class_Data_Geopolygon) {
                $data = array(new Object_Data_Geopoint(150.54428100585938, -33.464671118242684), new Object_Data_Geopoint(150.73654174804688, -33.913733814316245), new Object_Data_Geopoint(151.2542724609375, -33.9946115848146));
                $object->$setter($data);
            } else if ($data instanceof Object_Class_Data_Image) {
                $object->$setter($refAsset);
            } else if ($data instanceof Object_Class_Data_Slider) {
                $object->$setter(6);
            } else if ($data instanceof Object_Class_Data_Numeric) {
                $object->$setter(12000);
            } else if ($data instanceof Object_Class_Data_Table) {
                $object->$setter(array(array("eins", "zwei", "drei"), array(1, 2, 3), array("a", "b", "c")));
            } else if ($data instanceof Object_Class_Data_Textarea) {
                $object->$setter("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.");
            } else if ($data instanceof Object_Class_Data_Wysiwyg) {
                $object->$setter("<p>This is some <strong>HTML</strong> content</p><ul><li>list one</li><li>list two</li></ul>");
            } else if ($data instanceof Object_Class_Data_Link) {
                $l = new Object_Data_Link();
                $l->setPath("http://www.pimcore.org");
                $l->setTitle("pimcore.org");
                $l->setText("pimcore.org");
                $object->$setter($l);
            }

        }
    }

    protected function createObjectConcrete($class, $keySuffix, $minAmountLazyRelations)
    {
        $document = $this->createRandomDocument("page");

        $asset = $this->createRandomAssetImage();


        $object = $this->createRandomObject($class);
        $object->setKey($object->getKey() . $keySuffix);

        $this->assertTrue($object->getId() > 0);
        //$objectFetched = Object_Unittest::getById($object->getId());
        //$this->assertTrue($objectFetched instanceof Object_Unittest);


        $fd = $object->getClass()->getFieldDefinitions();
        $this->setFieldData($object, $fd, $document, $asset, $minAmountLazyRelations);

        //properties
        $object->setProperties($this->getRandomProperties("object"));

        $object->save();

        //new objects must be unpublished
        $refetch = Object_Abstract::getById($object->getId());

        $this->assertFalse($refetch->isPublished());
        $this->assertTrue(Test_Tool::objectsAreEqual($object, $refetch, false));


        return $object;
    }

    /**
     * creates a random object with no data / properties set
     * @param string $type folder,unittest
     * @return Object_Abstract
     */
    protected function createRandomObject($type)
    {
        $class = "Object_" . ucfirst($type);
        $object = new $class();
        $object->setParentId(1);
        $object->setUserOwner(1);
        $object->setUserModification(1);
        $object->setCreationDate(time());
        $object->setKey(uniqid() . rand(10, 99));
        $object->save();

        return $object;
    }

    /**
     * creates a random document page with no data / properties set
     * @param string type folder,page,snippet
     * @return Document_Page
     */
    protected function createRandomDocument($type)
    {
        $class = "Document_" . ucfirst($type);
        $doc = $class::create(1, array(
                                      "userOwner" => 1,
                                      "key" => uniqid() . rand(10, 99)
                                 ));
        return $doc;
    }

    /**
     * create a random asset image with no properties set
     * @return Asset
     */
    protected function createRandomAssetImage()
    {
        $asset = Asset_Image::create(1, array(
                                             "filename" => uniqid() . rand(10, 99) . ".jpg",
                                             "data" => file_get_contents(TESTS_PATH . "/resources/assets/images/image" . rand(1, 4) . ".jpg"),
                                             "userOwner" => 1
                                        ));
        return $asset;
    }

    /**
     * create a random asset image with no properties set
     * @return Asset
     */
    protected function createRandomAssetVideo()
    {
        $asset = Asset_Video::create(1, array(
                                             "filename" => uniqid() . rand(10, 99) . ".wmv",
                                             "data" => file_get_contents(TESTS_PATH . "/resources/assets/video/recyclebin.wmv"),
                                             "userOwner" => 1
                                        ));
        return $asset;
    }


    /**
     * @param  string $ctype
     *
     * @return array
     */
    protected function getRandomProperties($ctype)
    {


        $document = $this->createRandomDocument("snippet");

        $asset = $this->createRandomAssetImage();

        $object = $this->createRandomObject("unittest");

        $properties = array();

        // object property
        $property = new Property();
        $property->setType("object");
        $property->setName("object");
        $property->setCtype($ctype);
        $property->setDataFromEditmode($object->getFullPath());
        $property->setInheritable(true);
        $properties["object"] = $property;

        // document property
        $property = new Property();
        $property->setType("document");
        $property->setName("document");
        $property->setCtype($ctype);
        $property->setDataFromEditmode($document->getFullPath());
        $property->setInheritable(true);
        $properties["document"] = $property;

        // asset property
        $property = new Property();
        $property->setType("asset");
        $property->setName("asset");
        $property->setCtype($ctype);
        $property->setDataFromEditmode($asset->getFullPath());
        $property->setInheritable(true);
        $properties["asset"] = $property;

        // text property
        $property = new Property();
        $property->setType("text");
        $property->setName("text");
        $property->setCtype($ctype);
        $property->setDataFromEditmode("hallo property");
        $property->setInheritable(true);
        $properties["text"] = $property;

        // bool  property
        $property = new Property();
        $property->setType("bool");
        $property->setName("bool");
        $property->setCtype($ctype);
        $property->setDataFromEditmode(true);
        $property->setInheritable(true);
        $properties["bool"] = $property;

        // date  property
        $property = new Property();
        $property->setType("date");
        $property->setName("date");
        $property->setCtype($ctype);
        $property->setDataFromEditmode(new Zend_Date());
        $property->setInheritable(true);
        $properties["date"] = $property;

        return $properties;

    }

    protected function addDataToDocument(Document $document)
    {


        $doc = $this->createRandomDocument("page");
        $snippet = $this->createRandomDocument("snippet");
        $image = $this->createRandomAssetImage();
        $video = $this->createRandomAssetVideo();

        $dataJson = '{"textarea": {
                "data": "simple text ...",
                "type": "textarea"
            },
            "wysiwyg": {
                "data": "<p>\n\tSome <strong>wysiwyg </strong>text</p>\n<p>\n\t </p>\n<br />\n",
                "type": "wysiwyg"
            },
            "video": {
                "data": {
                    "id": "' . $video->getId() . '",
                    "type": "asset"
                },
                "type": "video"
            },
            "tableName": {
                "data": [
                    [
                        "Value 1",
                        "Value 2",
                        "Value 3"
                    ],
                    [
                        "this",
                        "is a",
                        "test"
                    ]
                ],
                "type": "table"
            },
            "snippet": {
                "data": "' . $snippet->getId() . '",
                "type": "snippet"
            },
            "select": {
                "data": "option1",
                "type": "select"
            },
            "renderlet": {
                "data": {
                    "id": "' . $image->getId() . '",
                    "type": "asset",
                    "subtype": "image",
                    "controller": "default",
                    "action": "default",
                    "title": "Drag here",
                    "height": 400,
                    "name": "pimcore_editable_renderlet_editable",
                    "border": false,
                    "bodyStyle": "min-height: 40px;"
                },
                "type": "renderlet"
            },
            "numeric": {
                "data": 2,
                "type": "numeric"
            },
            "multiselect": {
                "data": "",
                "type": "multiselect"
            },
            "link": {
                "data": {
                    "text": "' . $doc->getFullPath() . '",
                    "path": "",
                    "target": "",
                    "parameters": "",
                    "anchor": "",
                    "title": "",
                    "accesskey": "",
                    "rel": "",
                    "tabindex": "",
                    "type": "internal"
                },
                "type": "link"
            },
            "input": {
                "data": "some input",
                "type": "input"
            },
            "image": {
                "data": {
                    "id": "' . $image->getId() . '",
                    "path": "' . $image->getFullPath() . '",
                    "alt": ""
                },
                "type": "image"
            },
            "checkbox": {
                "data": true,
                "type": "checkbox"
            },
            "date": {
                "data": "2010-12-21T00:00:00",
                "type": "date"
            },
            "href": {
                "data": {
                    "id": "' . $doc->getId() . '",
                    "type": "document",
                    "subtype": "page"
                },
                "type": "href"
            }}';


        $data = Zend_Json::decode($dataJson);
        foreach ($data as $name => $value) {
            $d = $value["data"];
            $type = $value["type"];
            $document->setRawElement($name, $type, $d);

        }

    }
}
