<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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