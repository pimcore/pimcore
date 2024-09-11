<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use Exception;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class RgbaColor implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected int $r;

    protected int $g;

    protected int $b;

    protected int $a;

    /**
     * RgbaColor constructor.
     *
     */
    public function __construct(int $r = null, int $g = null, int $b = null, int $a = null)
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
        $this->setA($a);
        $this->markMeDirty();
    }

    public function getR(): int
    {
        return $this->r;
    }

    public function setR(?int $r): void
    {
        $this->r = is_null($r) ? 0 : $r;
        $this->markMeDirty();
    }

    public function getG(): int
    {
        return $this->g;
    }

    public function setG(?int $g): void
    {
        $this->g = is_null($g) ? 0 : $g;
        $this->markMeDirty();
    }

    public function getB(): int
    {
        return $this->b;
    }

    public function setB(?int $b): void
    {
        $this->b = is_null($b) ? 0 : $b;
        $this->markMeDirty();
    }

    public function getA(): int
    {
        return $this->a;
    }

    public function setA(?int $a): void
    {
        $this->a = is_null($a) ? 255 : $a;
        $this->markMeDirty();
    }

    public function getRgb(): array
    {
        return [$this->r, $this->g, $this->b];
    }

    /**
     *  Return R 0-255, G 0-255, B 0-255, A 0-255
     *
     */
    public function getRgba(): array
    {
        return [$this->r, $this->g, $this->b, $this->a];
    }

    /**
     *  Return R 0-255, G 0-255, B 0-255, A 0-1 (1 == full opacity)
     *
     */
    public function getCssRgba(): array
    {
        return [$this->r, $this->g, $this->b, round($this->a / 255, 3)];
    }

    public function getHex(bool $withAlpha = false, bool $withHash = true): string
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
     *
     * @throws Exception
     */
    public function setHex(string $hexValue): void
    {
        $hexValue = ltrim($hexValue, '#');
        $length = strlen($hexValue);
        if ($length == 6 || $length == 8) {
            if ($length == 6) {
                [$r, $g, $b] = sscanf($hexValue, '%02x%02x%02x');
                $a = 255;
            } else {
                [$r, $g, $b, $a] = sscanf($hexValue, '%02x%02x%02x%02x');
            }
            $this->setR($r);
            $this->setG($g);
            $this->setB($b);
            $this->setA($a);
        } else {
            throw new Exception('Format must be either hex6 or hex8 with or without leading hash');
        }
        $this->markMeDirty();
    }

    public function setRgba(int $r = null, int $g = null, int $b = null, int $a = null): void
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
        $this->setA($a);
        $this->markMeDirty();
    }

    public function __toString(): string
    {
        return $this->getHex(true, true);
    }
}
