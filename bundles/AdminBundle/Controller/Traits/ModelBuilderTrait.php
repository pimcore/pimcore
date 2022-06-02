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

namespace Pimcore\Bundle\AdminBundle\Controller\Traits;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\FactoryInterface;

/**
 * @internal
 */
trait ModelBuilderTrait
{
    protected FactoryInterface $modelFactory;

    /**
     * Returns a model entity for the given model name
     *
     * @template M
     *
     * @param string $modelName
     * @psalm-param class-string<M> $className
     * @param array|null $params
     *
     * @return AbstractModel
     * @psalm-return M
     */
    protected function buildModel(string $modelName, ?array $params = [])
    {
        return $this->modelFactory->build($modelName, $params);
    }

    /**
     * @return FactoryInterface
     */
    public function getModelFactory(): FactoryInterface
    {
        return $this->modelFactory;
    }

    /**
     * @required
     *
     * @param FactoryInterface $modelFactory
     */
    public function setModelFactory(FactoryInterface $modelFactory): void
    {
        $this->modelFactory = $modelFactory;
    }
}
