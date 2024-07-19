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
use Exception;
use Pimcore\Maintenance\TaskInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class DbCleanupBrokenViewsTask implements TaskInterface
{
    private Connection $db;

    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $tables = $this->db->fetchAllAssociative('SHOW FULL TABLES');
        foreach ($tables as $table) {
            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type === 'VIEW') {
                try {
                    $createStatement = $this->db->fetchAssociative('SHOW FIELDS FROM '.$name);
                } catch (Exception $e) {
                    if (str_contains($e->getMessage(), 'references invalid table')) {
                        $this->logger->error('view '.$name.' seems to be a broken one, it will be removed');
                        $this->logger->error('error message was: '.$e->getMessage());

                        $this->db->executeQuery('DROP VIEW '.$name);
                    } else {
                        $this->logger->error((string) $e);
                    }
                }
            }
        }
    }
}
