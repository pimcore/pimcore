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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Dao;

use Pimcore\Config;
use Pimcore\Db\PhpArrayFileTable;

/**
 * @internal
 */
abstract class PhpArrayTable implements DaoInterface
{
    use DaoTrait;

    /**
     * @var PhpArrayFileTable
     */
    protected $db;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
    }

    /**
     * @param string $name
     */
    protected function setFile($name)
    {
        $file = Config::locateConfigFile($name . '.php');
        $this->db = PhpArrayFileTable::get($file);
    }
}
