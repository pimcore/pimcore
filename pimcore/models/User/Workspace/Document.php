<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Workspace_Document extends User_Workspace_Abstract {

    /**
     * @var bool
     */
    public $save = false;

    /**
     * @var bool
     */
    public $unpublish = false;

    /**
     * @param boolean $save
     */
    public function setSave($save)
    {
        $this->save = $save;
    }

    /**
     * @return boolean
     */
    public function getSave()
    {
        return $this->save;
    }

    /**
     * @param boolean $unpublish
     */
    public function setUnpublish($unpublish)
    {
        $this->unpublish = $unpublish;
    }

    /**
     * @return boolean
     */
    public function getUnpublish()
    {
        return $this->unpublish;
    }
}
