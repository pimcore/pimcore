<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\EventListener\AdminSessionListener;

/**
 * Tagging interface defining controller as admin controller.
 *
 * @see AdminSessionListener
 */
interface AdminControllerInterface extends DoubleAuthenticationControllerInterface
{
}
