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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable\Area;

use Pimcore\Templating\Model\ViewModelInterface;

abstract class AbstractArea
{
    /**
     * @var ViewModelInterface
     *
     * @deprecated
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
     * @param ViewModelInterface $view
     *
     * @return $this
     *
     * @deprecated
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ViewModelInterface
     *
     * @deprecated
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param \Pimcore\Config\Config $config
     *
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
     * @param string $key
     *
     * @return mixed
     */
    public function getParam($key)
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getAllParams()
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param Info $brick
     *
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

class_alias(AbstractArea::class, 'Pimcore\Model\Document\Tag\Area\AbstractArea');
