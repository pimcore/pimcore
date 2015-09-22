<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;

class Folder extends Model\Asset
{

    /**
     * @var string
     */
    public $type = "folder";

    /**
     * set the children of the document
     *
     * @return array
     */
    public function setChilds($childs)
    {
        $this->childs = $childs;
        if (is_array($childs) and count($childs > 0)) {
            $this->hasChilds = true;
        } else {
            $this->hasChilds = false;
        }
        return $this;
    }
}
