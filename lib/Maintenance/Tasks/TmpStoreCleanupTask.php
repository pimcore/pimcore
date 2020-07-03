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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Db\ConnectionInterface;
use Pimcore\Maintenance\TaskInterface;

final class TmpStoreCleanupTask implements TaskInterface
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    /**
     * @param ConnectionInterface $db
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->db->deleteWhere('tmp_store', 'expiryDate < '.time());
    }
}
