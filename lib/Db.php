<?php

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

namespace Pimcore;

use Pimcore\Db\Connection;
use Pimcore\Db\ConnectionInterface;
use Psr\Log\LoggerInterface;

class Db
{
    /**
     * @return ConnectionInterface|Connection
     */
    public static function getConnection()
    {
        return self::get();
    }

    /**
     * @return ConnectionInterface|Connection
     */
    public static function reset()
    {
        self::close();

        return self::get();
    }

    /**
     * @return ConnectionInterface|Connection
     */
    public static function get()
    {
        /** @var ConnectionInterface|Connection $db */
        $db = \Pimcore::getContainer()->get('doctrine.dbal.default_connection');

        return $db;
    }

    /**
     * @internal
     *
     * @return LoggerInterface
     *
     * @internal
     */
    public static function getLogger()
    {
        return \Pimcore::getContainer()->get('monolog.logger.doctrine');
    }

    public static function close()
    {
        $db = \Pimcore::getContainer()->get('doctrine.dbal.default_connection');
        $db->close();
    }
}
