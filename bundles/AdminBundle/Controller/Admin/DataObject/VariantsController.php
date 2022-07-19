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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/variants", name="pimcore_admin_dataobject_variants_")
 *
 * @internal
 */
class VariantsController extends AdminController
{
    use DataObjectActionsTrait;

    /**
     * @Route("/update-key", name="updatekey", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateKeyAction(Request $request): JsonResponse
    {
        $id = $request->get('id');
        $key = $request->get('key');
        $object = DataObject\Concrete::getById($id);

        return $this->adminJson($this->renameObject($object, $key));
    }

    /**
     * @Route("/get-variants", name="getvariants", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     * @param GridHelperService $gridHelperService
     * @param LocaleServiceInterface $localeService
     * @param CsrfProtectionHandler $csrfProtection
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getVariantsAction(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        GridHelperService $gridHelperService,
        LocaleServiceInterface $localeService,
        CsrfProtectionHandler $csrfProtection
    ): JsonResponse {
        $parentObject = DataObject\Concrete::getById((int) $request->get('objectId'));
        if (empty($parentObject)) {
            throw new \Exception('No Object found with id ' . $request->get('objectId'));
        }

        if (!$parentObject->isAllowed('view')) {
            throw new \Exception('Permission denied');
        }

        $allParams = array_merge($request->request->all(), $request->query->all());
        $allParams['folderId'] = $parentObject->getId();
        $allParams['classId'] = $parentObject->getClassId();

        $csrfProtection->checkCsrfToken($request);

        $result = $this->gridProxy(
            $allParams,
            DataObject::OBJECT_TYPE_VARIANT,
            $request,
            $eventDispatcher,
            $gridHelperService,
            $localeService
        );

        return $this->adminJson($result);
    }
}
