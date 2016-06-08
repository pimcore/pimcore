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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
