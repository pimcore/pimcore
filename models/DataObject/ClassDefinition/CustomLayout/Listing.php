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

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition\CustomLayout[] load()
 * @method Model\DataObject\ClassDefinition\CustomLayout current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\DataObject\ClassDefinition\CustomLayout[]|null $layoutDefinitions
     *
     * @return self
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        return $this->setData($layoutDefinitions);
    }

    /**
     * @return Model\DataObject\ClassDefinition\CustomLayout[]
     */
    public function getLayoutDefinitions()
    {
        return $this->getData();
    }
}
