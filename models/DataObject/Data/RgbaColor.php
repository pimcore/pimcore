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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

class RgbaColor
{
    /** @var int */
    public $r;

    /** @var int */
    public $g;

    /** @var int */
    public $b;

    /** @var int */
    public $a;

    /**
     * RgbaColor constructor.
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $a
     */
    public function __construct($r = null, $g = null, $b = null, $a = null)
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
        $this->setA($a);
    }

    /**
     * @return int
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @param int $r
     */
    public function setR($r)
    {
        $this->r = is_null($r) ? 0 : $r;
    }

    /**
     * @return int
     */
    public function getG(): int
    {
        return $this->g;
    }

    /**
     * @param int $g
     */
    public function setG($g)
    {
        $this->g = is_null($g) ? 0 : $g;
    }

    /**
     * @return int
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param int $b
     */
    public function setB($b)
    {
        $this->b = is_null($b) ? 0 : $b;
    }

    /**
     * @return int
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param int $a
     */
    public function setA($a)
    {
        $this->a = is_null($a) ? 255 : $a;
    }

    /**
     * @return array
     */
    public function getRgb()
    {
        return [$this->r, $this->g, $this->b];
    }

    /**
     *  Return R 0-255, G 0-255, B 0-255, A 0-255
     *
     * @return array
     */
    public function getRgba()
    {
        return [$this->r, $this->g, $this->b, $this->a];
    }

    /**
     *  Return R 0-255, G 0-255, B 0-255, A 0-1 (1 == full opacity)
     *
     * @return array
     */
    public function getCssRgba()
    {
        return [$this->r, $this->g, $this->b, round($this->a / 255, 3)];
    }

    /**
     * @param bool $withAlpha
     * @param bool $withHash
     *
     * @return string
     */
    public function getHex($withAlpha = false, $withHash = true)
    {
        if ($withAlpha) {
            $result = sprintf('%02x%02x%02x%02x', $this->r, $this->g, $this->b, $this->a);
        } else {
            $result = sprintf('%02x%02x%02x', $this->r, $this->g, $this->b);
        }
        if ($withHash) {
            $result = '#' . $result;
        }

        return $result;
    }

    /**
     * @param $hexValue
     *
     * @throws \Exception
     */
    public function setHex($hexValue)
    {
        $hexValue = ltrim($hexValue, '#');
        $length = strlen($hexValue);
        if ($length == 6 || $length == 8) {
            if ($length == 6) {
                list($r, $g, $b) = sscanf($hexValue, '%02x%02x%02x');
                $a = 255;
            } else {
                list($r, $g, $b, $a) = sscanf($hexValue, '%02x%02x%02x%02x');
            }
            $this->setR($r);
            $this->setG($g);
            $this->setB($b);
            $this->setA($a);
        } else {
            throw new \Exception('Format must be either hex6 or hex8 with or without leading hash');
        }
    }

    /**
     * @param null $r
     * @param null $g
     * @param null $b
     * @param null $a
     */
    public function setRgba($r = null, $g = null, $b = null, $a = null)
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
        $this->setA($a);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getHex(true, true);
    }
}
