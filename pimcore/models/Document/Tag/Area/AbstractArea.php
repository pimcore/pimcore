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
 * @package    Document
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag\Area;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\View;

abstract class AbstractArea
{
    /**
     * @var ViewModelInterface|View
     */
    protected $view;

    /**
     * @var \Pimcore\Config\Config
     */
    protected $config;

    /**
     * @var Info
     */
    protected $brick;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param ViewModelInterface|View $view
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ViewModelInterface|View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return \Pimcore\Config\Config
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
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * @param $params
     * @return $this
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
