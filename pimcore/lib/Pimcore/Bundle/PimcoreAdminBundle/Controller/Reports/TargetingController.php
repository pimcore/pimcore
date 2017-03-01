<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Reports;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Model\Tool\Targeting;
use Pimcore\Model\Document;
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
     * @param Request $request
     * @return JsonResponse
     */
    public function ruleListAction(Request $request)
    {
        $targets = [];
        $list = new Targeting\Rule\Listing();

        foreach ($list->load() as $target) {
            $targets[] = [
                "id" => $target->getId(),
                "text" => $target->getName(),
                "qtip" => $target->getId()
            ];
        }

        return $this->json($targets);
    }

    /**
     * @Route("/rule-add")
     * @param Request $request
     * @return JsonResponse
     */
    public function ruleAddAction(Request $request)
    {
        $target = new Targeting\Rule();
        $target->setName($request->get("name"));
        $target->save();

        return $this->json(["success" => true, "id" => $target->getId()]);
    }

    /**
     * @Route("/rule-delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function ruleDeleteAction(Request $request)
    {
        $success = false;

        $target = Targeting\Rule::getById($request->get("id"));
        if ($target) {
            $target->delete();
            $success = true;
        }

        return $this->json(["success" => $success]);
    }

    /**
     * @Route("/rule-get")
     * @param Request $request
     * @return JsonResponse
     */
    public function ruleGetAction(Request $request)
    {
        $target = Targeting\Rule::getById($request->get("id"));
        $redirectUrl = $target->getActions()->getRedirectUrl();
        if (is_numeric($redirectUrl)) {
            $doc = Document::getById($redirectUrl);
            if ($doc instanceof Document) {
                $target->getActions()->redirectUrl = $doc->getFullPath();
            }
        }

        return $this->json($target);
    }

    /**
     * @Route("/rule-save")
     * @param Request $request
     * @return JsonResponse
     */
    public function ruleSaveAction(Request $request)
    {
        $data = \Zend_Json::decode($request->get("data"));

        $target = Targeting\Rule::getById($request->get("id"));
        $target->setValues($data["settings"]);

        $target->setConditions($data["conditions"]);

        $actions = new Targeting\Rule\Actions();
        $actions->setRedirectEnabled($data["actions"]["redirect.enabled"]);
        $actions->setRedirectUrl($data["actions"]["redirect.url"]);
        $actions->setRedirectCode($data["actions"]["redirect.code"]);
        $actions->setEventEnabled($data["actions"]["event.enabled"]);
        $actions->setEventKey($data["actions"]["event.key"]);
        $actions->setEventValue($data["actions"]["event.value"]);
        $actions->setProgrammaticallyEnabled($data["actions"]["programmatically.enabled"]);
        $actions->setCodesnippetEnabled($data["actions"]["codesnippet.enabled"]);
        $actions->setCodesnippetCode($data["actions"]["codesnippet.code"]);
        $actions->setCodesnippetSelector($data["actions"]["codesnippet.selector"]);
        $actions->setCodesnippetPosition($data["actions"]["codesnippet.position"]);
        $actions->setPersonaId($data["actions"]["persona.id"]);
        $actions->setPersonaEnabled($data["actions"]["persona.enabled"]);
        $target->setActions($actions);

        $target->save();

        return $this->json(["success" => true]);
    }



    /* PERSONAS */

    /**
     * @Route("/persona-list")
     * @param Request $request
     * @return JsonResponse
     */
    public function personaListAction(Request $request)
    {
        $personas = [];
        $list = new Targeting\Persona\Listing();

        foreach ($list->load() as $persona) {
            $personas[] = [
                "id" => $persona->getId(),
                "text" => $persona->getName(),
                "qtip" => $persona->getId()
            ];
        }

        return $this->json($personas);
    }

    /**
     * @Route("/persona-add")
     * @param Request $request
     * @return JsonResponse
     */
    public function personaAddAction(Request $request)
    {
        $persona = new Targeting\Persona();
        $persona->setName($request->get("name"));
        $persona->save();

        return $this->json(["success" => true, "id" => $persona->getId()]);
    }

    /**
     * @Route("/persona-delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function personaDeleteAction(Request $request)
    {
        $success = false;

        $persona = Targeting\Persona::getById($request->get("id"));
        if ($persona) {
            $persona->delete();
            $success = true;
        }

        return $this->json(["success" => $success]);
    }

    /**
     * @Route("/persona-get")
     * @param Request $request
     * @return JsonResponse
     */
    public function personaGetAction(Request $request)
    {
        $persona = Targeting\Persona::getById($request->get("id"));
        return $this->json($persona);
    }

    /**
     * @Route("/persona-save")
     * @param Request $request
     * @return JsonResponse
     */
    public function personaSaveAction(Request $request)
    {
        $data = \Zend_Json::decode($request->get("data"));

        $persona = Targeting\Persona::getById($request->get("id"));
        $persona->setValues($data["settings"]);

        $persona->setConditions($data["conditions"]);
        $persona->save();

        return $this->json(["success" => true]);
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

        $request = $event->getRequest();

        // check permissions
        $notRestrictedActions = ["persona-list"];
        if (!in_array($request->get("action"), $notRestrictedActions)) {
            $this->checkPermission("targeting");
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
