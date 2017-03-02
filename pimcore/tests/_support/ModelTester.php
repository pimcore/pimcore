<?php

namespace Pimcore\Tests;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\ClassDefinition\Data\ObjectsMetadata;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 *
 * @method ClassDefinition createClass($name, $jsonFile)
 */
class ModelTester extends \Codeception\Actor
{
    use _generated\ModelTesterActions;

    /**
     * Setup standard Unittest class
     *
     * @param string $name
     * @param string $file
     * @return ClassDefinition
     */
    public function setupUnittestClass($name = 'unittest', $file = 'class-import.json')
    {
        if (!$this->hasClass($name)) {
            /** @var ClassDefinition $class */
            $class = $this->createClass($name, $file);

            /** @var ObjectsMetadata $fd */
            $fd = $class->getFieldDefinition('objectswithmetadata');
            if ($fd) {
                $fd->setAllowedClassId($class->getId());
                $class->save();
            }

            return $class;
        }

        return ClassDefinition::getByName($name);
    }
}
