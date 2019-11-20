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

use Pimcore\Db;
use Pimcore\Maintenance\TaskInterface;
use Psr\Log\LoggerInterface;

final class DbCleanupBrokenViewsTask implements TaskInterface
{
    /**
     * @var Db\ConnectionInterface
     */
    private $db;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Db\ConnectionInterface   $db
     * @param LoggerInterface $logger
     */
    public function __construct(Db\ConnectionInterface $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $tables = $this->db->fetchAll('SHOW FULL TABLES');
        foreach ($tables as $table) {
            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type === 'VIEW') {
                try {
                    $createStatement = $this->db->fetchRow('SHOW FIELDS FROM '.$name);
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'references invalid table') !== false) {
                        $this->logger->error('view '.$name.' seems to be a broken one, it will be removed');
                        $this->logger->error('error message was: '.$e->getMessage());

                        $this->db->query('DROP VIEW '.$name);
                    } else {
                        $this->logger->error($e);
                    }
                }
            }
        }
    }
}
