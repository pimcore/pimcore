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
