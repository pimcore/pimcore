<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_PricingController extends Pimcore\Controller\Action\Admin
{
    /**
     * init pricing config system
     */
    public function init()
    {
        parent::init();

        // permission check
        $key = 'plugin_onlineshop_pricing_rules';
        $access = $this->getUser()->getPermission( $key );
        if(!$access)
        {
            throw new Exception('this function requires "plugin_onlineshop_pricing_rules" permission!');
        }
    }


    /**
     * definierte preisregeln ausgeben
     */
    public function listAction()
    {
        $rules = new OnlineShop_Framework_Impl_Pricing_Rule_List();
        $rules->setOrderKey('prio');
        $rules->setOrder('ASC');

        $json = array();
        foreach($rules->load() as $rule)
        {
            /* @var  OnlineShop_Framework_Pricing_IRule $rule */

            if($rule->getActive())
            {
                $icon = 'plugin_onlineshop_pricing_icon_rule_' . $rule->getBehavior();
                $title = 'Verhalten: ' . $rule->getBehavior();
            }
            else
            {
                $icon = 'plugin_onlineshop_pricing_icon_rule_disabled';
                $title = 'Deaktiviert';
            }

            $json[] = array(
                'iconCls' => $icon,
                'id' => $rule->getId(),
                'text' => $rule->getName(),
                'qtipCfg' => array(
                    'xtype' => 'quicktip',
                    'title' => $rule->getLabel(),
                    'text' => $title
                )
            );
        }

        $this->_helper->json($json);
    }


    /**
     * preisregel details als json ausgeben
     */
    public function getAction()
    {
        $rule = OnlineShop_Framework_Impl_Pricing_Rule::getById( (int)$this->getParam('id') );
        if($rule)
        {
            // get data
            $condition = $rule->getCondition();
            foreach(\Pimcore\Tool::getValidLanguages() as $lang)
            {
                $localizedLabel[ $lang ] = $rule->getLabel( $lang );
                $localizedDescription[ $lang ] = $rule->getDescription( $lang );
            }

            // create json config
            $json = array(
                'id' => $rule->getId(),
                'name' => $rule->getName(),
                'label' => $localizedLabel,
                'description' => $localizedDescription,
                'behavior' => $rule->getBehavior(),
                'active' => $rule->getActive(),
                'condition' => $condition ? json_decode($condition->toJSON()) : '',
                'actions' => array()
            );

            foreach($rule->getActions() as $action)
            {
                $json['actions'][] = json_decode($action->toJSON());
            }

            $this->_helper->json( $json );
        }
    }


    /**
     * add new rule
     */
    public function addAction()
    {
        // send json respone
        $return = array(
            'success' => false,
            'message' => ''
        );

        // save rule
        try
        {
            $rule = new OnlineShop_Framework_Impl_Pricing_Rule();
            $rule->setName( $this->getParam('name') );
            $rule->save();

            $return['success'] = true;
            $return['id'] = $rule->getId();
        }
        catch(Exception $e)
        {
            $return['message'] = $e->getMessage();
        }

        // send respone
        $this->_helper->json($return);
    }


    /**
     * delete exiting rule
     */
    public function deleteAction()
    {
        // send json respone
        $return = array(
            'success' => false,
            'message' => ''
        );

        // delete rule
        try
        {
            $rule = OnlineShop_Framework_Impl_Pricing_Rule::getById( (int)$this->getParam('id') );
            $rule->delete();
            $return['success'] = true;
        }
        catch(Exception $e)
        {
            $return['message'] = $e->getMessage();
        }

        // send respone
        $this->_helper->json($return);
    }


    /**
     * save rule config
     */
    public function saveAction()
    {
        // send json respone
        $return = array(
            'success' => false,
            'message' => ''
        );

        // save rule config
        try
        {
            $data = json_decode($this->getParam('data'));
            $rule = OnlineShop_Framework_Impl_Pricing_Rule::getById( (int)$this->getParam('id') );

            // apply basic settings
            $rule->setBehavior( $data->settings->behavior )
                ->setActive( (bool)$data->settings->active );

            // apply lang fields
            foreach(\Pimcore\Tool::getValidLanguages() as $lang)
            {
                $rule->setLabel( $data->settings->{'label.' . $lang}, $lang );
                $rule->setDescription( $data->settings->{'description.' . $lang}, $lang );
            }


            // create root condition
            $rootContainer = new stdClass();
            $rootContainer->parent = null;
            $rootContainer->operator = null;
            $rootContainer->type = 'Bracket';
            $rootContainer->conditions = array();

            // create a tree from the flat structure
            $currentContainer = $rootContainer;
            foreach($data->conditions as $settings)
            {
                // handle brackets
                if($settings->bracketLeft == true)
                {
                    $newContainer = new stdClass();
                    $newContainer->parent = $currentContainer;
                    $newContainer->type = 'Bracket';
                    $newContainer->conditions = array();

                    // move condition from current item to bracket item
                    $newContainer->operator = $settings->operator;
                    $settings->operator = null;

                    $currentContainer->conditions[] = $newContainer;
                    $currentContainer = $newContainer;
                }

                $currentContainer->conditions[] = $settings;

                if( $settings->bracketRight == true )
                {
                    $old = $currentContainer;
                    $currentContainer = $currentContainer->parent;
                    unset($old->parent);
                }
            }

            // create rule condition
            $condition = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getCondition( $rootContainer->type );
            $condition->fromJSON( json_encode($rootContainer) );
            $rule->setCondition( $condition );


            // save action
            $arrActions = array();
            foreach($data->actions as $setting)
            {
                $action = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getAction( $setting->type );
                $action->fromJSON( json_encode($setting) );
                $arrActions[] = $action;
            }
            $rule->setActions($arrActions);

            // save rule
            $rule->save();

            // finish
            $return['success'] = true;
            $return['id'] = $rule->getId();
        }
        catch(Exception $e)
        {
            $return['message'] = $e->getMessage();
        }

        // send respone
        $this->_helper->json($return);
    }


    public function saveOrderAction()
    {
        // send json respone
        $return = array(
            'success' => false,
            'message' => ''
        );

        // save order
        $rules = json_decode($this->getParam('rules'));
        foreach($rules as $id => $prio)
        {
            $rule = OnlineShop_Framework_Impl_Pricing_Rule::getById( (int)$id );
            if($rule)
                $rule->setPrio( (int)$prio )->save();
        }
        $return['success'] = true;

        // send respone
        $this->_helper->json($return);
    }


    /**
     *
     */
    public function getConfigAction()
    {
        // init
        $json = array(
            'condition' => array(),
            'action' => array()
        );

        // get config
        $pricingConfig = OnlineShop_Framework_Factory::getInstance()->getConfig()->get('onlineshop')->get('pricingmanager');
        if($pricingConfig)
        {
            $list = $pricingConfig->get('config')->get( 'condition' );
            foreach($list as $name => $config)
            {
                $json['condition'][] = $name;
            }

            $list = $pricingConfig->get('config')->get( 'action' );
            foreach($list as $name => $config)
            {
                $json['action'][] = $name;
            }
        }

        // print
        $this->_helper->json($json);
    }



    public function testAction()
    {
//        $dateRange = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getCondition('DateRange');
//        $action = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getAction('Gift');
//        var_dump($dateRange,$action);exit;


        // test normal
//        $cart = OnlineShop_Framework_Factory::getInstance()->getCartManager()->createCart(array('name' => 'pricingTest'));
//        $cart = OnlineShop_Framework_Factory::getInstance()->getCartManager()->getCart(2);
//
//
//        $pricingManager = OnlineShop_Framework_Factory::getInstance()->getPricingManager();
//        $pricingManager->applyCartRules( $cart );


        $env = new OnlineShop_Framework_Impl_Pricing_Environment;
//
//        // test daterange
//        $dateRange = new OnlineShop_Framework_Impl_Pricing_Condition_DateRange();
//        $dateRange->setStarting(new Zend_Date('2013-02-03'));
//        $dateRange->setEnding(new Zend_Date('2013-07-04'));
//        var_dump($dateRange->check($env)); exit;
//
//
//        // test action
//        $giftAction = new OnlineShop_Framework_Impl_Pricing_Action_Gift();
//        $giftAction->setProduct( OnlineShop_Framework_AbstractProduct::getById(18149) );
//
//        // test rule
//        $priceRule = new OnlineShop_Framework_Impl_Pricing_Rule();
//        $priceRule->addCondition($dateRange);
//        $priceRule->setAction($giftAction);

//        var_dump($priceRule->check($env)); exit;

        // test conditionlist OR
        $dateRange = new OnlineShop_Framework_Impl_Pricing_Condition_DateRange();   // true
        $dateRange->setStarting(new \Zend_Date('2013-02-03'));
        $dateRange->setEnding(new \Zend_Date('2013-20-04'));
        $dateRange2 = new OnlineShop_Framework_Impl_Pricing_Condition_DateRange();  // false
        $dateRange2->setStarting(new \Zend_Date('2012-02-03'));
        $dateRange2->setEnding(new \Zend_Date('2012-30-04'));

        $bracket = new OnlineShop_Framework_Impl_Pricing_Condition_Bracket();
        $bracket->addCondition($dateRange, null);
        $bracket->addCondition($dateRange2, OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_AND_NOT); // true



        // bracket test
        $dateRange3 = new OnlineShop_Framework_Impl_Pricing_Condition_DateRange();  // false
        $dateRange3->setStarting(new \Zend_Date('2012-02-03'));
        $dateRange3->setEnding(new \Zend_Date('2012-30-04'));

        $bracket2 = new OnlineShop_Framework_Impl_Pricing_Condition_Bracket();
        $bracket2->addCondition($bracket, null);
        $bracket2->addCondition($dateRange3, OnlineShop_Framework_Pricing_Condition_IBracket::OPERATOR_AND_NOT);

        # var_dump($bracket2->check($env) );die();


        echo $bracket2->toJSON();
        exit;
    }


    /**
     * cart rule test
     */
    public function testCartAction()
    {
        $cart = OnlineShop_Framework_Factory::getInstance()->getCartManager()->createCart(array('name' => 'pricingTest'));


        $cart = OnlineShop_Framework_Factory::getInstance()->getCartManager()->getCart(2);

        $pricingManager = OnlineShop_Framework_Factory::getInstance()->getPricingManager();
        $pricingManager->applyCartRules( $cart );

        exit;
    }
}
