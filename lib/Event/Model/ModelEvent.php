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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\ModelInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ModelEvent extends Event implements ModelEventInterface
{
    use ArgumentsAwareTrait;

    protected ModelInterface $modelInterface;

    public function __construct(ModelInterface $model, array $arguments = [])
    {
        $this->modelInterface = $model;
        $this->arguments = $arguments;
    }

    public function getModel(): ModelInterface
    {
        return $this->modelInterface;
    }

    public function setModel(ModelInterface $model): void
    {
        $this->modelInterface = $model;
    }
}
