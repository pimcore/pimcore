<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Tool\DeviceDetector;
use Symfony\Component\Templating\Helper\Helper;

class Device extends Helper
{
    /**
     * @param null $default
     * @return DeviceDetector
     */
    public function __invoke($default = null)
    {
        return DeviceDetector::getInstance($default);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return "device";
    }
}
