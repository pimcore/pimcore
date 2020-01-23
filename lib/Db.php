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

namespace Pimcore;

use Pimcore\Db\Connection;
use Psr\Log\LoggerInterface;

class Db
{
    /**
     * @static
     *
     * @return \Pimcore\Db\ConnectionInterface
     */
    public static function getConnection()
    {
        return self::get();
    }

    /**
     * @return Db\ConnectionInterface
     */
    public static function reset()
    {
        self::close();

        return self::get();
    }

    /**
     * @static
     *
     * @return \Pimcore\Db\ConnectionInterface|Connection
     */
    public static function get()
    {
        /**
         * @var \Pimcore\Db\ConnectionInterface $db
         */
        $db = \Pimcore::getContainer()->get('database_connection');

        return $db;
    }

    /**
     * @static
     *
     * @return LoggerInterface
     */
    public static function getLogger()
    {
        return \Pimcore::getContainer()->get('monolog.logger.doctrine');
    }

    /**
     * @static
     */
    public static function close()
    {
        $db = \Pimcore::getContainer()->get('database_connection');
        $db->close();
    }
}
