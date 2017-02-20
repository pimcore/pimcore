<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\TemplatePhp;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Config;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginController extends Controller
{
    /**
     * @Route("/login", name="admin_login")
     * @TemplatePhp()
     */
    public function loginAction(Request $request)
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('admin_index');
        }

        $view = new ViewModel([
            'config' => Config::getSystemConfig()
        ]);

        if ($request->get('auth_failed')) {
            $view->error = 'error_auth_failed';
        }
        if ($request->get('session_expired')) {
            $view->error = 'error_session_expired';
        }

        $this->addPluginAssets($view);

        return $view;
    }

    /**
     * @param ViewModel $view
     * @return $this
     */
    protected function addPluginAssets(ViewModel $view)
    {
        $bundleManager = $this->get('pimcore.extension.bundle_manager');

        $view->pluginCssPaths = $bundleManager->getCssPaths();

        return $this;
    }

    /**
     * @Route("/login/lostpassword", name="admin_login_lost_password")
     */
    public function lostPasswordAction()
    {
        // TODO implement
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * @Route("/login/deeplink", name="admin_login_deeplink")
     */
    public function deeplinkAction()
    {
        // TODO implement
        throw new \RuntimeException('Not implemented yet');
    }
}
