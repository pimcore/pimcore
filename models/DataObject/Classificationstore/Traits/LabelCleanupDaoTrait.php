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

namespace Pimcore\Model\DataObject\Classificationstore\Traits;

use Pimcore\Model\DataObject\Classificationstore\Label;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Throwable;

/**
 * Shared by the four classification store configuration DAOs: a deleted entity takes its own
 * labels with it, in every language, as one unit of work with its row.
 *
 * Only the entity's own labels go. Deleting a store leaves its groups, keys and collections in
 * place, so it leaves their labels in place too.
 *
 * @internal
 */
trait LabelCleanupDaoTrait
{
    /**
     * @throws Throwable
     */
    private function deleteWithLabels(string $table, LabelEntityType $entityType): void
    {
        $this->writeAtomically(function () use ($table, $entityType): void {
            $id = $this->model->getId();

            if ($id !== null) {
                $label = new Label();
                $label->setEntityType($entityType);
                $label->setEntityId($id);
                $label->getDao()->deleteByEntity();
            }

            $this->db->delete($table, ['id' => $id]);
        });
    }
}
