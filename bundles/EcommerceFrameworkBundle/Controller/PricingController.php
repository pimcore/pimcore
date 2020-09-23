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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Tool\RestClient\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigController
 *
 * @Route("/pricing")
 */
class PricingController extends AdminController implements EventedControllerInterface
{
    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // permission check
        $access = $this->getAdminUser()->isAllowed('bundle_ecommerce_pricing_rules');
        if (!$access) {
            throw new \Exception('this function requires "bundle_ecommerce_pricing_rules" permission!');
        }
    }

    /**
     * @Route("/list", name="pimcore_ecommerceframework_pricing_list", methods={"GET"})
     */
    public function listAction()
    {
        $rules = new Rule\Listing();
        $rules->setOrderKey('prio');
        $rules->setOrder('ASC');

        $json = [];
        foreach ($rules->load() as $rule) {
            /* @var  RuleInterface $rule */

            if ($rule->getActive()) {
                $icon = 'bundle_ecommerce_pricing_icon_rule_' . $rule->getBehavior();
                $title = 'Verhalten: ' . $rule->getBehavior();
            } else {
                $icon = 'bundle_ecommerce_pricing_icon_rule_disabled';
                $title = 'Deaktiviert';
            }

            $json[] = [
                'iconCls' => $icon,
                'id' => $rule->getId(),
                'text' => $rule->getName(),
                'qtipCfg' => [
                    'xtype' => 'quicktip',
                    'title' => $rule->getLabel(),
                    'text' => $title,
                ],
            ];
        }

        return $this->adminJson($json);
    }

    /**
     * get priceing rule details as json
     *
     * @Route("/get", name="pimcore_ecommerceframework_pricing_get", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws NotFoundHttpException
     */
    public function getAction(Request $request)
    {
        $rule = Rule::getById((int) $request->get('id'));
        if ($rule) {
            // get data
            $condition = $rule->getCondition();
            $localizedLabel = [];
            $localizedDescription = [];

            foreach (\Pimcore\Tool::getValidLanguages() as $lang) {
                $localizedLabel[$lang] = $rule->getLabel($lang);
                $localizedDescription[$lang] = $rule->getDescription($lang);
            }

            // create json config
            $json = [
                'id' => $rule->getId(),
                'name' => $rule->getName(),
                'label' => $localizedLabel,
                'description' => $localizedDescription,
                'behavior' => $rule->getBehavior(),
                'active' => $rule->getActive(),
                'condition' => $condition ? json_decode($condition->toJSON()) : '',
                'actions' => [],
            ];

            foreach ($rule->getActions() as $action) {
                $json['actions'][] = json_decode($action->toJSON());
            }

            return $this->adminJson($json);
        }

        throw $this->createNotFoundException('Rule not found');
    }

    /**
     * add new rule
     *
     * @Route("/add", name="pimcore_ecommerceframework_pricing_add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => '',
        ];

        // save rule
        try {
            $rule = new Rule();
            $rule->setName($request->get('name'));
            $rule->save();

            $return['success'] = true;
            $return['id'] = $rule->getId();
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send respone
        return $this->adminJson($return);
    }

    /**
     * delete exiting rule
     *
     * @Route("/delete", name="pimcore_ecommerceframework_pricing_delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => '',
        ];

        // delete rule
        try {
            $rule = Rule::getById((int) $request->get('id'));
            $rule->delete();
            $return['success'] = true;
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send respone
        return $this->adminJson($return);
    }

    /**
     * @Route("/copy", name="pimcore_ecommerceframework_pricing_copy", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     * copy existing rule
     */
    public function copyAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => '',
        ];

        // copy rule
        try {
            /** @var Rule $ruleSource */
            $ruleSource = Rule::getById((int) $request->get('id'));
            $rules = (new Rule\Listing())->load();

            $name = $ruleSource->getName() . '_copy';

            // Get new unique name.
            do {
                $uniqueName = true;

                foreach ($rules as $rule) {
                    if ($rule->getName() == $name) {
                        $uniqueName = false;
                        $name .= '_copy';

                        break;
                    }
                }
            } while (!$uniqueName);

            // Clone and save new rule.
            $newRule = clone $ruleSource;
            $newRule->setId(null);
            $newRule->setName($name);
            $newRule->save();

            $return['success'] = true;
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send respone
        return $this->adminJson($return);
    }

    /**
     * @Route("/rename", name="pimcore_ecommerceframework_pricing_rename", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     * rename exiting rule
     */
    public function renameAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => '',
        ];

        $ruleId = $request->get('id');
        $ruleNewName = $request->get('name');

        try {
            if ($ruleId && $ruleNewName) {
                $renameRule = Rule::getById($ruleId);

                if ($renameRule->getName() != $ruleNewName) {
                    $rules = (new Rule\Listing())->load();

                    // Check if rulename is available.
                    foreach ($rules as $rule) {
                        if ($rule->getName() == $ruleNewName) {
                            throw new Exception('Rulename already exists.');
                        }
                    }

                    $renameRule->setName($ruleNewName);
                    $renameRule->save();
                }

                $return['success'] = true;
            }
        } catch (Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send respone
        return $this->adminJson($return);
    }

    /**
     * save rule config
     *
     * @Route("/save", name="pimcore_ecommerceframework_pricing_save", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => '',
        ];

        // save rule config
        try {
            $data = json_decode($request->get('data'));
            $rule = Rule::getById((int) $request->get('id'));

            // apply basic settings
            $rule->setBehavior($data->settings->behavior)
                ->setActive((bool)$data->settings->active);

            // apply lang fields
            foreach (\Pimcore\Tool::getValidLanguages() as $lang) {
                $rule->setLabel($data->settings->{'label.' . $lang}, $lang);
                $rule->setDescription($data->settings->{'description.' . $lang}, $lang);
            }

            // create root condition
            $rootContainer = new \stdClass();
            $rootContainer->parent = null;
            $rootContainer->operator = null;
            $rootContainer->type = 'Bracket';
            $rootContainer->conditions = [];

            // create a tree from the flat structure
            $currentContainer = $rootContainer;
            foreach ($data->conditions as $settings) {
                // handle brackets
                if ($settings->bracketLeft == true) {
                    $newContainer = new \stdClass();
                    $newContainer->parent = $currentContainer;
                    $newContainer->type = 'Bracket';
                    $newContainer->conditions = [];

                    // move condition from current item to bracket item
                    $newContainer->operator = $settings->operator;
                    $settings->operator = null;

                    $currentContainer->conditions[] = $newContainer;
                    $currentContainer = $newContainer;
                }

                $currentContainer->conditions[] = $settings;

                if ($settings->bracketRight == true) {
                    $old = $currentContainer;
                    $currentContainer = $currentContainer->parent;
                    unset($old->parent);
                }
            }

            // create rule condition
            $condition = Factory::getInstance()->getPricingManager()->getCondition($rootContainer->type);
            $condition->fromJSON(json_encode($rootContainer));
            $rule->setCondition($condition);

            // save action
            $arrActions = [];
            foreach ($data->actions as $setting) {
                $action = Factory::getInstance()->getPricingManager()->getAction($setting->type);
                $action->fromJSON(json_encode($setting));
                $arrActions[] = $action;
            }
            $rule->setActions($arrActions);

            // save rule
            $rule->save();

            // finish
            $return['success'] = true;
            $return['id'] = $rule->getId();
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send respone
        return $this->adminJson($return);
    }

    /**
     * @Route("/save-order", name="pimcore_ecommerceframework_pricing_save-order", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveOrderAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => '',
        ];

        // save order
        $rules = json_decode($request->get('rules'));
        foreach ($rules as $id => $prio) {
            $rule = Rule::getById((int)$id);
            if ($rule) {
                $rule->setPrio((int)$prio)->save();
            }
        }
        $return['success'] = true;

        // send respone
        return $this->adminJson($return);
    }

    /**
     * @Route("/get-config", name="pimcore_ecommerceframework_pricing_get-config", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getConfigAction()
    {
        $pricingManager = Factory::getInstance()->getPricingManager();

        return $this->adminJson([
            'condition' => array_keys($pricingManager->getConditionMapping()),
            'action' => array_keys($pricingManager->getActionMapping()),
        ]);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
