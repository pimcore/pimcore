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

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Model;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\FilterListingInterface;
use Pimcore\Model\Listing\OrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @internal
 *
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition\CustomLayout[] load()
 * @method Model\DataObject\ClassDefinition\CustomLayout|false current()
 */
class Listing extends AbstractModel implements FilterListingInterface, OrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    protected ?array $layoutDefinitions = null;

    /**
     * @param Model\DataObject\ClassDefinition\CustomLayout[]|null $layoutDefinitions
     *
     * @return self
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;

        return $this;
    }

    /**
     * @return Model\DataObject\ClassDefinition\CustomLayout[]
     */
    public function getLayoutDefinitions()
    {
        if ($this->layoutDefinitions === null) {
            $this->layoutDefinitions = $this->load();
        }

        return $this->layoutDefinitions;
    }
}
