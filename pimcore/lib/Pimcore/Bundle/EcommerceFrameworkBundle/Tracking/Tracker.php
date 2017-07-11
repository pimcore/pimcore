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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

use Pimcore\Google\Analytics;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

abstract class Tracker implements ITracker
{
    /**
     * @var ITrackingItemBuilder
     */
    protected $trackingItemBuilder;

    /**
     * @var EngineInterface
     */
    protected $templatingEngine;

    /**
     * @var bool
     */
    private $dependenciesIncluded = false;

    /**
     * @var array
     */
    protected $dependencies = [];

    public function __construct(ITrackingItemBuilder $trackingItemBuilder, EngineInterface $templatingEngine)
    {
        $this->trackingItemBuilder = $trackingItemBuilder;
        $this->templatingEngine    = $templatingEngine;
    }

    /**
     * @inheritdoc
     */
    public function getTrackingItemBuilder()
    {
        return $this->trackingItemBuilder;
    }

    /**
     * View script prefix
     *
     * @return string
     */
    abstract protected function getViewScriptPrefix();

    /**
     * Get path to view script
     *
     * @param string $name
     *
     * @return string
     */
    protected function getViewScript($name)
    {
        return sprintf(
            'PimcoreEcommerceFrameworkBundle:Tracking/%s:%s.js.php',
            $this->getViewScriptPrefix(),
            $name
        );
    }

    /**
     * Remove null values from an object, keep protected keys in any case
     *
     * @param array $data
     * @param array $protectedKeys
     *
     * @return array
     */
    protected function filterNullValues(array $data, array $protectedKeys = [])
    {
        $result = [];
        foreach ($data as $key => $value) {
            $isProtected = in_array($key, $protectedKeys);
            if (null !== $value || $isProtected) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Include all defined google dependencies of this tracker
     * and only include them once in the script.
     */
    public function includeDependencies()
    {
        if ($this->dependenciesIncluded) {
            return;
        }

        if (0 === count($this->dependencies)) {
            return;
        }

        foreach ($this->dependencies as $dependency) {
            Analytics::addAdditionalCode("ga('require', '" . $dependency . "')", 'beforePageview');
        }

        $this->dependenciesIncluded = true;
    }
}
