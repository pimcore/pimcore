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
use Pimcore\Db;

trait DaoTrait
{
    /**
     * @var \Pimcore\Model\AbstractModel
     */
    protected $model;

    /**
     * @param \Pimcore\Model\AbstractModel $model
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param array $data
     * @return void
     */
    protected function assignVariablesToModel($data)
    {
        $this->model->setValues($data);
    }
}
