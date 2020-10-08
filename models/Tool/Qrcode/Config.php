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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Qrcode;

use Pimcore\Model;

/**
 * @deprecated
 *
 * @method \Pimcore\Model\Tool\Qrcode\Config\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Config extends Model\AbstractModel
{
    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $foreColor = '';

    /**
     * @var string
     */
    public $backgroundColor = '';

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
     * @param string $name
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name)
    {
        try {
            $code = new self();
            $code->getDao()->getByName($name);

            return $code;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $description
     *
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
     * @param string $name
     *
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
     * @param string $url
     *
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
     * @param string $backgroundColor
     *
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
     * @param string $foreColor
     *
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
     * @param bool $googleAnalytics
     *
     * @return $this
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->googleAnalytics = (bool) $googleAnalytics;

        return $this;
    }

    /**
     * @return bool
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
