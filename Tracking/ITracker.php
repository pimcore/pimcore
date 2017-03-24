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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

interface ITracker
{
    /**
     * @param ITrackingItemBuilder $trackingItemBuilder
     */
    public function __construct(ITrackingItemBuilder $trackingItemBuilder, EngineInterface $renderer);

    /**
     * @return ITrackingItemBuilder
     */
    public function getTrackingItemBuilder();

    /**
     * @return mixed
     */
    public function includeDependencies();
}