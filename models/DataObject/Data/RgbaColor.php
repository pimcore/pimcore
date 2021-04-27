<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class RgbaColor implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /** @var int */
    protected int $r;

    /** @var int */
    protected int $g;

    /** @var int */
    protected int $b;

    /** @var int */
    protected int $a;

    /**
     * RgbaColor constructor.
     *
     * @param int|null $r
     * @param int|null $g
     * @param int|null $b
     * @param int|null $a
     */
    public function __construct($r = null, $g = null, $b = null, $a = null)
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
        $this->setA($a);
        $this->markMeDirty();
    }

    /**
     * @return int
     */
    public function getR(): int
    {
        return $this->r;
    }

    /**
     * @param int|null $r
     */
    public function setR(?int $r): void
    {
        $this->r = is_null($r) ? 0 : $r;
        $this->markMeDirty();
    }

    /**
     * @return int
     */
    public function getG(): int
    {
        return $this->g;
    }

    /**
     * @param int|null $g
     */
    public function setG($g): void
    {
        $this->g = is_null($g) ? 0 : $g;
        $this->markMeDirty();
    }

    /**
     * @return int
     */
    public function getB(): int
    {
        return $this->b;
    }

    /**
     * @param int|null $b
     */
    public function setB(?int $b): void
    {
        $this->b = is_null($b) ? 0 : $b;
        $this->markMeDirty();
    }

    /**
     * @return int
     */
    public function getA(): int
    {
        return $this->a;
    }

    /**
     * @param int|null $a
     */
    public function setA(?int $a): void
    {
        $this->a = is_null($a) ? 255 : $a;
        $this->markMeDirty();
    }

    /**
     * @return array
     */
    public function getRgb(): array
    {
        return [$this->r, $this->g, $this->b];
    }

    /**
     *  Return R 0-255, G 0-255, B 0-255, A 0-255
     *
     * @return array
     */
    public function getRgba(): array
    {
        return [$this->r, $this->g, $this->b, $this->a];
    }

    /**
     *  Return R 0-255, G 0-255, B 0-255, A 0-1 (1 == full opacity)
     *
     * @return array
     */
    public function getCssRgba(): array
    {
        return [$this->r, $this->g, $this->b, round($this->a / 255, 3)];
    }

    /**
     * @param bool $withAlpha
     * @param bool $withHash
     *
     * @return string
     */
    public function getHex($withAlpha = false, $withHash = true): string
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
     * @param string $hexValue
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
        $this->markMeDirty();
    }

    /**
     * @param int|null $r
     * @param int|null $g
     * @param int|null $b
     * @param int|null $a
     */
    public function setRgba($r = null, $g = null, $b = null, $a = null)
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
        $this->setA($a);
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getHex(true, true);
    }
}
