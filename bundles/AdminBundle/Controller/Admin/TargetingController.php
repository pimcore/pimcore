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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\Model\TargetGroupEvent;
use Pimcore\Event\TargetGroupEvents;
use Pimcore\Model\Tool\Targeting;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/targeting")
 */
class TargetingController extends AdminController implements EventedControllerInterface
{
    /* RULES */

    /**
     * @Route("/rule/list", name="pimcore_admin_targeting_rulelist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleListAction(Request $request)
    {
        $targets = [];

        $list = new Targeting\Rule\Listing();
        $list->setOrderKey('prio');
        $list->setOrder('ASC');

        /** @var Targeting\Rule $target */
        foreach ($list->load() as $target) {
            $targets[] = [
                'id' => $target->getId(),
                'text' => $target->getName(),
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
    public function ruleAddAction(Request $request)
    {
        $target = new Targeting\Rule();
        $target->setName($request->get('name'));
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
    public function ruleDeleteAction(Request $request)
    {
        $success = false;

        $target = Targeting\Rule::getById($request->get('id'));
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
    public function ruleGetAction(Request $request)
    {
        $target = Targeting\Rule::getById($request->get('id'));

        return $this->adminJson($target);
    }

    /**
     * @Route("/rule/save", name="pimcore_admin_targeting_rulesave", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleSaveAction(Request $request)
    {
        $data = $this->decodeJson($request->get('data'));

        /** @var Targeting\Rule|Targeting\Rule\Dao $target */
        $target = Targeting\Rule::getById($request->get('id'));
        $target->setValues($data['settings']);
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
    public function ruleOrderAction(Request $request)
    {
        $return = [
            'success' => false,
            'message' => '',
        ];

        $rules = $this->decodeJson($request->get('rules'));

        /** @var Targeting\Rule[] $changedRules */
        $changedRules = [];
        foreach ($rules as $id => $prio) {
            /** @var Targeting\Rule $rule */
            $rule = Targeting\Rule::getById((int)$id);
            $prio = (int)$prio;

            if ($rule) {
                if ((int)$rule->getPrio() !== $prio) {
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

    /* TARGET GROUPS */

    /**
     * @Route("/target-group/list", name="pimcore_admin_targeting_targetgrouplist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function targetGroupListAction(Request $request)
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

        /** @var TargetGroup $targetGroup */
        foreach ($list->load() as $targetGroup) {
            $targetGroups[] = [
                'id' => $targetGroup->getId(),
                'text' => $targetGroup->getName(),
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
     * @param CoreHandlerInterface $cache
     *
     * @return JsonResponse
     */
    public function targetGroupAddAction(Request $request, CoreHandlerInterface $cache)
    {
        /** @var TargetGroup|TargetGroup\Dao $targetGroup */
        $targetGroup = new TargetGroup();
        $targetGroup->setName($request->get('name'));
        $targetGroup->save();

        $event = new TargetGroupEvent($targetGroup);
        \Pimcore::getEventDispatcher()->dispatch(TargetGroupEvents::POST_ADD, $event);

        $cache->clearTag('target_groups');

        return $this->adminJson(['success' => true, 'id' => $targetGroup->getId()]);
    }

    /**
     * @Route("/target-group/delete", name="pimcore_admin_targeting_targetgroupdelete", methods={"DELETE"})
     *
     * @param Request $request
     * @param CoreHandlerInterface $cache
     *
     * @return JsonResponse
     */
    public function targetGroupDeleteAction(Request $request, CoreHandlerInterface $cache)
    {
        $success = false;

        /** @var TargetGroup|TargetGroup\Dao $targetGroup */
        $targetGroup = TargetGroup::getById($request->get('id'));
        if ($targetGroup) {
            $event = new TargetGroupEvent($targetGroup);
            $targetGroup->delete();
            $success = true;

            \Pimcore::getEventDispatcher()->dispatch(TargetGroupEvents::POST_DELETE, $event);
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
    public function targetGroupGetAction(Request $request)
    {
        /** @var TargetGroup|TargetGroup\Dao $targetGroup */
        $targetGroup = TargetGroup::getById($request->get('id'));

        return $this->adminJson($targetGroup);
    }

    /**
     * @Route("/target-group/save", name="pimcore_admin_targeting_targetgroupsave", methods={"PUT"})
     *
     * @param Request $request
     * @param CoreHandlerInterface $cache
     *
     * @return JsonResponse
     */
    public function targetGroupSaveAction(Request $request, CoreHandlerInterface $cache)
    {
        $data = $this->decodeJson($request->get('data'));

        /** @var TargetGroup|TargetGroup\Dao $targetGroup */
        $targetGroup = TargetGroup::getById($request->get('id'));
        $targetGroup->setValues($data['settings']);
        $targetGroup->save();

        $event = new TargetGroupEvent($targetGroup);
        \Pimcore::getEventDispatcher()->dispatch(TargetGroupEvents::POST_UPDATE, $event);

        $cache->clearTag('target_groups');

        return $this->adminJson(['success' => true]);
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

        // check permissions
        $this->checkActionPermission($event, 'targeting', ['targetGroupListAction']);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
