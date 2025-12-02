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
