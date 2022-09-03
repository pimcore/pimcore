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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class User extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'user';

    /**
     * @internal
     *
     * @var bool
     */
    public $unique;

    /**
     * @internal
     *
     * @return User
     */
    protected function init()
    {
        //loads select list options
        $options = $this->getOptions();
        if (\Pimcore::inAdmin() || empty($options)) {
            $this->configureOptions();
        }

        return $this;
    }

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
     * @param string|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return null|string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $this->init();
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
        $list = new Model\User\Listing();
        $list->setOrder('asc');
        $list->setOrderKey('name');
        $users = $list->load();

        $options = [];
        if (is_array($users) && count($users) > 0) {
            foreach ($users as $user) {
                if ($user instanceof Model\User) {
                    $value = $user->getName();
                    $first = $user->getFirstname();
                    $last = $user->getLastname();
                    if (!empty($first) || !empty($last)) {
                        $value .= ' (' . $first . ' ' . $last . ')';
                    }
                    $options[] = [
                        'value' => $user->getId(),
                        'key' => $value,
                    ];
                }
            }
        }
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data)) {
            $user = Model\User::getById($data);
            if (!$user instanceof Model\User) {
                throw new Model\Element\ValidationException('Invalid user reference');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        $obj->configureOptions();

        return $obj;
    }

    public function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['options']);

        return array_keys($vars);
    }

    public function __wakeup()
    {
        //loads select list options
        $this->init();
    }

    /**
     * @return $this
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()// : static
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

    /**
     * @return bool
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = (bool) $unique;
    }
}
