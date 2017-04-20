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

namespace Pimcore\Bundle\AdminBundle\Controller\Reports;

use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Tool\Qrcode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/qrcode")
 */
class QrcodeController extends ReportsControllerBase implements EventedControllerInterface
{
    /**
     * @Route("/tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeAction(Request $request)
    {
        $codes = [];

        $list = new Qrcode\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $codes[] = [
                'id' => $item->getName(),
                'text' => $item->getName()
            ];
        }

        return $this->json($codes);
    }

    /**
     * @Route("/add")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $success = false;

        $code = Qrcode\Config::getByName($request->get('name'));

        if (!$code) {
            $code = new Qrcode\Config();
            $code->setName($request->get('name'));
            $code->save();

            $success = true;
        }

        return $this->json(['success' => $success, 'id' => $code->getName()]);
    }

    /**
     * @Route("/delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        $code = Qrcode\Config::getByName($request->get('name'));
        $code->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $code = Qrcode\Config::getByName($request->get('name'));

        return $this->json($code);
    }

    /**
     * @Route("/update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $code = Qrcode\Config::getByName($request->get('name'));
        $data = $this->decodeJson($request->get('configuration'));

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($code, $setter)) {
                $code->$setter($value);
            }
        }

        $code->save();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/code")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function codeAction(Request $request)
    {
        $url = '';

        if ($request->get('name')) {
            $url = $request->getScheme() . '://' . $request->getHttpHost() . '/qr~-~code/' .
                $request->get('name');
        } elseif ($request->get('documentId')) {
            $doc = Document::getById($request->get('documentId'));
            $url = $request->getScheme() . '://' . $request->getHttpHost()
                . $doc->getFullPath();
        } elseif ($request->get('url')) {
            $url = $request->get('url');
        }

        $code = new \Endroid\QrCode\QrCode;
        $code->setText($url);
        $code->setPadding(0);
        $code->setSize(500);

        $hexToRGBA = function ($hex) {
            list($r, $g, $b) = sscanf($hex, '#%02x%02x%02x');

            return ['r' => $r, 'g' => $g, 'b' => $b, 'a' => 0];
        };

        if (strlen($request->get('foreColor', '')) == 7) {
            $code->setForegroundColor($hexToRGBA($request->get('foreColor')));
        }

        if (strlen($request->get('backgroundColor', '')) == 7) {
            $code->setBackgroundColor($hexToRGBA($request->get('backgroundColor')));
        }

        $tmpFile = PIMCORE_PRIVATE_VAR . '/qr-code-' . uniqid() . '.png';
        $code->render($tmpFile);
        $response = new BinaryFileResponse($tmpFile);

        if ($request->get('download')) {
            $code->setSize(4000);
            $response->setContentDisposition('attachment', 'qrcode-' . $request->get('name', 'preview') . '.png');
        }

        $response->deleteFileAfterSend(true);
        $response->headers->set('Content-Type', 'image/png');

        return $response;
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

        $this->checkActionPermission($event, 'qr_codes', ['codeAction']);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
