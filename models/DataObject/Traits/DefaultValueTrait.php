<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\DefaultValueGeneratorResolver;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

trait DefaultValueTrait
{
    /** @var string */
    public $defaultValueGenerator = '';

    /**
     * @param \Pimcore\Model\DataObject\Concrete $object
     * @param array $context
     *
     * @return null|string
     */
    abstract protected function doGetDefaultValue($object, $context = []);

    /**
     * @param mixed $data
     * @param Concrete $object
     * @param array $params
     *
     * @return mixed modified data
     */
    protected function handleDefaultValue($data, $object = null, $params = [])
    {
        $context = isset($params['context']) ? $params['context'] : [];
        $isUpdate = isset($params['isUpdate']) ? $params['isUpdate'] : true;

        /**
         * 1. only for create, not on update. otherwise there is no way to null it out anymore.
         */
        if ($isUpdate) {
            return $data;
        }

        /**
         * 2. if inheritance is enabled and there is no parent value then take the default value.
         * 3. if inheritance is disabled, take the default value.
         */
        if ($this->isEmpty($data)) {
            $class = null;
            $owner = isset($params['owner']) ? $params['owner'] : null;
            if ($owner instanceof Concrete) {
                if ($isUpdate) {
                    // only consider default value for new objects
                    return $data;
                }
                $class = $owner->getClass();
            } elseif ($owner instanceof AbstractData) {
                if ($isUpdate) {
                    // only consider default value for new bricks
                    return $data;
                }
                $class = $owner->getObject()->getClass();
            }

            if ($object !== null && !empty($this->defaultValueGenerator)) {
                $defaultValueGenerator = DefaultValueGeneratorResolver::resolveGenerator($this->defaultValueGenerator);

                if ($defaultValueGenerator instanceof DefaultValueGeneratorInterface) {
                    if (!isset($params['context'])) {
                        $params['context'] = [];
                    }

                    if ($owner instanceof Concrete) {
                        $params['context'] = array_merge($params['context'], [
                            'ownerType' => 'object',
                            'fieldname' => $this->getName(),
                        ]);
                    } elseif ($owner instanceof Localizedfield) {
                        $params['context'] = array_merge($params['context'], [
                            'ownerType' => 'localizedfield',
                            'ownerName' => 'localizedfields',
                            'position' => $params['language'],
                            'fieldname' => $this->getName(),
                        ]);
                    } elseif ($owner instanceof \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData) {
                        $params['context'] = array_merge($params['context'], [
                            'ownerType' => 'fieldcollection',
                            'ownerName' => $owner->getFieldname(),
                            'fieldname' => $this->getName(),
                            'index' => $owner->getIndex(),
                        ]);
                    } elseif ($owner instanceof AbstractData) {
                        $params['context'] = array_merge($params['context'], [
                            'ownerType' => 'objectbrick',
                            'ownerName' => $owner->getFieldname(),
                            'fieldname' => $this->getName(),
                            'index' => $owner->getType(),
                        ]);
                    }

                    return $defaultValueGenerator->getValue($object, $this, $params['context']);
                }
            }

            // we check first if we even want to work with default values. if this is not the case then
            // we are also not allowed to inspect the parent value.

            // if the parent doesn't have a value then we take the configured value as fallback
            $configuredDefaultValue = $this->doGetDefaultValue($object, $context);
            if (!$this->isEmpty($configuredDefaultValue)) {
                if ($class && $class->getAllowInherit()) {
                    $params = [];

                    $inheritanceEnabled = Concrete::getGetInheritedValues();

                    try {
                        // make sure we get the inherited value of the parent
                        Concrete::setGetInheritedValues(true);

                        $data = $owner->getValueFromParent($this->getName(), $params);
                        if (!$this->isEmpty($data)) {
                            return null;
                        }
                    } catch (InheritanceParentNotFoundException $e) {
                        // no data from parent available, use the default value
                    } finally {
                        Concrete::setGetInheritedValues($inheritanceEnabled);
                    }
                }
            }
            $data = $configuredDefaultValue;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getDefaultValueGenerator(): string
    {
        return $this->defaultValueGenerator;
    }

    /**
     * @param string $defaultValueGenerator
     */
    public function setDefaultValueGenerator($defaultValueGenerator)
    {
        $this->defaultValueGenerator = (string)$defaultValueGenerator;
    }
}
