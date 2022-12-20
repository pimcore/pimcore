<?php
declare(strict_types=1);

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
use Symfony\Contracts\Translation\LocaleAwareInterface;
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
    protected TokenStorageUserResolver $tokenResolver;

    protected TranslatorInterface $translator;

    /**
     * AdminController constructor.
     *
     * @param TokenStorageUserResolver $tokenStorageUserResolver
     * @param TranslatorInterface $translator
     */
    public function __construct(TokenStorageUserResolver $tokenStorageUserResolver, TranslatorInterface $translator)
    {
        $this->tokenResolver = $tokenStorageUserResolver;
        $this->translator = $translator;
    }

    public function onKernelControllerEvent(ControllerEvent $event)
    {
        // set language
        $user = $this->tokenResolver->getUser();

        if ($user) {
            if ($this->translator instanceof LocaleAwareInterface) {
                $this->translator->setLocale($user->getLanguage());
            }
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
    public function voucherCodeTabAction(Request $request): Response
    {
        $onlineShopVoucherSeries = OnlineShopVoucherSeries::getById((int) $request->get('id'));

        if (!$onlineShopVoucherSeries) {
            throw $this->createNotFoundException('Voucher series not found');
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
    public function exportTokensAction(Request $request): Response
    {
        $onlineShopVoucherSeries = OnlineShopVoucherSeries::getById((int) $request->get('id'));

        if (!$onlineShopVoucherSeries) {
            throw $this->createNotFoundException('Voucher series not found');
        }

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
        $response->headers->set('Content-Length', (string) strlen($result));

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
        $onlineShopVoucherSeries = OnlineShopVoucherSeries::getById((int) $request->get('id'));

        if (!$onlineShopVoucherSeries) {
            throw $this->createNotFoundException('Could not get voucher series, probably you did not provide a correct id.');
        }

        if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
            $result = $tokenManager->insertOrUpdateVoucherSeries();

            $params = ['id' => $request->get('id')]; //$request->query->all();

            if ($result === false) {
                $params['error'] = $this->translator->trans('bundle_ecommerce_voucherservice_msg-error-generation', [], 'admin');
            } else {
                $params['success'] = $this->translator->trans('bundle_ecommerce_voucherservice_msg-success-generation', [], 'admin');
            }

            return $this->redirectToRoute(
                'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                $params
            );
        }
    }

    /**
     * Removes tokens due to given filter parameters.
     *
     * @Route("/cleanup", name="pimcore_ecommerce_backend_voucher_cleanup", methods={"POST"})
     */
    public function cleanupAction(Request $request)
    {
        $onlineShopVoucherSeries = OnlineShopVoucherSeries::getById((int) $request->get('id'));

        if (!$onlineShopVoucherSeries) {
            throw $this->createNotFoundException('Could not get voucher series, probably you did not provide a correct id.');
        }
        if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
            // Prepare cleanUp parameter array.
            $params = ['id' => $request->get('id')]; // $request->query->all();
            $request->get('usage') ? $params['usage'] = $request->get('usage') : '';
            $request->get('olderThan') ? $params['olderThan'] = $request->get('olderThan') : '';

            if (empty($params['usage'])) {
                $params['error'] = $this->translator->trans('bundle_ecommerce_voucherservice_msg-error-required-missing', [], 'admin');
            } elseif ($tokenManager->cleanUpCodes($params)) {
                $params['success'] = $this->translator->trans('bundle_ecommerce_voucherservice_msg-success-cleanup', [], 'admin');
            } else {
                $params['error'] = $this->translator->trans('bundle_ecommerce_voucherservice_msg-error-cleanup', [], 'admin');
            }

            return $this->redirectToRoute(
                'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                $params
            );
        }
    }

    /**
     * Removes token reservations due to given duration.
     *
     * @Route("/cleanup-reservations", name="pimcore_ecommerce_backend_voucher_cleanup-reservations", methods={"POST"})
     *
     */
    public function cleanupReservationsAction(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $duration = $request->get('duration');
        $id = $request->get('id');

        if (!isset($duration)) {
            return $this->redirectToRoute(
                'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                ['error' => $this->translator->trans('bundle_ecommerce_voucherservice_msg-error-cleanup-reservations-duration-missing', [], 'admin'), 'id' => $id]
            );
        }

        $onlineShopVoucherSeries = DataObject::getById($id);
        if ($onlineShopVoucherSeries instanceof OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                if ($tokenManager->cleanUpReservations($duration, $id)) {
                    return $this->redirectToRoute(
                        'pimcore_ecommerce_backend_voucher_voucher-code-tab',
                        ['success' => $this->translator->trans('bundle_ecommerce_voucherservice_msg-success-cleanup-reservations', [], 'admin'), 'id' => $id]
                    );
                }
            }
        }

        return $this->redirectToRoute(
            'pimcore_ecommerce_backend_voucher_voucher-code-tab',
            ['error' => $this->translator->trans('bundle_ecommerce_voucherservice_msg-error-cleanup-reservations', [], 'admin'), 'id' => $id]
        );
    }
}
