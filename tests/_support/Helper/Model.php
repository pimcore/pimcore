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
     * @param $filename
     */
    public function createUnittestClass($filename) {

        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        $name = "unittest";

        if (!$cm->hasClass($name)) {


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

            $def = new ClassDefinition();
            $def->setName($name);
            $def->setLayoutDefinitions($root);
            $json = ClassDefinition\Service::generateClassDefinitionJson($def);
            $cm->saveJson($filename,$json);
        }

    }


    /**
     * Initialize mode class definitions
     */
    public function initializeDefinitions()
    {
        $cm = $this->getClassManager();

        $cm->setupFieldcollection('unittestfieldcollection', 'fieldcollection-import.json');

        $this->createUnittestClass('class-import.json');
        $unittestClass = $this->setupUnittestClass('unittest', 'class-import.json');

        $allFieldsClass = $this->setupUnittestClass('allfields', 'class-allfields.json');

        $cm->setupClass('inheritance', 'inheritance.json');

        $cm->setupObjectbrick('unittestBrick', 'brick-import.json');
    }

    /**
     * Setup standard Unittest class
     *
     * @param string $name
     * @param string $file
     *
     * @return ClassDefinition
     */
    public function setupUnittestClass($name = 'unittest', $file = 'class-import.json')
    {

        $cm = $this->getClassManager();

        if (!$cm->hasClass($name)) {
            /** @var ClassDefinition $class */
            $class = $cm->setupClass($name, $file);

            /** @var ClassDefinition\Data\ObjectsMetadata $fd */
            $fd = $class->getFieldDefinition('objectswithmetadata');
            if ($fd) {
                $fd->setAllowedClassId($class->getName());
                $class->save();
            }

            return $class;
        }

        return $cm->getClass($name);
    }
}
