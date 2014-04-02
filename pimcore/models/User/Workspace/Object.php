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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Workspace_Object extends User_Workspace_Abstract {

    /**
     * @var bool
     */
    public $save = false;

    /**
     * @var bool
     */
    public $unpublish = false;


    /**
     * @var string
     */
    public $lEdit = null;

    /**
     * @var string
     */
    public $lView = null;

    /**
     * @var string
     */
    public $layouts = null;

    /**
     * @param boolean $save
     */
    public function setSave($save)
    {
        $this->save = $save;
        return $this;
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
        return $this;
    }

    /**
     * @return boolean
     */
    public function getUnpublish()
    {
        return $this->unpublish;
    }

    /**
     * @param string $lEdit
     */
    public function setLEdit($lEdit)
    {
        $this->lEdit = $lEdit;
    }

    /**
     * @return string
     */
    public function getLEdit()
    {
        return $this->lEdit;
    }

    /**
     * @param string $lView
     */
    public function setLView($lView)
    {
        $this->lView = $lView;
    }

    /**
     * @return string
     */
    public function getLView()
    {
        return $this->lView;
    }

    /**
     * @param string $layouts
     */
    public function setLayouts($layouts)
    {
        $this->layouts = $layouts;
    }

    /**
     * @return string
     */
    public function getLayouts()
    {
        return $this->layouts;
    }


}
