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

namespace Pimcore\Bundle\SeoBundle\Maintenance;

use Doctrine\DBAL\Connection;
use Pimcore\Maintenance\TaskInterface;

/**
 * @internal
 */
class LogErrorCleanupTask implements TaskInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function execute(): void
    {
        // keep the history for max. 7 days (=> exactly 144h), according to the privacy policy (EU/German Law)
        // it's allowed to store the IP for 7 days for security reasons (DoS, ...)
        $limit = time() - (6 * 86400);

        $this->db->executeStatement('DELETE FROM http_error_log WHERE `date` < :limit', [
            'limit' => $limit,
        ]);
    }
}
