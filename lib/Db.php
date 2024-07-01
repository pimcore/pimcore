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

namespace Pimcore;

use Doctrine\DBAL\Connection;
use Pimcore;

class Db
{
    public static function getConnection(): Connection
    {
        return self::get();
    }

    public static function reset(): Connection
    {
        self::close();

        return self::get();
    }

    public static function get(): Connection
    {
        /** @var Connection $db */
        $db = Pimcore::getContainer()->get('doctrine.dbal.default_connection');

        return $db;
    }

    public static function close(): void
    {
        $db = Pimcore::getContainer()->get('doctrine.dbal.default_connection');
        $db->close();
    }
}
