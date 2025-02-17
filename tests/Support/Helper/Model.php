<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Support\Helper;

use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

class Model extends AbstractDefinitionHelper
{
    public function _beforeSuite(array $settings = []): void
    {
        DataObject::setHideUnpublished(false);
        parent::_beforeSuite($settings);
    }

    /**
     * Set up a class which contains a classification store field
     */
    public function setupPimcoreClass_Csstore(array $params = [], string $name = 'csstore', string $filename = 'classificationstore.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $csField = $this->createDataChild('classificationstore', 'csstore');
            $csField->setStoreId($params['storeId']);
            $panel->addChild($csField);

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename);
        }

        return $class;
    }

    /**
     * Set up a class used for lazy loading tests.
     *
     * @throws Exception
     */
    public function setupPimcoreClass_LazyLoading(string $name = 'LazyLoading', string $filename = 'lazyloading/class_LazyLoading_export.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('input'));
            $panel->addChild($this->createDataChild('manyToManyObjectRelation', 'objects')
                ->setClasses(['RelationTest'])
            );

            $panel->addChild($this->createDataChild('manyToOneRelation', 'relation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'relations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'advancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $panel->addChild($this->createDataChild('advancedManyToManyRelation', 'advancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'meta'],
                ]));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');

            $lFields->addChild($this->createDataChild('manyToManyObjectRelation', 'lobjects')
                ->setClasses(['RelationTest'])
            );

            $lFields->addChild($this->createDataChild('manyToOneRelation', 'lrelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('manyToManyRelation', 'lrelations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'ladvancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $lFields->addChild($this->createDataChild('advancedManyToManyRelation', 'ladvancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'meta'],
                ]));

            $lFields->addChild($this->createDataChild('manyToManyObjectRelation', 'lobjects')
                ->setClasses(['RelationTest'])
            );

            $block = new ClassDefinition\Data\Block();
            $block->setName('testblock');

            $block->addChild($this->createDataChild('manyToManyObjectRelation', 'blockobjects')
                ->setClasses(['RelationTest'])
            );

            $block->addChild($this->createDataChild('manyToOneRelation', 'blockrelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $block->addChild($this->createDataChild('manyToManyRelation', 'blockrelations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $block->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'blockadvancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $block->addChild($this->createDataChild('advancedManyToManyRelation', 'blockadvancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $blockLazyLoaded = new ClassDefinition\Data\Block();
            $blockLazyLoaded->setName('testblockLazyloaded');
            $blockLazyLoaded->setLazyLoading(true);

            $blockLazyLoaded->addChild($this->createDataChild('manyToManyObjectRelation', 'blockobjectsLazyLoaded')
                ->setClasses(['RelationTest'])
            );

            $blockLazyLoaded->addChild($this->createDataChild('manyToOneRelation', 'blockrelationLazyLoaded')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $blockLazyLoaded->addChild($this->createDataChild('manyToManyRelation', 'blockrelationsLazyLoaded')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $blockLazyLoaded->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'blockadvancedObjectsLazyLoaded')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $blockLazyLoaded->addChild($this->createDataChild('advancedManyToManyRelation', 'blockadvancedRelationsLazyLoaded')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($lFields);
            $panel->addChild($block);
            $panel->addChild($blockLazyLoaded);

            $panel->addChild($this->createDataChild('fieldcollections', 'fieldcollection')
                ->setAllowedTypes(['LazyLoadingTest', 'LazyLoadingLocalizedTest']));

            $panel->addChild($this->createDataChild('objectbricks', 'bricks'));

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename, true, 'LL');
        }

        return $class;
    }

    /**
     * Set up a class used for relation tests.
     *
     * @throws Exception
     */
    public function setupPimcoreClass_RelationTest(string $name = 'RelationTest', string $filename = 'relations/class_RelationTest_export.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('input', 'someAttribute'));
            $panel->addChild($this->createDataChild('input', 'someAttribute1'));
            $panel->addChild($this->createDataChild('input', 'someAttribute2'));
            $panel->addChild($this->createDataChild('input', 'someAttribute3'));
            $panel->addChild($this->createDataChild('input', 'someAttribute4'));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');
            $lFields->addChild($this->createDataChild('input', 'xsomeAttribute'));
            $lFields->addChild($this->createDataChild('input', 'xsomeAttribute1'));
            $lFields->addChild($this->createDataChild('input', 'xsomeAttribute2'));
            $lFields->addChild($this->createDataChild('input', 'xsomeAttribute3'));
            $lFields->addChild($this->createDataChild('input', 'xsomeAttribute4'));
            $panel->addChild($lFields);

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename);
        }

        return $class;
    }

    /**
     * Set up a class used for relation tests.
     *
     * @throws Exception
     */
    public function setupPimcoreClass_MultipleAssignments(string $name = 'MultipleAssignments', string $filename = 'relations/class_MultipleAssignments_export.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('advancedManyToManyRelation', 'onlyOneManyToMany')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($this->createDataChild('advancedManyToManyRelation', 'multipleManyToMany')
                ->setAllowMultipleAssignments(true)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'onlyOneManyToManyObject')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'multipleManyToManyObject')
                ->setAllowMultipleAssignments(true)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename, true);
        }

        return $class;
    }

    /**
     * Set up a class used for Block Test.
     *
     * @throws Exception
     */
    public function setupPimcoreClass_Block(string $name = 'unittestBlock', string $filename = 'block-import.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $block = new ClassDefinition\Data\Block();
            $block->setName('testblock');

            $block->addChild($this->createDataChild('input', 'blockinput'));
            $block->addChild($this->createDataChild('link', 'blocklink'));
            $block->addChild($this->createDataChild('hotspotimage', 'blockhotspotimage'));

            $block->addChild($this->createDataChild('advancedManyToManyRelation', 'blockadvancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');

            $lblock = new ClassDefinition\Data\Block();
            $lblock->setName('ltestblock');

            $lblock->addChild($this->createDataChild('input', 'lblockinput'));
            $lblock->addChild($this->createDataChild('link', 'lblocklink'));
            $lblock->addChild($this->createDataChild('hotspotimage', 'lblockhotspotimage'));

            $lblock->addChild($this->createDataChild('advancedManyToManyRelation', 'lblockadvancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['unittest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'meta', 'type' => 'text', 'label' => 'meta'],
                ]));

            $lFields->addChild($lblock);

            $panel->addChild($block);
            $panel->addChild($lFields);
            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename, true);
        }

        return $class;
    }

    /**
     * Set up a class used for Link Test.
     *
     * @throws Exception
     */
    public function setupPimcoreClass_Link(string $name = 'unittestLink', string $filename = 'link-import.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new ClassDefinition\Layout\Panel();
            $panel = (new ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $link = new ClassDefinition\Data\Link();
            $link->setName('testlink');

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');

            $llink = new ClassDefinition\Data\Link();
            $llink->setName('ltestlink');

            $lFields->addChild($llink);

            $panel->addChild($link);
            $panel->addChild($lFields);
            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename, true);
        }

        return $class;
    }

    /**
     * Set up a class which (hopefully) contains all data types
     *
     * @throws Exception
     */
    public function setupPimcoreClass_Unittest(string $name = 'unittest', string $filename = 'class-import.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $calculatedValue = $this->createDataChild('calculatedValue');
            $calculatedValue->setCalculatorClass('@test.calculatorservice');
            $panel->addChild($calculatedValue);

            $calculatedValueExpression = $this->createDataChild('calculatedValue', 'calculatedValueExpression');
            $calculatedValueExpression->setCalculatorExpression("object.getFirstname() ~ ' some calc'");
            $calculatedValueExpression->setCalculatorType(ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_EXPRESSION);
            $panel->addChild($calculatedValueExpression);

            $calculatedValueExpressionConstant = $this->createDataChild('calculatedValue', 'calculatedValueExpressionConstant');
            $calculatedValueExpressionConstant->setCalculatorExpression("constant('PIMCORE_PROJECT_ROOT')");
            $calculatedValueExpressionConstant->setCalculatorType(ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_EXPRESSION);
            $panel->addChild($calculatedValueExpressionConstant);

            $panel->addChild($this->createDataChild('consent'));

            $panel->addChild($this->createDataChild('country'));
            $panel->addChild($this->createDataChild('countrymultiselect', 'countries'));

            $panel->addChild($this->createDataChild('date'));
            $panel->addChild($this->createDataChild('datetime'));
            $panel->addChild($this->createDataChild('dateRange'));

            $panel->addChild($this->createDataChild('email'));

            /** @var ClassDefinition\Data\EncryptedField $encryptedField */
            $encryptedField = $this->createDataChild('encryptedField');

            $encryptedField->setDelegateDatatype('input');
            $panel->addChild($encryptedField);

            $panel->addChild($this->createDataChild('externalImage'));

            $panel->addChild($this->createDataChild('firstname'));

            $panel->addChild($this->createDataChild('gender'));

            $panel->addChild($this->createDataChild('geopoint', 'point', false));
            $panel->addChild($this->createDataChild('geobounds', 'bounds', false));
            $panel->addChild($this->createDataChild('geopolygon', 'polygon', false));
            $panel->addChild($this->createDataChild('geopolyline', 'polyline', false));

            $panel->addChild($this->createDataChild('imageGallery'));
            $panel->addChild($this->createDataChild('input'));
            /** @var ClassDefinition\Data\Input $inputWithDefault */
            $inputWithDefault = $this->createDataChild('input', 'inputWithDefault');
            $inputWithDefault->setDefaultValue('default');
            $panel->addChild($inputWithDefault);
            /** @var ClassDefinition\Data\Input $mandatoryInputWithDefault */
            $mandatoryInputWithDefault = $this->createDataChild('input', 'mandatoryInputWithDefault', true);
            $mandatoryInputWithDefault->setDefaultValue('default');
            $panel->addChild($mandatoryInputWithDefault);

            $panel->addChild($this->createDataChild('manyToOneRelation', 'lazyHref')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'lazyMultihref')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyObjectRelation', 'lazyObjects')
                ->setClasses([]));

            $panel->addChild($this->createDataChild('manyToOneRelation', 'href')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'multihref')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyObjectRelation', 'objects')
                ->setClasses([]));

            $panel->addChild($this->createDataChild('inputQuantityValue'));
            $panel->addChild($this->createDataChild('quantityValue'));

            $panel->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'objectswithmetadata')
                ->setAllowedClassId($name)
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'meta1', 'type' => 'text', 'label' => 'label1'],
                    ['position' => 2, 'key' => 'meta2', 'type' => 'text', 'label' => 'label2'], ]));

            $panel->addChild($this->createDataChild('lastname'));

            $panel->addChild($this->createDataChild('numeric', 'number'));

            $passwordField = $this->createDataChild('password');
            $passwordField->setAlgorithm(ClassDefinition\Data\Password::HASH_FUNCTION_PASSWORD_HASH);
            $panel->addChild($passwordField);

            $panel->addChild($this->createDataChild('rgbaColor', 'rgbaColor', false));

            $panel->addChild($this->createDataChild('select')->setOptions([
                ['key' => 'Selection 1', 'value' => '1'],
                ['key' => 'Selection 2', 'value' => '2'], ]));

            $panel->addChild($this->createDataChild('slider'));

            $panel->addChild($this->createDataChild('textarea'));
            $panel->addChild($this->createDataChild('time'));

            $panel->addChild($this->createDataChild('wysiwyg'));

            $panel->addChild($this->createDataChild('video', 'video', false));

            $panel->addChild($this->createDataChild('multiselect')->setOptions([
                ['key' => 'Katze', 'value' => 'cat'],
                ['key' => 'Kuh', 'value' => 'cow'],
                ['key' => 'Tiger', 'value' => 'tiger'],
                ['key' => 'Schwein', 'value' => 'pig'],
                ['key' => 'Esel', 'value' => 'donkey'],
                ['key' => 'Affe', 'value' => 'monkey'],
                ['key' => 'Huhn', 'value' => 'chicken'],
            ]));

            $panel->addChild($this->createDataChild('language', 'languagex'));
            $panel->addChild($this->createDataChild('languagemultiselect', 'languages'));
            $panel->addChild($this->createDataChild('user'));
            $panel->addChild($this->createDataChild('link'));
            $panel->addChild($this->createDataChild('image'));
            $panel->addChild($this->createDataChild('hotspotimage'));
            $panel->addChild($this->createDataChild('checkbox'));
            $panel->addChild($this->createDataChild('booleanSelect'));
            $panel->addChild($this->createDataChild('table'));
            $panel->addChild($this->createDataChild('structuredTable', 'structuredtable', false)
                ->setCols([
                    ['position' => 1, 'key' => 'col1', 'type' => 'number', 'label' => 'collabel1'],
                    ['position' => 2, 'key' => 'col2', 'type' => 'text', 'label' => 'collabel2'],
                ])
                ->setRows([
                    ['position' => 1, 'key' => 'row1', 'label' => 'rowlabel1'],
                    ['position' => 2, 'key' => 'row2', 'label' => 'rowlabel2'],
                    ['position' => 3, 'key' => 'row3', 'label' => 'rowlabel3'],
                ])
            );
            $panel->addChild($this->createDataChild('fieldcollections', 'fieldcollection')
                ->setAllowedTypes(['unittestfieldcollection']));
            $panel->addChild($this->createDataChild('reverseObjectRelation', 'nonowner')->setOwnerClassName($name)->setOwnerFieldName('objects'));
            $panel->addChild($this->createDataChild('fieldcollections', 'myfieldcollection')
                ->setAllowedTypes(['unittestfieldcollection']));

            $panel->addChild($this->createDataChild('urlSlug')->setAction('MyController::myAction'));
            $panel->addChild($this->createDataChild('urlSlug', 'urlSlug2')->setAction('MyController::myAction'));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');
            $lFields->addChild($this->createDataChild('input', 'linput'));
            $lFields->addChild($this->createDataChild('textarea', 'ltextarea'));
            $lFields->addChild($this->createDataChild('wysiwyg', 'lwysiwyg'));
            $lFields->addChild($this->createDataChild('numeric', 'lnumber'));
            $lFields->addChild($this->createDataChild('slider', 'lslider'));
            $lFields->addChild($this->createDataChild('date', 'ldate'));
            $lFields->addChild($this->createDataChild('datetime', 'ldatetime'));
            $lFields->addChild($this->createDataChild('time', 'ltime'));
            $lFields->addChild($this->createDataChild('select', 'lselect')->setOptions([
                ['key' => 'one', 'value' => '1'],
                ['key' => 'two', 'value' => '2'], ]));

            $lFields->addChild($this->createDataChild('multiselect', 'lmultiselect')->setOptions([
                ['key' => 'one', 'value' => '1'],
                ['key' => 'two', 'value' => '2'], ]));
            $lFields->addChild($this->createDataChild('countrymultiselect', 'lcountries'));
            $lFields->addChild($this->createDataChild('languagemultiselect', 'llanguages'));
            $lFields->addChild($this->createDataChild('table', 'ltable'));
            $lFields->addChild($this->createDataChild('image', 'limage'));
            $lFields->addChild($this->createDataChild('checkbox', 'lcheckbox'));
            $lFields->addChild($this->createDataChild('link', 'llink'));
            $lFields->addChild($this->createDataChild('manyToManyObjectRelation', 'lobjects')
                ->setClasses([]));

            $lFields->addChild($this->createDataChild('manyToManyRelation', 'lmultihrefLazy')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('urlSlug', 'lurlSlug')->setAction('MyController::myLocalizedAction'));

            $panel->addChild($lFields);
            $panel->addChild($this->createDataChild('objectbricks', 'mybricks'));

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename);
        }

        return $class;
    }

    /**
     * Used for inheritance tests
     *
     * @throws Exception
     */
    public function setupPimcoreClass_Inheritance(string $name = 'inheritance', string $filename = 'inheritance.json'): ?DataObject\ClassDefinitionInterface
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$class = $cm->getClass($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');
            $lFields->addChild($this->createDataChild('input'));
            $lFields->addChild($this->createDataChild('textarea'));
            $lFields->addChild($this->createDataChild('wysiwyg'));

            $otherPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('Layout');
            $otherPanel->addChild($this->createDataChild('input', 'normalinput'));
            $otherPanel->addChild($this->createDataChild('image', 'yx'));
            $otherPanel->addChild($this->createDataChild('slider'));
            $otherPanel->addChild($this->createDataChild('manyToManyObjectRelation', 'relationobjects')
                ->setClasses([]));
            $panel->addChild($this->createDataChild('manyToOneRelation', 'relation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($lFields);
            $panel->addChild($otherPanel);
            $panel->addChild($this->createDataChild('objectbricks', 'mybricks'));

            $csField = $this->createDataChild('classificationstore', 'teststore');
            $csField->setStoreId(1);
            $panel->addChild($csField);

            $root->addChild($rootPanel);
            $class = $this->createClass($name, $root, $filename, true);
        }

        return $class;
    }

    protected function createClass(string $name, ClassDefinition\Layout $layout, string $filename, bool $inheritanceAllowed = false, ?string $id = null): DataObject\ClassDefinitionInterface
    {
        $cm = $this->getClassManager();
        $def = new ClassDefinition();

        if ($id !== null) {
            $def->setId($id);
        }
        $def->setName($name);
        $def->setLayoutDefinitions($layout);
        $def->setAllowInherit($inheritanceAllowed);
        $json = ClassDefinition\Service::generateClassDefinitionJson($def);
        $cm->saveJson($filename, $json);

        return $cm->setupClass($name, $filename);
    }

    /**
     * Sets up a Fieldcollection
     *
     * @throws Exception
     */
    public function setupFieldcollection_Unittestfieldcollection(string $name = 'unittestfieldcollection', string $filename = 'fieldcollection-import.json'): ?Definition
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$definition = $cm->getFieldcollection($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('input', 'fieldinput1'));
            $panel->addChild($this->createDataChild('input', 'fieldinput2'));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'fieldRelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'fieldRelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('advancedManyToManyRelation', 'advancedFieldRelation')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'fieldLazyRelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');

            $lFields->addChild($this->createDataChild('Input', 'linput'));

            $lFields->addChild($this->createDataChild('manyToOneRelation', 'lrelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($lFields);

            $root->addChild($rootPanel);
            $definition = $this->createFieldcollection($name, $root, $filename);
        }

        return $definition;
    }

    /**
     * Sets up a Fieldcollection for lazy loading tests
     *
     * @throws Exception
     */
    public function setupFieldcollection_LazyLoadingTest(string $name = 'LazyLoadingTest', string $filename = 'lazyloading/fieldcollection_LazyLoadingTest_export.json'): ?Definition
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$definition = $cm->getFieldcollection($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('manyToManyObjectRelation', 'objects')
                ->setClasses(['RelationTest'])
            );

            $panel->addChild($this->createDataChild('manyToOneRelation', 'relation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'relations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'advancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $panel->addChild($this->createDataChild('advancedManyToManyRelation', 'advancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'meta'],
                ]));

            $root->addChild($rootPanel);
            $definition = $this->createFieldcollection($name, $root, $filename);
        }

        return $definition;
    }

    /**
     * Sets up a Fieldcollection for localized lazy loading tests
     *
     * @throws Exception
     */
    public function setupFieldcollection_LazyLoadingLocalizedTest(string $name = 'LazyLoadingLocalizedTest', string $filename = 'lazyloading/fieldcollection_LazyLoadingLocalizedTest_export.json'): ?Definition
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$definition = $cm->getFieldcollection($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('input', 'normalInput'));

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');

            $lFields->addChild($this->createDataChild('input', 'linput'));

            $lFields->addChild($this->createDataChild('manyToManyObjectRelation', 'lobjects')
                ->setClasses(['RelationTest'])
            );

            $lFields->addChild($this->createDataChild('manyToOneRelation', 'lrelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('manyToManyRelation', 'lrelations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'ladvancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $lFields->addChild($this->createDataChild('advancedManyToManyRelation', 'ladvancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($lFields);

            $root->addChild($rootPanel);
            $definition = $this->createFieldcollection($name, $root, $filename);
        }

        return $definition;
    }

    /**
     * Sets up an object brick used for lazy loading tests
     *
     * @throws Exception
     */
    public function setupObjectbrick_LazyLoadingTest(string $name = 'LazyLoadingTest', string $filename = 'lazyloading/objectbrick_LazyLoadingTest_export.json'): ?Definition
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$definition = $cm->getObjectbrick($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('manyToManyObjectRelation', 'objects')
                ->setClasses(['RelationTest'])
            );

            $panel->addChild($this->createDataChild('manyToOneRelation', 'relation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'relations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $panel->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'advancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $panel->addChild($this->createDataChild('advancedManyToManyRelation', 'advancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadataUpper', 'type' => 'text', 'label' => 'meta'],
                ]));

            $root->addChild($rootPanel);
            $definition = $this->createObjectbrick($name, $root, $filename, [
                ['classname' => 'LazyLoading', 'fieldname' => 'bricks'],

            ]);
        }

        return $definition;
    }

    /**
     * Sets up an object brick used for lazy loading tests
     *
     * @throws Exception
     */
    public function setupObjectbrick_LazyLoadingLocalizedTest(string $name = 'LazyLoadingLocalizedTest', string $filename = 'lazyloading/objectbrick_LazyLoadingLocalizedTest_export.json'): ?Definition
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$definition = $cm->getObjectbrick($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $lFields = new \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields();
            $lFields->setName('localizedfields');

            $lFields->addChild($this->createDataChild('manyToManyObjectRelation', 'lobjects')
                ->setClasses(['RelationTest'])
            );

            $lFields->addChild($this->createDataChild('manyToOneRelation', 'lrelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('manyToManyRelation', 'lrelations')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true));

            $lFields->addChild($this->createDataChild('input', 'linput'));

            $lFields->addChild($this->createDataChild('advancedManyToManyObjectRelation', 'ladvancedObjects')
                ->setAllowMultipleAssignments(false)
                ->setAllowedClassId('RelationTest')
                ->setClasses([])
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'metadata'],
                ]));

            $lFields->addChild($this->createDataChild('advancedManyToManyRelation', 'ladvancedRelations')
                ->setAllowMultipleAssignments(false)
                ->setDocumentTypes([])->setAssetTypes([])->setClasses(['RelationTest'])
                ->setDocumentsAllowed(false)->setAssetsAllowed(false)->setObjectsAllowed(true)
                ->setColumns([ ['position' => 1, 'key' => 'metadata', 'type' => 'text', 'label' => 'meta'],
                ]));

            $panel->addChild($lFields);
            $root->addChild($rootPanel);
            $definition = $this->createObjectbrick($name, $root, $filename, [
                ['classname' => 'LazyLoading', 'fieldname' => 'bricks'],

            ]);
        }

        return $definition;
    }

    /**
     * Sets up an object brick
     *
     * @throws Exception
     */
    public function setupObjectbrick_UnittestBrick(string $name = 'unittestBrick', string $filename = 'brick-import.json'): ?Definition
    {
        /** @var ClassManager $cm */
        $cm = $this->getClassManager();

        if (!$definition = $cm->getObjectbrick($name)) {
            $root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel('root');
            $panel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel())->setName('MyLayout');
            $rootPanel = (new \Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel())->setName('Layout');
            $rootPanel->addChild($panel);

            $panel->addChild($this->createDataChild('input', 'brickinput'));

            $panel->addChild($this->createDataChild('input', 'brickinput2'));

            $panel->addChild($this->createDataChild('manyToManyRelation', 'brickLazyRelation')
                ->setDocumentTypes([])->setAssetTypes([])->setClasses([])
                ->setDocumentsAllowed(true)->setAssetsAllowed(true)->setObjectsAllowed(true));

            $root->addChild($rootPanel);
            $definition = $this->createObjectbrick($name, $root, $filename, [
                ['classname' => 'unittest', 'fieldname' => 'mybricks'],
                ['classname' => 'inheritance', 'fieldname' => 'mybricks'],
            ]);
        }

        return $definition;
    }

    /**
     *
     * @throws Exception
     */
    protected function createFieldcollection(string $name, ClassDefinition\Layout $layout, string $filename): Definition
    {
        $cm = $this->getClassManager();
        $def = new Definition();
        $def->setKey($name);
        $def->setLayoutDefinitions($layout);
        $json = ClassDefinition\Service::generateFieldCollectionJson($def);
        $cm->saveJson($filename, $json);

        return $cm->setupFieldcollection($name, $filename);
    }

    protected function createObjectbrick(string $name, ClassDefinition\Layout $layout, string $filename, array $classDefinitions = []): DataObject\Objectbrick\Definition
    {
        $cm = $this->getClassManager();
        $def = new DataObject\Objectbrick\Definition();
        $def->setKey($name);
        $def->setLayoutDefinitions($layout);
        $def->setClassDefinitions($classDefinitions);
        $json = ClassDefinition\Service::generateObjectBrickJson($def);
        $cm->saveJson($filename, $json);

        return $cm->setupObjectbrick($name, $filename);
    }

    public function setupUnitDefinitions(): void
    {
        DataObject\QuantityValue\Unit::create(['abbreviation' => 'mm'])->save();
        DataObject\QuantityValue\Unit::create(['abbreviation' => 'cm'])->save();
        DataObject\QuantityValue\Unit::create(['abbreviation' => 'm'])->save();
    }

    /**
     * Initialize widely used class definitions
     */
    public function initializeDefinitions(): void
    {
        $this->setupQuantityValueUnits();

        $cm = $this->getClassManager();

        $this->setupUnitDefinitions();
        $this->setupFieldcollection_Unittestfieldcollection();

        $this->setupPimcoreClass_Unittest();
        $this->setupPimcoreClass_Inheritance();
        $this->setupPimcoreClass_RelationTest();

        $this->setupObjectbrick_UnittestBrick();
    }

    private function setupUnit(string $abbr): void
    {
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation($abbr);
        if (!$unit) {
            $unit = new DataObject\QuantityValue\Unit();
            $unit->setAbbreviation($abbr);
            $unit->save();
        }
    }

    public function setupQuantityValueUnits(): void
    {
        $this->setupUnit('mm');
        $this->setupUnit('cm');
        $this->setupUnit('dm');
        $this->setupUnit('m');
        $this->setupUnit('km');
    }
}
