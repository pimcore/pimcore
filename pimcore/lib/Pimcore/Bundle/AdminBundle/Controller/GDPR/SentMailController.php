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

use Pimcore\Model\Tool\Email\Log;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class SentMailController
 *
 * @Route("/sent-mail")
 *
 * @package GDPRDataExtractorBundle\Controller
 */
class SentMailController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{
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

    /**
     * @param Request $request
     * @Route("/export")
     */
    public function exportDataObjectAction(Request $request)
    {
        $sentMail = Log::getById($request->get('id'));

        $sentMailArray = (array)$sentMail;
        $sentMailArray['htmlBody'] = $sentMail->getHtmlLog();
        $sentMailArray['textBody'] = $sentMail->getTextLog();

        $jsonResponse = $this->json($sentMailArray);
        $jsonResponse->headers->set('Content-Disposition', 'attachment; filename="export-mail-' . $sentMail->getId() . '.json"');

        return $jsonResponse;
    }
}
