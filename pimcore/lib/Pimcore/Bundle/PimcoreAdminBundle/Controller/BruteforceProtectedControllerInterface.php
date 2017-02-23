<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Will be used by the bruteforce protection listener to determine if bruteforce protection is necessary.
 */
interface BruteforceProtectedControllerInterface
{
}
