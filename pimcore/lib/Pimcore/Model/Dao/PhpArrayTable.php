<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Dao;

use Pimcore\Cache;
use Pimcore\Config;
use \Pimcore\Db\PhpArrayFileTable;

abstract class PhpArrayTable implements DaoInterface {

    use DaoTrait;

    /**
     * @var PhpArrayFileTable
     */
    protected $db;

    /**
     *
     */
    public function configure() {

    }

    /**
     * @param $name
     */
    protected function setFile($name) {
        $file = Config::locateConfigFile($name . ".php");
        $this->db = PhpArrayFileTable::get($file);
    }
}
