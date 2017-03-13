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

use Pimcore\Model\Document\Tag;

class Info
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var Tag|Tag\Area|Tag\Areablock
     */
    public $tag;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $index;

    /**
     * @deprecated
     * @var string
     */
    public $name;

    /**
     * @deprecated
     * @var \Pimcore\Config\Config
     */
    public $config;

    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     * @var string
     */
    public $path;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Tag|Tag\Area|Tag\Areablock
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param Tag $tag
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param int $index
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     *
     * @param \Pimcore\Config\Config $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     *
     * @return \Pimcore\Config\Config
     */
    public function getConfig()
    {
        return $this->config;
    }


    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @deprecated Only used for legacy areas as the AreaInterface now handles static Area data.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
