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

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Tool\Targeting;
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
     * @Route("/rule-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ruleListAction(Request $request)
    {
        $targets = [];
        $list = new Targeting\Rule\Listing();

        foreach ($list->load() as $target) {
            $targets[] = [
                'id' => $target->getId(),
                'text' => $target->getName(),
                'qtip' => $target->getId()
            ];
        }

        return $this->adminJson($targets);
    }

    /**
     * @Route("/rule-add")
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
     * @Route("/rule-delete")
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
     * @Route("/rule-get")
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
     * @Route("/rule-save")
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

    /* PERSONAS */

    /**
     * @Route("/persona-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function personaListAction(Request $request)
    {
        $personas = [];
        $list = new Targeting\Persona\Listing();

        if ($request->get('add-default')) {
            $personas[] = [
                'id' => 0,
                'text' => 'default',
                'qtip' => 0
            ];
        }

        foreach ($list->load() as $persona) {
            $personas[] = [
                'id' => $persona->getId(),
                'text' => $persona->getName(),
                'qtip' => $persona->getId()
            ];
        }

        return $this->adminJson($personas);
    }

    /**
     * @Route("/persona-add")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function personaAddAction(Request $request)
    {
        $persona = new Targeting\Persona();
        $persona->setName($request->get('name'));
        $persona->save();

        return $this->adminJson(['success' => true, 'id' => $persona->getId()]);
    }

    /**
     * @Route("/persona-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function personaDeleteAction(Request $request)
    {
        $success = false;

        $persona = Targeting\Persona::getById($request->get('id'));
        if ($persona) {
            $persona->delete();
            $success = true;
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/persona-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function personaGetAction(Request $request)
    {
        $persona = Targeting\Persona::getById($request->get('id'));

        return $this->adminJson($persona);
    }

    /**
     * @Route("/persona-save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function personaSaveAction(Request $request)
    {
        $data = $this->decodeJson($request->get('data'));

        $persona = Targeting\Persona::getById($request->get('id'));
        $persona->setValues($data['settings']);

        $persona->setConditions($data['conditions']);
        $persona->save();

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
        $this->checkActionPermission($event, 'targeting', ['personaListAction']);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
