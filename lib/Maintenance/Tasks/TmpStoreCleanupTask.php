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

namespace Pimcore\Maintenance\Tasks;

use Doctrine\DBAL\Connection;
use Pimcore\Maintenance\TaskInterface;

/**
 * @internal
 */
class TmpStoreCleanupTask implements TaskInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function execute(): void
    {
        $this->db->executeQuery('DELETE FROM tmp_store WHERE `expiryDate` < :time', ['time' => time()]);
    }
}
