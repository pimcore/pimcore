<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

/**
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
