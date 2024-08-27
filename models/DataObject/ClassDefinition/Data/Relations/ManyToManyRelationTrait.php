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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

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
        Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object,
        array $params = []): bool
    {
        $forceSave = $params['forceSave'] ?? false;

        if (
            $forceSave === false &&
            !DataObject::isDirtyDetectionDisabled() &&
            $object instanceof DirtyIndicatorInterface
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

    public function save(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
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
}
