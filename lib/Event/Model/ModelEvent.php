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
