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
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Pimcore\WorkflowManagement\Workflow;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;

/**
 * @Route("/workflow")
 */
class WorkflowController extends AdminController implements EventedControllerInterface
{
    /**
     * @var Workflow\Manager $manager;
     */
    private $manager;

    /**
     * @var Workflow\Decorator $decorator;
     */
    private $decorator;

    /**
     * @var Document|Asset|ConcreteObject $element
     */
    private $element;

    /**
     * @var string $selectedAction
     */
    private $selectedAction;

    /**
     * @var string $newState
     */
    private $newState;

    /**
     * @var string $newStatus
     */
    private $newStatus;

    /**
     * Returns a JSON of the available workflow actions to the admin panel
     *
     * @Route("/get-workflow-form")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWorkflowFormAction(Request $request, Manager $workflowManager)
    {
        try {

            $workflow = $workflowManager->getWorkflowIfExists($this->element, (string) $request->get('workflowName'));
            $workflowConfig = $workflowManager->getWorkflowConfig((string) $request->get('workflowName'));

            if(empty($workflow) || empty($workflowConfig)) {
                $wfConfig = [
                    'message' => 'workflow not found'
                ];
            } else {

                //this is the default returned workflow data
                $wfConfig = [
                    'message'               => '',
                    'notes_enabled'         => false,
                    'notes_required'        => false,
                    'additional_fields'     => []
                ];

                $enabledTransitions = $workflow->getEnabledTransitions($this->element);
                /**
                 * @var Transition $transition
                 */
                $transition = null;
                foreach($enabledTransitions as $_transition) {
                    if($_transition->getName() === $request->get('transitionName')) {
                        $transition = $_transition;
                    }
                }

                if(empty($transition)) {
                    $wfConfig['message'] = sprintf("transition %s currently not allowed", (string) $request->get('transitionName'));
                } else {
                    $wfConfig['notes_required'] = $transition->getNotesCommentRequired();
                    $wfConfig['additional_fields'] = [];
                }

            }

        } catch (\Exception $e) {
            $wfConfig['message'] = $e->getMessage();
        }

        return $this->adminJson($wfConfig);
    }

    /**
     * @Route("/submit-workflow-transition")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function submitWorkflowTransitionAction(Request $request, Registry $workflowRegistry, Manager $workflowManager)
    {
        $workflowOptions = $request->get('workflow', []);
        $workflow = $workflowRegistry->get($this->element, $request->get('workflowName'));


        if ($workflow->can($this->element, $request->get('transition'))) {

            try {
                $workflowManager->applyWithAdditionalData($workflow, $this->element, $request->get('transition'), $workflowOptions);

                $data = [
                    'success' => true,
                    'callback' => 'reloadObject'
                ];
            } catch (ValidationException $e) {

                $reason = '';
                if(sizeof((array)$e->getSubItems())>0) {
                    $reason = '<ul>' . implode('', array_map(function($item){
                            return '<li>' . $item . '</li>';
                        },$e->getSubItems())) . '</ul>';
                }

                $data = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'reason' => $reason

                ];
            } catch (\Exception $e) {
                $data = [
                    'success' => false,
                    'message' => 'error performing action on this element',
                    'reason' => $e->getMessage()
                ];
            }
        } else {
            $data = [
                'success' => false,
                'message' => 'error validating the action on this element, element cannot peform this action',
                'reason' => 'transition is currently not allowed'
            ];
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/submit-global-action")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function submitGlobalAction(Request $request, Registry $workflowRegistry, Manager $workflowManager)
    {
        $workflowOptions = $request->get('workflow', []);
        $workflow = $workflowRegistry->get($this->element, $request->get('workflowName'));

        try {
            $workflowManager->applyGlobalAction($workflow, $this->element, $request->get('transition'), $workflowOptions);

            $data = [
                'success' => true,
                'callback' => 'reloadObject'
            ];
        } catch (ValidationException $e) {

            $reason = '';
            if(sizeof((array)$e->getSubItems())>0) {
                $reason = '<ul>' . implode('', array_map(function($item){
                        return '<li>' . $item . '</li>';
                    },$e->getSubItems())) . '</ul>';
            }

            $data = [
                'success' => false,
                'message' => $e->getMessage(),
                'reason' => $reason

            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => 'error performing action on this element',
                'reason' => $e->getMessage()
            ];
        }


        return $this->adminJson($data);
    }


    /**
     * @param  Document|Asset|ConcreteObject $element
     *
     * @return Document|Asset|ConcreteObject
     */
    protected function getLatestVersion($element)
    {

        //TODO move this maybe to a service method, since this is also used in DataObjectController and DocumentControllers
        if ($element instanceof Document) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();
                if ($latestDoc instanceof Document) {
                    $element = $latestDoc;
                    $element->setModificationDate($element->getModificationDate());
                }
            }
        }

        if ($element instanceof DataObject\Concrete) {
            $modificationDate = $element->getModificationDate();
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestObj = $latestVersion->loadData();
                if ($latestObj instanceof ConcreteObject) {
                    $element = $latestObj;
                    $element->setModificationDate($modificationDate);
                }
            }
        }

        return $element;
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @throws \Exception
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $request = $event->getRequest();

        if ($request->get('ctype') === 'document') {
            $this->element = Document::getById((int) $request->get('cid', 0));
        } elseif ($request->get('ctype') === 'asset') {
            $this->element = Asset::getById((int) $request->get('cid', 0));
        } elseif ($request->get('ctype') === 'object') {
            $this->element = ConcreteObject::getById((int) $request->get('cid', 0));
        }

        if (!$this->element) {
            throw new \Exception('Cannot load element' . $request->get('cid') . ' of type \'' . $request->get('ctype') . '\'');
        }

        //get the latest available version of the element -
        $this->element = $this->getLatestVersion($this->element);
        $this->element->setUserModification($this->getAdminUser()->getId());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
