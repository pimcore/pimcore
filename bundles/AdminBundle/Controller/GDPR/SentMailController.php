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

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Model\Tool\Email\Log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/export", name="pimcore_admin_gdpr_sentmail_exportdataobject", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function exportDataObjectAction(Request $request)
    {
        $this->checkPermission('emails');

        $sentMail = Log::getById($request->get('id'));

        $sentMailArray = (array)$sentMail;
        $sentMailArray['htmlBody'] = $sentMail->getHtmlLog();
        $sentMailArray['textBody'] = $sentMail->getTextLog();

        $json = $this->encodeJson($sentMailArray, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);
        $jsonResponse = new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-mail-' . $sentMail->getId() . '.json"',
        ], true);

        return $jsonResponse;
    }
}
