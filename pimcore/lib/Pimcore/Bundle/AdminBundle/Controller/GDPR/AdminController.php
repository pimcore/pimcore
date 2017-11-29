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

namespace Pimcore\Bundle\AdminBundle\Controller\GDPR;


use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\Manager;
use Pimcore\Bundle\AdminBundle\Security\User\Exception\InvalidUserException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class AdminController
 *
 * @package GDPRDataExtractorBundle\Controller
 */
class AdminController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{
    /**
     * @Route("/get-data-providers")
     */
    public function getDataProvidersAction(Request $request, Manager $manager)
    {
        $services = $manager->getServices();

        $response = [];

        foreach($services as $service) {

            $response[] = [
                'name' => $service->getName(),
                'jsClass' => $service->getJsClassName()
            ];

        }
        return $this->json($response);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

}
