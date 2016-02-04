<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\Tag\Area;

use Pimcore\Model;

abstract class AbstractArea
{

    /**
     * @var \Zend_View
     */
    protected $view;

    /**
     * @var \Zend_Config
     */
    protected $config;

    /**
     * @var Info
     */
    protected $brick;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @param $view
     * @return void
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * @return \Zend_View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param $config
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return \Zend_Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getParam($key)
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }
        return;
    }

    /**
     * @return array
     */
    public function getAllParams()
    {
        return $this->params;
    }

    /**
     * @deprecated
     * @param $key
     * @return mixed
     */
    public function _getParam($key)
    {
        return $this->getParam($key);
    }

    /**
     * @deprecated
     * @return array
     */
    public function _getAllParams()
    {
        return $this->getAllParams();
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * @param $params
     * @return void
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param Info $brick
     * @return $this
     */
    public function setBrick($brick)
    {
        $this->brick = $brick;
        return $this;
    }

    /**
     * @return Info
     */
    public function getBrick()
    {
        return $this->brick;
    }
}
