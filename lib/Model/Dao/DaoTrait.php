<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
