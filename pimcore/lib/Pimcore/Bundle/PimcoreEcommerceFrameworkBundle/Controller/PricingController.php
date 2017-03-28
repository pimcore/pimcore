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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IRule;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Controller\EventedControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigController
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
        $key = 'plugin_onlineshop_pricing_rules';
        $access = $this->getUser()->getPermission($key);
        if (!$access) {
            throw new \Exception('this function requires "plugin_onlineshop_pricing_rules" permission!');
        }
    }


    /**
     * @Route("/list")
     * definierte preisregeln ausgeben
     */
    public function listAction()
    {
        $rules = new Rule\Listing();
        $rules->setOrderKey('prio');
        $rules->setOrder('ASC');

        $json = [];
        foreach ($rules->load() as $rule) {
            /* @var  IRule $rule */

            if ($rule->getActive()) {
                $icon = 'plugin_onlineshop_pricing_icon_rule_' . $rule->getBehavior();
                $title = 'Verhalten: ' . $rule->getBehavior();
            } else {
                $icon = 'plugin_onlineshop_pricing_icon_rule_disabled';
                $title = 'Deaktiviert';
            }

            $json[] = [
                'iconCls' => $icon,
                'id' => $rule->getId(),
                'text' => $rule->getName(),
                'qtipCfg' => [
                    'xtype' => 'quicktip',
                    'title' => $rule->getLabel(),
                    'text' => $title
                ]
            ];
        }

        return $this->json($json);
    }


    /**
     * @Route("/get")
     * @param Request $request
     * preisregel details als json ausgeben
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
                $localizedLabel[ $lang ] = $rule->getLabel($lang);
                $localizedDescription[ $lang ] = $rule->getDescription($lang);
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
                'actions' => []
            ];

            foreach ($rule->getActions() as $action) {
                $json['actions'][] = json_decode($action->toJSON());
            }

            return $this->json($json);
        }
    }


    /**
     * @Route("/add")
     * @param Request $request
     * add new rule
     */
    public function addAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => ''
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
        return $this->json($return);
    }


    /**
     * @Route("/delete")
     * @param Request $request
     * delete exiting rule
     */
    public function deleteAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => ''
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
        return $this->json($return);
    }


    /**
     * @Route("/save")
     * @param Request $request
     * save rule config
     */
    public function saveAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => ''
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
        return $this->json($return);
    }


    /**
     * @Route("/save-order")
     * @param Request $request
     */
    public function saveOrderAction(Request $request)
    {
        // send json respone
        $return = [
            'success' => false,
            'message' => ''
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
        return $this->json($return);
    }


    /**
     * @Route("/get-config")
     * @param Request $request
     */
    public function getConfigAction(Request $request)
    {
        // init
        $json = [
            'condition' => [],
            'action' => []
        ];

        // get config
        $pricingConfig = Factory::getInstance()->getConfig()->get('onlineshop')->get('pricingmanager');
        if ($pricingConfig) {
            $list = $pricingConfig->get('config')->get('condition');
            foreach ($list as $name => $config) {
                $json['condition'][] = $name;
            }

            $list = $pricingConfig->get('config')->get('action');
            foreach ($list as $name => $config) {
                $json['action'][] = $name;
            }
        }

        // print
        return $this->json($json);
    }


    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
