<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\EventListener\AdminAuthenticationDoubleCheckListener;

/**
 * Tagging interface defining controller as admin controller.
 *
 * @see AdminAuthenticationDoubleCheckListener
 */
interface AdminControllerInterface
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
