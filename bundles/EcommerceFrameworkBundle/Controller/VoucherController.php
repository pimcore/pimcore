<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ExportableTokenManagerInterface;
use Pimcore\Controller\FrontendController;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\OnlineShopVoucherSeries;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class VoucherController
 *
 * @Route("/voucher")
 *
 * @internal
 */
class VoucherController extends FrontendController implements KernelControllerEventInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services[TokenStorageUserResolver::class] = TokenStorageUserResolver::class;

        return $services;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelControllerEvent(ControllerEvent $event)
    {
        // set language
        $user = $this->get(TokenStorageUserResolver::class)->getUser();

        if ($user) {
            $this->get('translator')->setLocale($user->getLanguage());
            $event->getRequest()->setLocale($user->getLanguage());
        }

        // enable inherited values
        DataObject::setGetInheritedValues(true);
        Localizedfield::setGetFallbackValues(true);
    }

    /**
     * Loads and shows voucherservice backend tab
     *
     * @Route("/voucher-code-tab", name="pimcore_ecommerce_backend_voucher_voucher-code-tab", methods={"GET"})
     */
    public function voucherCodeTabAction(Request $request)
    {
        $onlineShopVoucherSeries = DataObject::getById($request->get('id'));

        if (!($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries)) {
            throw new \InvalidArgumentException('Voucher series not found');
        }

        $paramsBag = [];
        if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
            $paramsBag['series'] = $onlineShopVoucherSeries;
            $paramsBag['voucherType'] = $tokenManager->getConfiguration()->getType();

            if ($tokenManager instanceof ExportableTokenManagerInterface) {
                $paramsBag['supportsExport'] = true;
            }

            $renderScript = $tokenManager->prepareConfigurationView($paramsBag, $request->query->all());

            return $this->render($renderScript, $paramsBag);
        } else {
            $paramsBag['errors'] = ['bundle_ecommerce_voucherservice_msg-error-config-missing'];

            return $this->render('@PimcoreEcommerceFramework/voucher/voucher_code_tab_error.html.twig', $paramsBag);
        }
    }

    /**
     * Export tokens to file. The action should implement all export formats defined in ExportableTokenManagerInterface.
     *
     * @Route("/export-tokens", name="pimcore_ecommerce_backend_voucher_export-tokens", methods={"GET"})
     */
    public function exportTokensAction(Request $request)
    {
        $onlineShopVoucherSeries = DataObject::getById($request->get('id'));
        if (!($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries)) {
            throw new \InvalidArgumentException('Voucher series not found');
        }

        /** @var \Pimcore\Model\DataObject\OnlineShopVoucherSeries $onlineShopVoucherSeries */
        $tokenManager = $onlineShopVoucherSeries->getTokenManager();
        if (!(null !== $tokenManager && $tokenManager instanceof ExportableTokenManagerInterface)) {
            throw new \InvalidArgumentException('Token manager does not support exporting');
        }

        $format = $request->get('format', ExportableTokenManagerInterface::FORMAT_CSV);
        $contentType = null;
        $suffix = null;
        $download = true;

        switch ($format) {
            case ExportableTokenManagerInterface::FORMAT_CSV:
                $result = $tokenManager->exportCsv($request->query->all());
                $contentType = 'text/csv';
                $suffix = 'csv';

                break;

            case ExportableTokenManagerInterface::FORMAT_PLAIN:
                $result = $tokenManager->exportPlain($request->query->all());
                $contentType = 'text/plain';
                $suffix = 'txt';
                $download = false;

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

    /**
     * Generates new Tokens or Applies single token settings.
     *
     * @Route("/generate", name="pimcore_ecommerce_backend_voucher_generate", methods={"GET"})
     */
    public function generateAction(Request $request)
    {
        $onlineShopVoucherSeries = DataObject::getById($request->get('id'));
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $result = $tokenManager->insertOrUpdateVoucherSeries();

                $translator = $this->get('translator');
                $params = ['id' => $request->get('id')]; //$request->query->all();

                if ($result === false) {
                    $params['error'] = $translator->trans('bundle_ecommerce_voucherservice_msg-error-generation', [], 'admin');
                } else {
                    $params['success'] = $translator->trans('bundle_ecommerce_voucherservice_msg-success-generation', [], 'admin');
                }

                return $this->redirectToRoute(
                    'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                    $params
                );
            }
        } else {
            throw new \InvalidArgumentException('Could not get voucher series, probably you did not provide a correct id.');
        }
    }

    /**
     * Removes tokens due to given filter parameters.
     *
     * @Route("/cleanup", name="pimcore_ecommerce_backend_voucher_cleanup", methods={"POST"})
     */
    public function cleanupAction(Request $request)
    {
        $onlineShopVoucherSeries = DataObject::getById($request->get('id'));
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $translator = $this->get('translator');

                // Prepare cleanUp parameter array.
                $params = ['id' => $request->get('id')]; // $request->query->all();
                $request->get('usage') ? $params['usage'] = $request->get('usage') : '';
                $request->get('olderThan') ? $params['olderThan'] = $request->get('olderThan') : '';

                if (empty($params['usage'])) {
                    $params['error'] = $translator->trans('bundle_ecommerce_voucherservice_msg-error-required-missing', [], 'admin');
                } elseif ($tokenManager->cleanUpCodes($params)) {
                    $params['success'] = $translator->trans('bundle_ecommerce_voucherservice_msg-success-cleanup', [], 'admin');
                } else {
                    $params['error'] = $translator->trans('bundle_ecommerce_voucherservice_msg-error-cleanup', [], 'admin');
                }

                return $this->redirectToRoute(
                    'pimcore_ecommerce_backend_voucher_voucher-code-tab',
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
     * @Route("/cleanup-reservations", name="pimcore_ecommerce_backend_voucher_cleanup-reservations", methods={"POST"})
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function cleanupReservationsAction(Request $request)
    {
        $duration = $request->get('duration');
        $id = $request->get('id');
        $translator = $this->get('translator');

        if (!isset($duration)) {
            return $this->redirectToRoute(
                'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                ['error' => $translator->trans('bundle_ecommerce_voucherservice_msg-error-cleanup-reservations-duration-missing', [], 'admin'), 'id' => $id]
            );
        }

        $onlineShopVoucherSeries = DataObject::getById($id);
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                if ($tokenManager->cleanUpReservations($duration, $id)) {
                    return $this->redirectToRoute(
                        'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                        ['success' => $translator->trans('bundle_ecommerce_voucherservice_msg-success-cleanup-reservations', [], 'admin'), 'id' => $id]
                    );
                }
            }
        }

        return $this->redirectToRoute(
            'pimcore_ecommerce_backend_voucher_voucher-code-tab',
            ['error' => $translator->trans('bundle_ecommerce_voucherservice_msg-error-cleanup-reservations', [], 'admin'), 'id' => $id]
        );
    }
}
