<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\ControllerApi;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;

abstract class AbstractApiController extends AdminController
{
    /**
     * @inheritDoc
     */
    public function needsSessionDoubleAuthenticationCheck()
    {
        return false;
    }
}
