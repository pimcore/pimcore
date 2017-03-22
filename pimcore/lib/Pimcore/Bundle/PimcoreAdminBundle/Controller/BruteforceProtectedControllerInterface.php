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

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;
use Pimcore\Bundle\PimcoreAdminBundle\EventListener\BruteforceProtectionListener;

/**
 * Tagging interface used to protect certain controllers from brute force attacks
 *
 * @see BruteforceProtectionListener
 */
interface BruteforceProtectedControllerInterface
{
}
