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

namespace Pimcore\Model\Translation\AbstractTranslation;

/**
 * @property \Pimcore\Model\Translation\AbstractTranslation $model
 *
 * @deprecated
 */
abstract class Dao extends \Pimcore\Model\Translation\Dao implements Dao\DaoInterface
{
    /**
     * @var string
     */
    public static $_tableName = 'translations_messages';

    /**
     * @return mixed
     */
    public static function getTableName()
    {
        return static::$_tableName;
    }

    /**
     * @return string
     */
    protected function getDatabaseTableName(): string
    {
        return static::$_tableName;
    }
}
