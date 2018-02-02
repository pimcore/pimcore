<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Version
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

/**
 * @method \Pimcore\Model\Version\Dao getDao()
 */
class GridConfigShare extends AbstractModel
{
    /**
     * @var int
     */
    public $gridConfigId;

    /**
     * @var int
     */
    public $sharedWithUserId;

    /**
     * @param $gridConfigId
     * @param $sharedWithUserId
     *
     * @return GridConfigShare
     */
    public static function getByGridConfigAndSharedWithId($gridConfigId, $sharedWithUserId)
    {
        $share = new self();
        $share->getDao()->getByGridConfigAndSharedWithId($gridConfigId, $sharedWithUserId);

        return $share;
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

    /**
     * @return int
     */
    public function getGridConfigId(): int
    {
        return $this->gridConfigId;
    }

    /**
     * @param int $gridConfigId
     */
    public function setGridConfigId(int $gridConfigId)
    {
        $this->gridConfigId = $gridConfigId;
    }

    /**
     * @return int
     */
    public function getSharedWithUserId(): int
    {
        return $this->sharedWithUserId;
    }

    /**
     * @param int $sharedWithUserId
     */
    public function setSharedWithUserId(int $sharedWithUserId)
    {
        $this->sharedWithUserId = $sharedWithUserId;
    }
}
