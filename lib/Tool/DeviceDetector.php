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

namespace Pimcore\Tool;

use Exception;

class DeviceDetector
{
    protected array $validDeviceTypes = ['phone', 'tablet', 'desktop'];

    protected ?string $default = 'desktop';

    protected bool $isPhone = false;

    protected bool $isDesktop = false;

    protected bool $isTablet = false;

    protected static ?DeviceDetector $instance = null;

    protected bool $determinedDeviceType = false;

    protected bool $wasUsed = false;

    public static function getInstance(string $default = null): DeviceDetector
    {
        if (!self::$instance) {
            self::$instance = new self($default);
        }

        return self::$instance;
    }

    public function __construct(string $default = null)
    {
        if ($default && in_array($default, ['desktop', 'mobile', 'tablet'])) {
            $this->default = $default;
        }
    }

    public function isDesktop(): bool
    {
        $this->determineDeviceType();

        return $this->isDesktop;
    }

    public function isTablet(): bool
    {
        $this->determineDeviceType();

        return $this->isTablet;
    }

    public function isPhone(): bool
    {
        $this->determineDeviceType();

        return $this->isPhone;
    }

    public function wasUsed(): bool
    {
        return $this->wasUsed;
    }

    public function setWasUsed(bool $wasUsed): void
    {
        $this->wasUsed = $wasUsed;
    }

    /**
     * Set the device type manually. Possible values for type: 'desktop', 'tablet', or 'phone'
     *
     *
     * @throws Exception
     */
    public function setDeviceType(string $type): void
    {
        $instance = self::$instance;
        if ($type == 'desktop') {
            $instance->isDesktop = true;
            $instance->isPhone = false;
            $instance->isTablet = false;
        } elseif ($type == 'tablet') {
            $instance->isTablet = true;
            $instance->isDesktop = false;
            $instance->isPhone = false;
        } elseif (in_array($type, ['mobile', 'phone'])) {
            $instance->isPhone = true;
            $instance->isDesktop = false;
            $instance->isTablet = false;
        } else {
            throw new Exception(sprintf('Unknown device "%s".', $type));
        }
    }

    public function getDevice(): ?string
    {
        foreach ($this->validDeviceTypes as $deviceType) {
            if ($this->{'is'.ucfirst($deviceType)}()) {
                return $deviceType;
            }
        }

        return $this->default;
    }

    public function __toString(): string
    {
        return $this->getDevice();
    }

    private function determineDeviceType(): void
    {
        $this->setWasUsed(true);

        if ($this->determinedDeviceType) {
            return;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $type = null;

        // check CloudFront headers
        foreach (['mobile', 'tablet', 'desktop'] as $cfType) {
            $cfHeaderName = 'HTTP_CLOUDFRONT_IS_' . strtoupper($cfType) . '_VIEWER';
            if (isset($_SERVER[$cfHeaderName]) && $_SERVER[$cfHeaderName] === 'true') {
                if ($cfType === 'mobile') {
                    $type = 'phone';
                } else {
                    $type = $cfType;
                }
            }
        }

        if (!$type) {
            // android devices
            if (stripos($userAgent, 'android') !== false) {
                // unfortunately there are still android tablet that contain "Mobile" in user-agent, damn!
                if (stripos($userAgent, 'mobile') !== false) {
                    $type = 'phone';
                } else {
                    $type = 'tablet';
                }
            }

            // ios devices
            if (stripos($userAgent, 'ipad') !== false) {
                $type = 'tablet';
            }
            if (stripos($userAgent, 'iphone') !== false) {
                $type = 'phone';
            }

            // all other vendors, like blackberry, ...
            if (!$type && stripos($userAgent, 'mobile') !== false) {
                $type = 'phone';
            }
        }

        // default is desktop
        if (!$type) {
            $type = $this->default;
        }

        // check for a forced type
        $typeForced = null;
        if (isset($_REQUEST['forceDeviceType']) && $_REQUEST['forceDeviceType']) {
            $typeForced = $_REQUEST['forceDeviceType'];
            // check if cookie exists and differs from request device type -> request has priority
            if (isset($_COOKIE['forceDeviceType']) && $_COOKIE['forceDeviceType'] != $_REQUEST['forceDeviceType']) {
                unset($_COOKIE['forceDeviceType']);
            }
        }

        if (isset($_COOKIE['forceDeviceType']) && $_COOKIE['forceDeviceType']) {
            $typeForced = $_COOKIE['forceDeviceType'];
        }

        if ($typeForced) {
            if (in_array($typeForced, $this->validDeviceTypes)) {
                /**
                 * @psalm-taint-escape cookie
                 */
                $type = $typeForced;

                // we don't set a cookie if we're in preview mode, or if a cookie is set already
                if (!isset($_COOKIE['forceDeviceType']) && !isset($_REQUEST['pimcore_preview'])) {
                    setcookie('forceDeviceType', $type);
                }
            }
        }

        $this->{'is'.ucfirst($type)} = true;
        $this->determinedDeviceType = true;
    }
}
