<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition\CustomLayout[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $layoutDefinitions = null;

    /**
     * @param array $layoutDefinitions
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;
    }

    /**
     * @return Model\DataObject\ClassDefinition\CustomLayout[]
     */
    public function getLayoutDefinitions()
    {
        if ($this->layoutDefinitions === null) {
            $this->getDao()->load();
        }

        return $this->layoutDefinitions;
    }
}
