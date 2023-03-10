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

namespace Pimcore\Bundle\PersonalizationBundle\Document\Newsletter\AddressSourceAdapter;

use Pimcore\Document\Newsletter\AddressSourceAdapter\DefaultAdapter as BaseDefaultAdapter;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Listing;

/**
 * @internal
 */
final class DefaultAdapter extends BaseDefaultAdapter
{
    /**
     * @var int[]
     */
    protected mixed $targetGroups = [];

    public function __construct(array $params)
    {
        $this->targetGroups = $params['target_groups'] ?? [];

        parent::__construct($params);
    }

    protected function getListing(): ?Listing
    {
        if (empty($this->list)) {
            $objectList = '\\Pimcore\\Model\\DataObject\\' . ucfirst($this->class) . '\\Listing';
            $this->list = new $objectList();

            $conditions = ['(newsletterActive = 1 AND newsletterConfirmed = 1)'];
            if ($this->condition) {
                $conditions[] = '(' . $this->condition . ')';
            }

            if ($this->targetGroups) {
                $class = ClassDefinition::getByName($this->class);

                if ($class) {
                    $conditions = $this->addTargetGroupConditions($class, $conditions);
                }
            }

            $this->list->setCondition(implode(' AND ', $conditions));
            $this->list->setOrderKey('email');
            $this->list->setOrder('ASC');

            $this->elementsTotal = $this->list->getTotalCount();
        }

        return $this->list;
    }

    /**
     * Handle target group filters
     *
     * @param ClassDefinition $class
     * @param array $conditions
     *
     * @return array
     */
    protected function addTargetGroupConditions(ClassDefinition $class, array $conditions): array
    {
        if (!$class->getFieldDefinition('targetGroup')) {
            return $conditions;
        }

        $fieldDefinition = $class->getFieldDefinition('targetGroup');
        if ($fieldDefinition instanceof ClassDefinition\Data\TargetGroup) {
            $targetGroups = [];
            foreach ($this->targetGroups as $value) {
                if (!empty($value)) {
                    $targetGroups[] = $this->list->quote($value);
                }
            }

            $conditions[] = 'targetGroup IN (' . implode(',', $targetGroups) . ')';
        } elseif ($fieldDefinition instanceof ClassDefinition\Data\TargetGroupMultiselect) {
            $targetGroupsCondition = [];
            foreach ($this->targetGroups as $value) {
                $targetGroupsCondition[] = 'targetGroup LIKE ' . $this->list->quote('%,' . $value . ',%');
            }

            $conditions[] = '(' . implode(' OR ', $targetGroupsCondition) . ')';
        }

        return $conditions;
    }
}
