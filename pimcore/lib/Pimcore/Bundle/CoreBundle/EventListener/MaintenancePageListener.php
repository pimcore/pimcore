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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Tool\Session;
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
     *
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
    public function loadTemplateFromResource($path)
    {
        $templateFile = $this->kernel->locateResource($path);
        if (file_exists($templateFile)) {
            $this->setTemplateCode(file_get_contents($templateFile));
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $maintenance = false;
        $file = \Pimcore\Tool\Admin::getMaintenanceModeFile();

        if (!is_file($file)) {
            return;
        }

        $conf = include($file);
        if (isset($conf['sessionId'])) {
            try {
                $requestSessionId = Session::getSessionIdFromRequest($event->getRequest());
            } catch (\Exception $e) {
                $requestSessionId = null;
            }

            if ($conf['sessionId'] != $requestSessionId) {
                $maintenance = true;
            }
        } else {
            @unlink($file);
        }

        // do not activate the maintenance for the server itself
        // this is to avoid problems with monitoring agents
        $serverIps = ['127.0.0.1'];

        if ($maintenance && !in_array($event->getRequest()->getClientIp(), $serverIps)) {
            $response = new Response($this->getTemplateCode(), 503);
            $event->setResponse($response);
        }
    }
}
