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

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Db;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

/**
 * @internal
 */
final class RequiredBy extends AbstractOperator
{
    private ?string $elementType = null;

    private bool $onlyCount;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->elementType = $config->elementType ?? null;
        $this->onlyCount = $config->onlyCount ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isArrayType = true;

        $db = Db::get();
        $typeCondition = '';
        switch ($this->getElementType()) {
            case 'document': $typeCondition = " AND sourcetype = 'document'";

                break;
            case 'asset': $typeCondition = " AND sourcetype = 'asset'";

                break;
            case 'object': $typeCondition = " AND sourcetype = 'object'";

                break;
        }

        if ($this->getOnlyCount()) {
            $query = 'select count(*) from dependencies where targettype = ? AND targetid = ?'. $typeCondition;
            $count = $db->fetchOne($query, [Service::getElementType($element), $element->getId()]);
            $result->value = $count;
        } else {
            $resultList = [];
            $query = 'select * from dependencies where targettype = ? AND targetid = ?'. $typeCondition;
            $dependencies = $db->fetchAllAssociative($query, [Service::getElementType($element), $element->getId()]);
            foreach ($dependencies as $dependency) {
                $sourceType = $dependency['sourcetype'];
                $sourceId = $dependency['sourceid'];
                $element = Service::getElementById($sourceType, $sourceId);
                $resultList[] = $element;
            }
            $result->value = $resultList;
        }

        return $result;
    }

    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    public function setElementType(?string $elementType): void
    {
        $this->elementType = $elementType;
    }

    public function getOnlyCount(): bool
    {
        return $this->onlyCount;
    }

    public function setOnlyCount(bool $onlyCount): void
    {
        $this->onlyCount = $onlyCount;
    }
}
