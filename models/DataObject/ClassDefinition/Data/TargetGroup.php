<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\Tool;

class TargetGroup extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'targetGroup';

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            try {
                $this->checkValidity($data, true, $params);
            } catch (\Exception $e) {
                $data = null;
            }
        }

        return $data;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param Model\DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return null|string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            try {
                $this->checkValidity($data, true, $params);
            } catch (\Exception $e) {
                $data = null;
            }
        }

        return $data;
    }

    /**
     * @internal
     */
    public function configureOptions()
    {
        /** @var Tool\Targeting\TargetGroup\Listing|Tool\Targeting\TargetGroup\Listing\Dao $list */
        $list = new Tool\Targeting\TargetGroup\Listing();
        $list->setOrder('asc');
        $list->setOrderKey('name');

        $targetGroups = $list->load();

        $options = [];
        foreach ($targetGroups as $targetGroup) {
            $options[] = [
                'value' => $targetGroup->getId(),
                'key' => $targetGroup->getName(),
            ];
        }

        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data)) {
            $targetGroup = Tool\Targeting\TargetGroup::getById($data);

            if (!$targetGroup instanceof Tool\Targeting\TargetGroup) {
                throw new Model\Element\ValidationException('Invalid target group reference');
            }
        }
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        $options = $obj->getOptions();
        if (\Pimcore::inAdmin() || empty($options)) {
            $obj->configureOptions();
        }

        return $obj;
    }

    /**
     * @return $this
     */
    public function jsonSerialize()
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveBlockedVars(): array
    {
        $blockedVars = parent::resolveBlockedVars();
        $blockedVars[] = 'options';

        return $blockedVars;
    }
}
