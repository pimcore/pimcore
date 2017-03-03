<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;

class MaintenancePageListener
{
    use ResponseInjectionTrait;

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @var string
     */
    protected $templateCode = null;

    /**
     * CookiePolicyNotice constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param $code
     */
    public function setTemplateCode($code)
    {
        $this->templateCode = $code;
    }

    /**
     * @return string
     */
    public function getTemplateCode()
    {
        return $this->templateCode;
    }

    /**
     * @param string $path
     */
    public function loadTemplateFromResource($path) {
        $templateFile = $this->kernel->locateResource($path);
        if(file_exists($templateFile)) {
            $this->setTemplateCode(file_get_contents($templateFile));
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent  $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $maintenance = false;
        $file = \Pimcore\Tool\Admin::getMaintenanceModeFile();

        if (is_file($file)) {
            $conf = include($file);
            if (isset($conf["sessionId"])) {
                if ($conf["sessionId"] != $_COOKIE["pimcore_admin_sid"]) {
                    $maintenance = true;
                }
            } else {
                @unlink($file);
            }
        }

        // do not activate the maintenance for the server itself
        // this is to avoid problems with monitoring agents
        $serverIps = ["127.0.0.1"];

        if ($maintenance && !in_array($event->getRequest()->getClientIp(), $serverIps)) {
            $response = new Response($this->getTemplateCode(), 503);
            $event->setResponse($response);
        }
    }
}
