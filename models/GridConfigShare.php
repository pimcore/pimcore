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

namespace Pimcore\Model;

use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\GridConfigShare\Dao getDao()
 *
 * @internal
 */
class GridConfigShare extends AbstractModel
{
    protected int $gridConfigId;

    protected int $sharedWithUserId;

    public static function getByGridConfigAndSharedWithId(int $gridConfigId, int $sharedWithUserId): ?GridConfigShare
    {
        try {
            $share = new self();
            $share->getDao()->getByGridConfigAndSharedWithId($gridConfigId, $sharedWithUserId);

            return $share;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->getDao()->save();
    }

    /**
     * Delete this share
     */
    public function delete()
    {
        $this->getDao()->delete();
    }

    public function getGridConfigId(): int
    {
        return $this->gridConfigId;
    }

    public function setGridConfigId(int $gridConfigId)
    {
        $this->gridConfigId = $gridConfigId;
    }

    public function getSharedWithUserId(): int
    {
        return $this->sharedWithUserId;
    }

    public function setSharedWithUserId(int $sharedWithUserId)
    {
        $this->sharedWithUserId = $sharedWithUserId;
    }
}
