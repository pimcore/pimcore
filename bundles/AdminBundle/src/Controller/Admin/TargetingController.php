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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Model\Tool\Targeting;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/targeting")
 *
 * @internal
 */
class TargetingController extends AdminController implements KernelControllerEventInterface
{
    // RULES

    private function correctName(string $name): string
    {
        return preg_replace('/[#?*:\\\\<>|"%&@=;+]/', '-', $name);
    }

    /**
     * @Route("/rule/list", name="pimcore_admin_targeting_rulelist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleListAction(Request $request): JsonResponse
    {
        $targets = [];

        $list = new Targeting\Rule\Listing();
        $list->setOrderKey('prio');
        $list->setOrder('ASC');

        foreach ($list->load() as $target) {
            $targets[] = [
                'id' => $target->getId(),
                'text' => htmlspecialchars($target->getName()),
                'active' => $target->getActive(),
                'qtip' => 'ID: ' . $target->getId(),
            ];
        }

        return $this->adminJson($targets);
    }

    /**
     * @Route("/rule/add", name="pimcore_admin_targeting_ruleadd", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleAddAction(Request $request): JsonResponse
    {
        $target = new Targeting\Rule();
        $target->setName($this->correctName($request->get('name')));
        $target->save();

        return $this->adminJson(['success' => true, 'id' => $target->getId()]);
    }

    /**
     * @Route("/rule/delete", name="pimcore_admin_targeting_ruledelete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleDeleteAction(Request $request): JsonResponse
    {
        $success = false;

        $target = Targeting\Rule::getById((int) $request->get('id'));
        if ($target) {
            $target->delete();
            $success = true;
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/rule/get", name="pimcore_admin_targeting_ruleget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleGetAction(Request $request): JsonResponse
    {
        $target = Targeting\Rule::getById((int) $request->get('id'));
        if (!$target) {
            throw $this->createNotFoundException();
        }
        $target = $target->getObjectVars();

        return $this->adminJson($target);
    }

    /**
     * @Route("/rule/save", name="pimcore_admin_targeting_rulesave", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleSaveAction(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request->get('data'));

        $target = Targeting\Rule::getById((int) $request->get('id'));
        if (!$target) {
            throw $this->createNotFoundException();
        }
        $target->setValues($data['settings']);
        $target->setName($this->correctName($target->getName()));
        $target->setConditions($data['conditions']);
        $target->setActions($data['actions']);
        $target->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/rule/order", name="pimcore_admin_targeting_ruleorder", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleOrderAction(Request $request): JsonResponse
    {
        $return = [
            'success' => false,
            'message' => '',
        ];

        $rules = $this->decodeJson($request->get('rules'));

        /** @var Targeting\Rule[] $changedRules */
        $changedRules = [];
        foreach ($rules as $id => $prio) {
            $rule = Targeting\Rule::getById((int)$id);
            $prio = (int)$prio;

            if ($rule) {
                if ($rule->getPrio() !== $prio) {
                    $rule->setPrio((int)$prio);
                    $changedRules[] = $rule;
                }
            } else {
                $return['message'] = sprintf('Rule %d was not found', (int)$id);

                return $this->adminJson($return, 400);
            }
        }

        // save only changed rules
        foreach ($changedRules as $changedRule) {
            $changedRule->save();
        }

        $return['success'] = true;

        return $this->adminJson($return);
    }

    // TARGET GROUPS

    /**
     * @Route("/target-group/list", name="pimcore_admin_targeting_targetgrouplist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function targetGroupListAction(Request $request): JsonResponse
    {
        $targetGroups = [];

        /** @var TargetGroup\Listing|TargetGroup\Listing\Dao $list */
        $list = new TargetGroup\Listing();

        if ($request->get('add-default')) {
            $targetGroups[] = [
                'id' => 0,
                'text' => 'default',
                'active' => true,
                'qtip' => 0,
            ];
        }

        foreach ($list->load() as $targetGroup) {
            $targetGroups[] = [
                'id' => $targetGroup->getId(),
                'text' => htmlspecialchars($targetGroup->getName()),
                'active' => $targetGroup->getActive(),
                'qtip' => $targetGroup->getId(),
            ];
        }

        return $this->adminJson($targetGroups);
    }

    /**
     * @Route("/target-group/add", name="pimcore_admin_targeting_targetgroupadd", methods={"POST"})
     *
     * @param Request $request
     * @param CoreCacheHandler $cache
     *
     * @return JsonResponse
     */
    public function targetGroupAddAction(Request $request, CoreCacheHandler $cache): JsonResponse
    {
        /** @var TargetGroup|TargetGroup\Dao $targetGroup */
        $targetGroup = new TargetGroup();
        $targetGroup->setName($this->correctName($request->get('name')));
        $targetGroup->save();

        $cache->clearTag('target_groups');

        return $this->adminJson(['success' => true, 'id' => $targetGroup->getId()]);
    }

    /**
     * @Route("/target-group/delete", name="pimcore_admin_targeting_targetgroupdelete", methods={"DELETE"})
     *
     * @param Request $request
     * @param CoreCacheHandler $cache
     *
     * @return JsonResponse
     */
    public function targetGroupDeleteAction(Request $request, CoreCacheHandler $cache): JsonResponse
    {
        $success = false;

        $targetGroup = TargetGroup::getById((int) $request->get('id'));
        if ($targetGroup) {
            $targetGroup->delete();
            $success = true;
        }

        $cache->clearTag('target_groups');

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/target-group/get", name="pimcore_admin_targeting_targetgroupget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function targetGroupGetAction(Request $request): JsonResponse
    {
        $targetGroup = TargetGroup::getById((int) $request->get('id'));
        if (!$targetGroup) {
            throw $this->createNotFoundException();
        }
        $targetGroup = $targetGroup->getObjectVars();

        return $this->adminJson($targetGroup);
    }

    /**
     * @Route("/target-group/save", name="pimcore_admin_targeting_targetgroupsave", methods={"PUT"})
     *
     * @param Request $request
     * @param CoreCacheHandler $cache
     *
     * @return JsonResponse
     */
    public function targetGroupSaveAction(Request $request, CoreCacheHandler $cache): JsonResponse
    {
        $data = $this->decodeJson($request->get('data'));

        $targetGroup = TargetGroup::getById((int) $request->get('id'));
        if (!$targetGroup) {
            throw $this->createNotFoundException();
        }
        $targetGroup->setValues($data['settings']);
        $targetGroup->setName($this->correctName($targetGroup->getName()));
        $targetGroup->save();

        $cache->clearTag('target_groups');

        return $this->adminJson(['success' => true]);
    }

    public function onKernelControllerEvent(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // check permissions
        $this->checkActionPermission($event, 'targeting', ['targetGroupListAction']);
    }
}
