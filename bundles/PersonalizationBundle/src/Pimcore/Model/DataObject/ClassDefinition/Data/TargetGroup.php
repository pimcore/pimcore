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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class TargetGroup extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return  null|string|int
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, Dataobject\Concrete $object = null, array $params = []): null|string|int
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
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return  null|string|int
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): null|string|int
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
    public function configureOptions(): void
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
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data)) {
            $targetGroup = Tool\Targeting\TargetGroup::getById((int)$data);

            if (!$targetGroup instanceof Tool\Targeting\TargetGroup) {
                throw new Model\Element\ValidationException('Invalid target group reference');
            }
        }
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);
        $options = $obj->getOptions();
        if (\Pimcore::inAdmin() || empty($options)) {
            $obj->configureOptions();
        }

        return $obj;
    }

    public function jsonSerialize(): mixed
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return parent::jsonSerialize();
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

    public function getFieldType(): string
    {
        return 'targetGroup';
    }
}
