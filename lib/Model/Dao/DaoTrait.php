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

namespace Pimcore\Model\Dao;

use Pimcore\Model\AbstractModel;

/**
 * @internal
 */
trait DaoTrait
{
    /**
     * @var \Pimcore\Model\AbstractModel
     */
    protected $model;

    public function setModel(AbstractModel $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function assignVariablesToModel(array $data): void
    {
        $this->model->setValues($data, true);
    }
}
