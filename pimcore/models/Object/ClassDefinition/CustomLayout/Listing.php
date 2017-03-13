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
 * @package    Object|Class
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\CustomLayout;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\ClassDefinition\CustomLayout\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of Object\ClassDefinition\CustomLayout\Listing
     *
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @param $key
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @param array $layoutDefinitions
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;
    }

    /**
     * @return array
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }
}
