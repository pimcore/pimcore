<?php
namespace Pimcore\Tests\Helper;

use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\ClassDefinition;

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
     * Initialize mode class definitions
     */
    public function initializeDefinitions()
    {
        $cm = $this->getClassManager();

        $cm->setupFieldcollection('unittestfieldcollection', 'fieldcollection-import.json');

        $unittestClass  = $this->setupUnittestClass('unittest', 'class-import.json');
        $allFieldsClass = $this->setupUnittestClass('allfields', 'class-allfields.json');

        $cm->setupClass('inheritance', 'inheritance.json');

        $cm->setupObjectbrick('unittestBrick', 'brick-import.json', [$unittestClass->getName()]);
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
                $fd->setAllowedClassId($class->getId());
                $class->save();
            }

            return $class;
        }

        return $cm->getClass($name);
    }
}
