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
 * @package    Tool
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Qrcode;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Qrcode\Config\Dao getDao()
 */
class Config extends Model\AbstractModel
{

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $description = "";

    /**
     * @var string
     */
    public $url = "";

    /**
     * @var
     */
    public $foreColor = "";

    /**
     * @var string
     */
    public $backgroundColor = "";

    /**
     * @var bool
     */
    public $googleAnalytics = true;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @param $name
     * @return Config
     * @throws \Exception
     */
    public static function getByName($name)
    {
        try {
            $code = new self();
            $code->getDao()->getByName($name);
        } catch (\Exception $e) {
            return null;
        }

        return $code;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $backgroundColor
     * @return $this
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param $foreColor
     * @return $this
     */
    public function setForeColor($foreColor)
    {
        $this->foreColor = $foreColor;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeColor()
    {
        return $this->foreColor;
    }

    /**
     * @param $googleAnalytics
     * @return $this
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->googleAnalytics = (bool) $googleAnalytics;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getGoogleAnalytics()
    {
        return $this->googleAnalytics;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }
}
