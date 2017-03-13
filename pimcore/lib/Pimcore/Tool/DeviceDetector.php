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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

class DeviceDetector
{

    /**
     * @var array
     */
    protected $validDeviceTypes = ["phone", "tablet", "desktop"];

    /**
     * @var null|string
     */
    protected $default = "desktop";

    /**
     * @var bool
     */
    protected $isPhone = false;

    /**
     * @var bool
     */
    protected $isDesktop = false;

    /**
     * @var bool
     */
    protected $isTablet = false;

    /**
     * @var null|DeviceDetector
     */
    protected static $instance = null;

    /**
     * @var bool
     */
    protected $determinedDeviceType = false;

    /**
     * @var bool
     */
    protected $wasUsed = false;

    /**
     * @param null $default
     * @return DeviceDetector
     */
    public static function getInstance($default = null)
    {
        if (!self::$instance) {
            self::$instance = new self($default);
        }

        return self::$instance;
    }

    /**
     * @param null $default
     */
    public function __construct($default = null)
    {
        if ($default && in_array($default, ["desktop", "mobile", "tablet"])) {
            $this->default = $default;
        }
    }

    /**
     * @return bool
     */
    public function isDesktop()
    {
        $this->determineDeviceType();

        return $this->isDesktop;
    }

    /**
     * @return bool
     */
    public function isTablet()
    {
        $this->determineDeviceType();

        return $this->isTablet;
    }

    /**
     * @return bool
     */
    public function isPhone()
    {
        $this->determineDeviceType();

        return $this->isPhone;
    }

    /**
     * @return bool
     */
    public function wasUsed()
    {
        return $this->wasUsed;
    }

    /**
     * @param $wasUsed
     */
    public function setWasUsed($wasUsed)
    {
        $this->wasUsed = $wasUsed;
    }

    /**
     * @return string
     */
    public function getDevice()
    {
        foreach ($this->validDeviceTypes as $deviceType) {
            if ($this->{"is".ucfirst($deviceType)}()) {
                return $deviceType;
            }
        }

        return $this->default;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDevice();
    }

    /**
     *
     */
    protected function determineDeviceType()
    {
        $this->setWasUsed(true);

        if ($this->determinedDeviceType) {
            return;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $type = null;

        // android devices
        if (stripos($userAgent, "android") !== false) {
            // unfortunately there are still android tablet that contain "Mobile" in user-agent, damn!
            if (stripos($userAgent, "mobile") !== false) {
                $type = "phone";
            } else {
                $type = "tablet";
            }
        }

        // ios devices
        if (stripos($userAgent, "ipad") !== false) {
            $type = "tablet";
        }
        if (stripos($userAgent, "iphone") !== false) {
            $type = "phone";
        }

        // all other vendors, like blackberry, ...
        if (!$type && stripos($userAgent, "mobile") !== false) {
            $type = "phone";
        }

        // default is desktop
        if (!$type) {
            $type = $this->default;
        }

        // check for a forced type
        $typeForced = null;
        if (isset($_REQUEST["forceDeviceType"]) && $_REQUEST["forceDeviceType"]) {
            $typeForced = $_REQUEST["forceDeviceType"];
        }

        if (isset($_COOKIE["forceDeviceType"]) && $_COOKIE["forceDeviceType"]) {
            $typeForced = $_COOKIE["forceDeviceType"];
        }

        if ($typeForced) {
            if (in_array($typeForced, $this->validDeviceTypes)) {
                $type = $typeForced;

                if (!isset($_COOKIE["forceDeviceType"])) {
                    setcookie("forceDeviceType", $type);
                }
            }
        }

        $this->{"is".ucfirst($type)} = true;
        $this->determinedDeviceType = true;
    }
}
