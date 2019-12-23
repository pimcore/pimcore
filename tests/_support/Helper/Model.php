<?php

namespace Pimcore\Tests\Helper;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;

class Model extends AbstractDefinitionHelper
{
    /**
     * @inheritDoc
     */
    public function _beforeSuite($settings = [])
    {
        AbstractObject::setHideUnpublished(false);
        parent::_beforeSuite($settings);
    }


    /**
     * Set up a class which contains a classification store field
     *
     * @param array $params
     * @param string $name
     * @param string $filename
     * @return ClassDefinition|null
     */
    public function setupPimcoreClass_Csstore($params = [], $name = "csstore", $filename = 'classificationstore.json') {

        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel("root");
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName("MyLayout");
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName("Layout");
            $rootPanel->addChild($panel);

            $csField = $this->createDataChild("classificationstore", "csstore");
            $csField->setStoreId($params["storeId"]);
            $panel->addChild($csField);

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename);
        }
        return $class;
    }

    /**
     * Set up a class which (hopefully) contains all data types
     *
     * @param string $name
     * @param string $filename
     * @return ClassDefinition|null
     * @throws \Exception
     */
    public function setupPimcoreClass_Unittest($name = "unittest", $filename = 'class-import.json') {

        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel("root");
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName("MyLayout");
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName("Layout");
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild("date"));
            $panel->addChild($this->createDataChild("manyToOneRelation", "lazyHref")
                ->setLazyLoading(true)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild("manyToManyRelation", "lazyMultihref")
                ->setLazyLoading(true)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild("manyToManyObjectRelation", "lazyObjects")
                ->setLazyLoading(true)
                ->setClasses([]));

            $panel->addChild($this->createDataChild("manyToOneRelation", "href")
                ->setLazyLoading(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild("manyToManyRelation", "multihref")
                ->setLazyLoading(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild("manyToManyObjectRelation", "objects")
                ->setLazyLoading(false)
                ->setClasses([]));

            $panel->addChild($this->createDataChild("advancedManyToManyObjectRelation", "objectswithmetadata")
                ->setLazyLoading(false)
                ->setAllowedClassId($name)
                ->setClasses([])
                ->setColumns([ ["position" => 1, "key" => "meta1", "type" => "text", "label" => "label1"],
                    ["position" => 2, "key" => "meta2", "type" => "text", "label" => "label2"]]));

            $panel->addChild($this->createDataChild("slider"));
            $panel->addChild($this->createDataChild("numeric", "number"));
            $panel->addChild($this->createDataChild("geopoint", "point"));
            $panel->addChild($this->createDataChild("geobounds", "bounds"));
            $panel->addChild($this->createDataChild("geopolygon", "poly"));
            $panel->addChild($this->createDataChild("datetime"));
            $panel->addChild($this->createDataChild("time"));
            $panel->addChild($this->createDataChild("input"));
            $panel->addChild($this->createDataChild("password"));
            $panel->addChild($this->createDataChild("textarea"));
            $panel->addChild($this->createDataChild("wysiwyg"));
            $panel->addChild($this->createDataChild("select")->setOptions([
                ["key" => "Selection 1", "value" => "1"],
                ["key" => "Selection 2", "value" => "2"]]));

            $panel->addChild($this->createDataChild("multiselect")->setOptions([
                ["key" => "Katze", "value" => "cat"],
                ["key" => "Kuh", "value" => "cow"],
                ["key" => "Tiger", "value" => "tiger"],
                ["key" => "Schwein", "value" => "pig"],
                ["key" => "Esel", "value" => "donkey"],
                ["key" => "Affe", "value" => "monkey"],
                ["key" => "Huhn", "value" => "chicken"]
            ]));

            $panel->addChild($this->createDataChild("country"));
            $panel->addChild($this->createDataChild("countrymultiselect", "countries"));
            $panel->addChild($this->createDataChild("language", "languagex"));
            $panel->addChild($this->createDataChild("languagemultiselect", "languages"));
            $panel->addChild($this->createDataChild("user"));
            $panel->addChild($this->createDataChild("link"));
            $panel->addChild($this->createDataChild("image"));
            $panel->addChild($this->createDataChild("hotspotimage"));
            $panel->addChild($this->createDataChild("checkbox"));
            $panel->addChild($this->createDataChild("table"));
            $panel->addChild($this->createDataChild("structuredTable", "structuredtable")
                ->setCols([
                    ["position" => 1, "key" => "col1", "type" => "number", "label" => "collabel1"],
                    ["position" => 2, "key" => "col2", "type" => "text", "label" => "collabel2"]
                ])
                ->setRows([
                    ["position" => 1, "key" => "row1", "label" => "rowlabel1"],
                    ["position" => 2, "key" => "row2", "label" => "rowlabel2"],
                    ["position" => 3, "key" => "row3", "label" => "rowlabel3"]
                ])
            );
            $panel->addChild($this->createDataChild("fieldcollections", "fieldcollection")
                ->setAllowedTypes(["unittestfieldcollection"]));
            $panel->addChild($this->createDataChild("reverseManyToManyObjectRelation", "nonowner"));
            $panel->addChild($this->createDataChild("fieldcollections", "myfieldcollection")
                ->setAllowedTypes(["unittestfieldcollection"]));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName("localizedfields");
            $lFields->addChild($this->createDataChild("input", "linput"));
            $lFields->addChild($this->createDataChild("textarea", "ltextarea"));
            $lFields->addChild($this->createDataChild("wysiwyg", "lwysiwyg"));
            $lFields->addChild($this->createDataChild("numeric", "lnumber"));
            $lFields->addChild($this->createDataChild("slider", "lslider"));
            $lFields->addChild($this->createDataChild("date", "ldate"));
            $lFields->addChild($this->createDataChild("datetime", "ldatetime"));
            $lFields->addChild($this->createDataChild("time", "ltime"));
            $lFields->addChild($this->createDataChild("select", "lselect")->setOptions([
                ["key" => "one", "value" => "1"],
                ["key" => "two", "value" => "2"]]));

            $lFields->addChild($this->createDataChild("multiselect", "lmultiselect")->setOptions([
                ["key" => "one", "value" => "1"],
                ["key" => "two", "value" => "2"]]));
            $lFields->addChild($this->createDataChild("countrymultiselect", "lcountries"));
            $lFields->addChild($this->createDataChild("languagemultiselect", "llanguages"));
            $lFields->addChild($this->createDataChild("table", "ltable"));
            $lFields->addChild($this->createDataChild("image", "limage"));
            $lFields->addChild($this->createDataChild("checkbox", "lcheckbox"));
            $lFields->addChild($this->createDataChild("link", "llink"));
            $lFields->addChild($this->createDataChild("manyToManyObjectRelation", "lobjects")
                ->setLazyLoading(false)
                ->setClasses([]));

            $lFields->addChild($this->createDataChild("manyToManyRelation", "lmultihrefLazy")
                ->setLazyLoading(true)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($lFields);
            $panel->addChild($this->createDataChild("objectbricks", "mybricks"));

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename);
        }
        return $class;
    }


    /**
     * Used for inheritance tests
     *
     * @param string $name
     * @param string $filename
     * @return ClassDefinition|null
     * @throws \Exception
     */
    public function setupPimcoreClass_Inheritance($name = "inheritance", $filename = 'inheritance.json') {

        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel("root");
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName("MyLayout");
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName("Layout");
            $rootPanel->addChild($panel);

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName("localizedfields");
            $lFields->addChild($this->createDataChild("input"));
            $lFields->addChild($this->createDataChild("textarea"));
            $lFields->addChild($this->createDataChild("wysiwyg"));

            $otherPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName("Layout");
            $otherPanel->addChild($this->createDataChild("input", "normalinput"));
            $otherPanel->addChild($this->createDataChild("image", "yx"));
            $otherPanel->addChild($this->createDataChild("slider"));
            $otherPanel->addChild($this->createDataChild("manyToManyObjectRelation", "relationobjects")
                ->setLazyLoading(false)
                ->setClasses([]));

            $panel->addChild($lFields);
            $panel->addChild($otherPanel);
            $panel->addChild($this->createDataChild("objectbricks", "mybricks"));

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename, true);
        }
        return $class;
    }


    /**
     * @param string $name
     * @param ClassDefinition\Layout $layout
     * @param string $filename
     * @return ClassDefinition
     * @return $inheritanceAllowed
     */
    protected function createClass($name, $layout, $filename, $inheritanceAllowed = false) {
        $cm = $this->getClassManager();
        $def = new ClassDefinition();
        $def->setName($name);
        $def->setLayoutDefinitions($layout);
        $def->setAllowInherit($inheritanceAllowed);
        $json = ClassDefinition\Service::generateClassDefinitionJson($def);
        $cm->saveJson($filename, $json);
        $class = $cm->setupClass($name, $filename);
        return $class;
    }

    /**
     * Initialize widely used class definitions
     */
    public function initializeDefinitions()
    {
        $cm = $this->getClassManager();

        $cm->setupFieldcollection('unittestfieldcollection', 'fieldcollection-import.json');

        $this->setupPimcoreClass_Unittest();
        $this->setupPimcoreClass_Inheritance();

        $cm->setupObjectbrick('unittestBrick', 'brick-import.json');
    }
}
