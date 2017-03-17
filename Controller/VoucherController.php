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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Controller\FrontendController;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\TokenManager\IExportableTokenManager;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Localizedfield;
use Pimcore\Model\Object\OnlineShopVoucherSeries;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class VoucherController
 * @Route("/voucher")
 */
class VoucherController extends FrontendController
{

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // set language
        $user = $this->get('pimcore_admin.security.token_storage_user_resolver')->getUser();
        if($user) {
            $this->get("translator")->setLocale($user->getLanguage());
            $event->getRequest()->setLocale($user->getLanguage());
        }

        // enable inherited values
        AbstractObject::setGetInheritedValues(true);
        Localizedfield::setGetFallbackValues(true);

        // enable view auto-rendering
        $this->setViewAutoRender($event->getRequest(), true, 'php');
    }


    /**
     * Loads and shows voucherservice backend tab
     * @Route("/voucher-code-tab", name="pimcore_ecommerce_backend_voucher_voucher-code-tab")
     */
    public function voucherCodeTabAction(Request $request)
    {
        $onlineShopVoucherSeries = AbstractObject::getById($request->get('id'));

        if (!($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries)) {
            throw new \InvalidArgumentException('Voucher series not found');
        }

        $paramsBag = [];
        if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
            $paramsBag['series'] = $onlineShopVoucherSeries;

            if ($tokenManager instanceof IExportableTokenManager) {
                $paramsBag['supportsExport'] = true;
            }

            $renderScript = $tokenManager->prepareConfigurationView($paramsBag, $request->query->all());
            return $this->render($renderScript, $paramsBag);
        } else {
            $paramsBag['errors'] = ['plugin_onlineshop_voucherservice_msg-error-config-missing'];
            return $this->render('PimcoreEcommerceFrameworkBundle:Voucher:voucherCodeTabError.html.php', $paramsBag);
        }
    }

    /**
     * Export tokens to file. The action should implement all export formats defined in IExportableTokenManager.
     * @Route("/export-tokens", name="pimcore_ecommerce_backend_voucher_export-tokens")
     */
    public function exportTokensAction(Request $request)
    {
        $onlineShopVoucherSeries = AbstractObject::getById($request->get('id'));
        if (!($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries)) {
            throw new \InvalidArgumentException('Voucher series not found');
        }

        /** @var \Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries */
        $tokenManager = $onlineShopVoucherSeries->getTokenManager();
        if (!(null !== $tokenManager && $tokenManager instanceof IExportableTokenManager)) {
            throw new \InvalidArgumentException('Token manager does not support exporting');
        }

        $format      = $request->get('format', IExportableTokenManager::FORMAT_CSV);
        $contentType = null;
        $suffix      = null;
        $download    = true;

        $result = '';
        switch ($format) {
            case IExportableTokenManager::FORMAT_CSV:
                $result      = $tokenManager->exportCsv($request->query->all());
                $contentType = 'text/csv';
                $suffix      = 'csv';
                break;

            case IExportableTokenManager::FORMAT_PLAIN:
                $result      = $tokenManager->exportPlain($request->query->all());
                $contentType = 'text/plain';
                $suffix      = 'txt';
                $download    = false;
                break;

            default:
                throw new \InvalidArgumentException('Invalid format');
        }

        $response = new Response($result);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Length', strlen($result));

        if ($download && null !== $suffix) {
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="voucher-export.%s"', $suffix));
        }

        return $response;
    }

//    /**
//     * @param OnlineShopVoucherSeries $onlineShopVoucherSeries
//     * @param \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager $tokenManager
//     * @param array $params
//     * @return Response
//     */
//    protected function renderTab(\Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries, \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager $tokenManager, $params = [])
//    {
//        $paramsBag = [];
//        $paramsBag['series'] = $onlineShopVoucherSeries;
//        $renderScript = $tokenManager->prepareConfigurationView($paramsBag, $params);
//        return $this->render($renderScript);
//    }

    /**
     * Generates new Tokens or Applies single token settings.
     * @Route("/generate", name="pimcore_ecommerce_backend_voucher_generate")
     */
    public function generateAction(Request $request)
    {
        $onlineShopVoucherSeries = AbstractObject::getById($request->get('id'));
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $result = $tokenManager->insertOrUpdateVoucherSeries();

                $translator = $this->get("translator");
                $params = ['id' => $request->get('id')]; //$request->query->all();

                if ($result == true) {
                    $params['success'] = $translator->trans('plugin_onlineshop_voucherservice_msg-success-generation', [], 'admin');
                } else {
                    $params['error'] = $translator->trans('plugin_onlineshop_voucherservice_msg-error-generation', [], 'admin');
                }

                return $this->redirectToRoute(
                    "pimcore_ecommerce_backend_voucher_voucher-code-tab",
                    $params
                );
            }
        } else {
            throw new \InvalidArgumentException('Could not get voucher series, probably you did not provide a correct id.');
        }
    }

    /**
     * Removes tokens due to given filter parameters.
     * @Route("/cleanup", name="pimcore_ecommerce_backend_voucher_cleanup")
     */
    public function cleanupAction(Request $request)
    {
        $onlineShopVoucherSeries = AbstractObject::getById($request->get('id'));
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {

                $translator = $this->get('translator');

                // Prepare cleanUp parameter array.
                $params = ['id' => $request->get('id')]; // $request->query->all();
                $request->get('usage') ? $params['usage'] = $request->get('usage') : '';
                $request->get('olderThan') ? $params['olderThan'] = $request->get('olderThan') : '';

                if (empty($params['usage'])) {
                    $params['error'] = $translator->trans('plugin_onlineshop_voucherservice_msg-error-required-missing', [], 'admin');
                } else if ($tokenManager->cleanUpCodes($params)) {
                    $params['success'] = $translator->trans('plugin_onlineshop_voucherservice_msg-success-cleanup', [], 'admin');
                } else {
                    $params['error'] = $translator->trans('plugin_onlineshop_voucherservice_msg-error-cleanup', [], 'admin');
                }

                return $this->redirectToRoute(
                    "pimcore_ecommerce_backend_voucher_voucher-code-tab",
                    $params
                );

            }
        } else {
            throw new \InvalidArgumentException('Could not get voucher series, probably you did not provide a correct id.');
        }
    }

    /**
     * Removes token reservations due to given duration.
     *
     * @Route("/cleanup-reservations", name="pimcore_ecommerce_backend_voucher_cleanup-reservations")
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function cleanupReservationsAction(Request $request)
    {
        $duration = $request->get('duration');
        $id = $request->get('id');
        $translator = $this->get("translator");

        if(!isset($duration)) {
            return $this->redirectToRoute(
                "pimcore_ecommerce_backend_voucher_voucher-code-tab",
                ['error' => $translator->trans('plugin_onlineshop_voucherservice_msg-error-cleanup-reservations-duration-missing', [], 'admin'), 'id' => $id]
            );
        }

        $onlineShopVoucherSeries = AbstractObject::getById($id);
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                if ($tokenManager->cleanUpReservations($duration, $id)) {

                    return $this->redirectToRoute(
                        "pimcore_ecommerce_backend_voucher_voucher-code-tab",
                        ['success' => $translator->trans('plugin_onlineshop_voucherservice_msg-success-cleanup-reservations', [], 'admin'), 'id' => $id]
                    );

                }
            }
        }

        return $this->redirectToRoute(
            "pimcore_ecommerce_backend_voucher_voucher-code-tab",
            ['error' => $translator->trans('plugin_onlineshop_voucherservice_msg-error-cleanup-reservations', [], 'admin'), 'id' => $id]
        );

    }

}



