<?php

namespace Pimcore\Bundle\CoreBundle\EventListener\Traits;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;

trait RequestController
{
    /**
     * Extract the action name.
     *
     * @return string
     */
    public function getActionName(Request $request)
    {
        $action = $request->attributes->get('_controller');
        $action = explode('::', $action);

        // use this line if you want to remove the trailing "Action" string
        //return isset($action[1]) ? preg_replace('/Action$/', '', $action[1]) : false;

        return $action[1];
    }

    /**
     * Extract the controller name (only for the master request).
     *
     * @return string
     */
    public function getControllerName(Request $request): ?string
    {
        $controller = $request->attributes->get('_controller');
        $controller = explode('::', $controller);

        // use this line if you want to remove the trailing "Controller" string
        //return isset($controller[4]) ? preg_replace('/Controller$/', '', $controller[4]) : false;

        if(isset($controller[0]) ) {
            if(strstr($controller[0], '\\')) {
                return $controller[0];
            }
        }

        return null;
    }

    public function isPimcoreController(Request $request): bool
    {
        $controller = $this->getControllerName($request);
        if($controller) {
            $controller = new \ReflectionClass('\\' . $controller);
            return $controller->isSubclassOf(FrontendController::class) ||
                $controller->isSubclassOf(AdminAbstractController::class) ||
                $controller->isSubclassOf(\Pimcore\Controller\Controller::class);


        }
        return false;
    }
}
