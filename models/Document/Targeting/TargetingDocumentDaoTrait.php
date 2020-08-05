<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Targeting;

use Pimcore\Model\Document\PageSnippet;

trait TargetingDocumentDaoTrait
{
    public function hasTargetGroupSpecificEditables(): bool
    {
        /** @var PageSnippet\Dao $this */
        $count = $this->db->fetchOne(
            'SELECT count(*) FROM documents_elements WHERE documentId = ? AND name LIKE ?',
            [
                $this->model->getId(),
                '%' . TargetingDocumentInterface::TARGET_GROUP_EDITABLE_PREFIX . '%' . TargetingDocumentInterface::TARGET_GROUP_EDITABLE_SUFFIX . '%',
            ]
        );

        return $count > 0;
    }

    public function getTargetGroupSpecificEditableNames(): array
    {
        /** @var PageSnippet\Dao $this */
        $names = $this->db->fetchCol(
            'SELECT name FROM documents_elements WHERE documentId = ? AND name LIKE ?',
            [
                $this->model->getId(),
                '%' . TargetingDocumentInterface::TARGET_GROUP_EDITABLE_PREFIX . '%' . TargetingDocumentInterface::TARGET_GROUP_EDITABLE_SUFFIX . '%',
            ]
        );

        return $names;
    }
}
