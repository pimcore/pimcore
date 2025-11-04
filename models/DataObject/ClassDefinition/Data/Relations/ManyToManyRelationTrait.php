<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use function is_array;

trait ManyToManyRelationTrait
{
    /**
     * Unless forceSave is set to true, this method will check if the field is dirty and skip the save if not
     */
    protected function skipSaveCheck(
        Localizedfield|AbstractData|DataObject\Objectbrick\Data\AbstractData|Concrete $object,
        array $params = []
    ): bool {
        $forceSave = $params['forceSave'] ?? false;

        if (
            $forceSave === false &&
            !DataObject::isDirtyDetectionDisabled()
        ) {
            if ($object instanceof DataObject\Localizedfield) {
                if ($object->getObject() instanceof DirtyIndicatorInterface && !$object->hasDirtyFields()) {
                    return true;
                }
            } elseif ($this->supportsDirtyDetection() && !$object->isFieldDirty($this->getName())) {
                return true;
            }
        }

        return false;
    }

    public function save(Localizedfield|AbstractData|DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        if ($this->skipSaveCheck($object, $params)) {
            return;
        }

        parent::save($object, $params);
    }

    protected function filterUnpublishedElements(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        if (DataObject::doHideUnpublished()) {
            $publishedList = [];
            foreach ($data as $listElement) {
                if (Element\Service::isPublished($listElement)) {
                    $publishedList[] = $listElement;
                }
            }

            return $publishedList;
        }

        return $data;
    }

    /**
     * Filter by relation feature
     *
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $prefix = '';
        $name = $params['name'] ?: $this->name;
        $prefix = $params['brickPrefix'] ?? null;

        if ($prefix !== null) {
            // The brick prefix might be quoted and with a dot suffix, if so, removing the first
            // and second last character to unquote
            $quoteIdentifierSymbol  = substr(Db::get()->quoteIdentifier(''), 0, 1);

            if (
                substr($prefix, 0, 1) === $quoteIdentifierSymbol &&
                substr($prefix, -2, 1) === $quoteIdentifierSymbol &&
                substr($prefix, -1) === '.'
            ) {
                // Case: `db`.
                $prefix = substr($prefix, 1, -2) . '.';
            } elseif (
                substr($prefix, 0, 1) === $quoteIdentifierSymbol &&
                substr($prefix, -1) === $quoteIdentifierSymbol
            ) {
                // Case: `db`
                $prefix = substr($prefix, 1, -1);
            }
        }

        return $this->getRelationFilterCondition($value, $operator, $prefix . $name);
    }
}
