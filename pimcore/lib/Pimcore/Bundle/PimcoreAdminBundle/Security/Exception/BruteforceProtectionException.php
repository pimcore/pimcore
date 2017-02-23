<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BruteforceProtectionException extends AccessDeniedHttpException
{
}
