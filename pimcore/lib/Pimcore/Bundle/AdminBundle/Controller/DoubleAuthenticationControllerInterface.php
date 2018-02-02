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

namespace Pimcore\Bundle\AdminBundle\Controller;

/**
 * Controllers implementing this interface will be double-checked for admin authentication.
 *
 * @see AdminAuthenticationDoubleCheckListener
 */
interface DoubleAuthenticationControllerInterface
{
    /**
     * Determines if session should be checked for a valid user in authentication double check
     *
     * @return bool
     */
    public function needsSessionDoubleAuthenticationCheck();

    /**
     * Determines if token storage should be checked for a valid user in authentication double check
     *
     * @return bool
     */
    public function needsStorageDoubleAuthenticationCheck();
}
