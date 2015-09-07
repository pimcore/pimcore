<?php

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

class OnlineShop_AdminOrderController extends Pimcore\Controller\Action\Admin
{
    /**
     * @var OnlineShop\Framework\IOrderManager
     */
    protected $orderManager;


    public function init()
    {
        parent::init();


        // enable layout only if its a normal request
        if($this->getRequest()->isXmlHttpRequest() === false)
        {
            $this->enableLayout();
            $this->setLayout('back-office');
        }


        // sprache setzen
        $language = $this->getUser() ? $this->getUser()->getLanguage() : 'en';
        Zend_Registry::set("Zend_Locale", new Zend_Locale( $language ));
        $this->language = $language;
        $this->view->language = $language;
        $this->initTranslation();


        // enable inherited values
        Object_Abstract::setGetInheritedValues(true);
        Object_Localizedfield::setGetFallbackValues(true);


        // init
        $this->orderManager = OnlineShop_Framework_Factory::getInstance()->getOrderManager();
    }


    protected function initTranslation() {

        $translate = null;
        if(Zend_Registry::isRegistered("Zend_Translate")) {
            $t = Zend_Registry::get("Zend_Translate");
            // this check is necessary for the case that a document is rendered within an admin request
            // example: send test newsletter
            if($t instanceof Pimcore_Translate) {
                $translate = $t;
            }
        }

        if(!$translate) {
            // setup Zend_Translate
            try {
                $locale = Zend_Registry::get("Zend_Locale");

                $translate = new Pimcore_Translate_Website($locale);

                if(Pimcore_Tool::isValidLanguage($locale)) {
                    $translate->setLocale($locale);
                } else {
                    Logger::error("You want to use an invalid language which is not defined in the system settings: " . $locale);
                    // fall back to the first (default) language defined
                    $languages = Pimcore_Tool::getValidLanguages();
                    if($languages[0]) {
                        Logger::error("Using '" . $languages[0] . "' as a fallback, because the language '".$locale."' is not defined in system settings");
                        $translate = new Pimcore_Translate_Website($languages[0]); // reinit with new locale
                        $translate->setLocale($languages[0]);
                    } else {
                        throw new Exception("You have not defined a language in the system settings (Website -> Frontend-Languages), please add at least one language.");
                    }
                }


                // register the translator in Zend_Registry with the key "Zend_Translate" to use the translate helper for Zend_View
                Zend_Registry::set("Zend_Translate", $translate);
            }
            catch (Exception $e) {
                Logger::error("initialization of Pimcore_Translate failed");
                Logger::error($e);
            }
        }

        return $translate;
    }


    /**
     * Bestellungen auflisten
     */
    public function listAction()
    {
        // create new order list
        $list = $this->orderManager->createOrderList();

        // set list type
        $list->setListType( $this->getParam('type', $list::LIST_TYPE_ORDER) );

        // set order state
        $list->setOrderState( OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED );


        // add select fields
        $list->addSelectField('order.OrderDate');
        $list->addSelectField(['OrderNumber' => 'order.orderNumber']);
        if($list->getListType() == $list::LIST_TYPE_ORDER)
        {
            $list->addSelectField(['TotalPrice' => 'order.totalPrice']);
        }
        else if($list->getListType() == $list::LIST_TYPE_ORDER_ITEM)
        {
            $list->addSelectField(['TotalPrice' => 'orderItem.totalPrice']);
        }
        $list->addSelectField(['Items' => 'count(orderItem.o_id)']);


        // Search
        if($this->getParam('q'))
        {
            $q = htmlentities($this->getParam('q'));
            $search = $this->getParam('search');
            switch($search)
            {
                case 'productType':
                    $filterProductType = new Filter\ProductType();
                    $filterProductType->setTypes( [$q] );
                    $list->addFilter( $filterProductType );
                    break;

                case 'order':
                default:
                    $filterOrder = new Filter\OrderSearch();
                    $filterOrder->setKeyword( $q );
                    $list->addFilter( $filterOrder );
                    break;
            }
        }


        // add Date Filter
        $filterDate = new Filter\OrderDateTime();
        if($this->getParam('from') || $this->getParam('till') )
        {
            $from = $this->getParam('from') ? new Zend_Date($this->getParam('from')) : null;
            $till = $this->getParam('till') ? new Zend_Date($this->getParam('till')) : null;
            if ($till){
                $till->add(1,Zend_Date::DAY);
            }

            if($from)
            {
                $filterDate->setFrom( $from );
            }
            if($till)
            {
                $filterDate->setTill( $till );
            }
        }
        else
        {
            // als default, nehmen wir den ersten des aktuellen monats
            $from = new Zend_Date();
            $from->setDay(1);

        }
        $list->addFilter( $filterDate );


        // set default order
        $list->setOrder( 'order.orderDate desc' );


        // create paging
        $paginator = Zend_Paginator::factory( $list );
        $paginator->setItemCountPerPage( 10 );
        $paginator->setCurrentPageNumber( $this->getParam('page', 1) );

        // view
        $this->view->paginator = $paginator;
    }


    /**
     * details der bestellung anzeigen
     */
    public function detailAction()
    {
        // init
        $order = Object_OnlineShopOrder::getById( $this->getParam('id') );
        /* @var OnlineShop_Framework_AbstractOrder $order */
        $orderAgent = $this->view->orderAgent = $this->orderManager->createOrderAgent( $order );


        /**
         * @param array $address
         *
         * @return string
         */
        $geoPoint = function (array $address) {
            # https://developers.google.com/maps/documentation/geocoding/index?hl=de#JSON
            $url = sprintf('http://maps.googleapis.com/maps/api/geocode/json?address=%1$s&sensor=false'
                , urlencode(
                    $address[0]
                    . ' ' . $address[1]
                    . ' ' . $address[2]
                    . ' ' . $address[3]
                )
            );
            $json = json_decode(file_get_contents( $url ));
            return $json->results[0]->geometry->location;
        };


        // get geo point
        $this->view->geoAddressInvoice = $geoPoint([$order->getCustomerStreet(), $order->getCustomerZip(), $order->getCustomerCity(), $order->getCustomerCountry()]);
        if($order->getDeliveryStreet() && $order->getDeliveryZip())
        {
            $this->view->geoAddressDelivery = $geoPoint([$order->getDeliveryStreet(), $order->getDeliveryZip(), $order->getDeliveryCity(), $order->getDeliveryCountry()]);
        }


        // get customer info
        if($order->getCustomer())
        {
            // init
            $arrCustomerAccount = [];
            $customer = $order->getCustomer();
            

            // register
            $register = new Zend_Date($order->getCreationDate());
            $arrCustomerAccount['created'] = $register->get(Zend_Date::DATE_MEDIUM);


            // mail
            if(method_exists($customer, 'getEMail'))
            {
                $arrCustomerAccount['email'] = $customer->getEMail();
            }


            // order count
            $addOrderCount = function () use($customer, &$arrCustomerAccount) {
                $order = new Object_OnlineShopOrder();
                $field = $order->getClass()->getFieldDefinition('customer');
                if($field instanceof \Pimcore\Model\Object\ClassDefinition\Data\Href)
                {
                    if(count($field->getClasses()) == 1)
                    {
                        $class = 'Pimcore\Model\Object\\' . reset($field->getClasses())['classes'];
                        /* @var \Pimcore\Model\Object\Concrete $class */

                        $orderList = $this->orderManager->createOrderList();
                        $orderList->joinCustomer( $class::classId() );

                        $orderList->getQuery()->where('customer.o_id = ?', $customer->getId());

                        $arrCustomerAccount['orderCount'] = $orderList->count();
                    }
                }
            };
            $addOrderCount();

            $this->view->arrCustomerAccount = $arrCustomerAccount;
        }



        // create timeline
        $arrIcons = [
            'itemChangeAmount' => 'glyphicon glyphicon-pencil'
            , 'itemCancel' => 'glyphicon glyphicon-remove'
            , 'itemComplaint' => 'glyphicon glyphicon-alert'
        ];

        $arrContext = [
            'itemChangeAmount' => 'default'
            , 'itemCancel' => 'danger'
            , 'itemComplaint' => 'warning'
        ];

        $arrTimeline = [];
        $date = new Zend_Date();
        foreach($orderAgent->getFullChangeLog() as $note)
        {
            /* @var \Pimcore\Model\Element\Note $note */

            // get avatar
            $user = Pimcore_Model_User::getById( $note->getUser() );
            /* @var \Pimcore\Model\User $user */
            $avatar = $user ? sprintf('/admin/user/get-image?id=%d', $user->getId()) : null;


            // group events
            $group = $date
                ->setTimestamp( $note->getDate() )
                ->get(Zend_Date::DATE_MEDIUM)
            ;


            // load reference
            $reference = Pimcore\Model\Object\Concrete::getById( $note->getCid() );
            $title = $reference instanceof OnlineShop_Framework_AbstractOrderItem
                ? $reference->getProduct()->getOSName()
                : null
            ;


            // add
            $arrTimeline[ $group ][] = [
                'icon' => $arrIcons[ $note->getTitle() ]
                , 'context' => $arrContext[ $note->getTitle() ] ?: 'default'
                , 'type' => $note->getTitle()
                , 'date' => $date->setTimestamp( $note->getDate() )->get(Zend_Date::DATETIME_MEDIUM)
                , 'avatar' => $avatar
                , 'user' => $user ? $user->getName() : null
                , 'message' => $note->getData()['message']['data']
                , 'title' => $title ?: $note->getTitle()
            ];
        }
        $this->view->timeLine = $arrTimeline;
    }


    /**
     * cancel order item
     */
    public function itemCancelAction()
    {
        // init
        $this->view->orderItem = $orderItem = Object_OnlineShopOrderItem::getById( $this->getParam('id') );
        /* @var \Pimcore\Model\Object\OnlineShopOrderItem $orderItem */
        $order = $orderItem->getOrder();


        if($this->getParam('confirmed') && $orderItem->isCancelAble())
        {
            // init
            $agent = $this->orderManager->createOrderAgent( $order );

            // cancel
            $note = $agent->itemCancel( $orderItem );

            // extend log
            $note->addData('message', 'text', $this->getParam('message'));
            $note->save();


            // redir
            $url = $this->view->url(['action' => 'detail', 'controller' => 'admin-order', 'module' => 'OnlineShop', 'id' => $order->getId()], 'plugin', true);
            $this->redirect( $url );
        }
    }


    /**
     * edit item
     */
    public function itemEditAction()
    {
        // init
        $this->view->orderItem = $orderItem = Object_OnlineShopOrderItem::getById( $this->getParam('id') );
        /* @var \Pimcore\Model\Object\OnlineShopOrderItem $orderItem */
        $order = $orderItem->getOrder();


        if($this->getParam('confirmed'))
        {
            // change item
            $agent = $this->orderManager->createOrderAgent( $order );
            $note = $agent->itemChangeAmount($orderItem, $this->getParam('quantity'));

            // extend log
            $note->addData('message', 'text', $this->getParam('message')); # 'text','date','document','asset','object','bool'
            $note->save();


            // redir
            $url = $this->view->url(['action' => 'detail', 'controller' => 'admin-order', 'module' => 'OnlineShop', 'id' => $order->getId()], 'plugin', true);
            $this->redirect( $url );
        }
    }


    /**
     * complaint item
     */
    public function itemComplaintAction()
    {
        // init
        $this->view->orderItem = $orderItem = Object_OnlineShopOrderItem::getById( $this->getParam('id') );
        /* @var \Pimcore\Model\Object\OnlineShopOrderItem $orderItem */
        $order = $orderItem->getOrder();


        if($this->getParam('confirmed'))
        {
            // change item
            $agent = $this->orderManager->createOrderAgent( $order );
            $note = $agent->itemComplaint($orderItem, $this->getParam('quantity'));

            // extend log
            $note->addData('message', 'text', $this->getParam('message'));
            $note->save();


            // redir
            $url = $this->view->url(['action' => 'detail', 'controller' => 'admin-order', 'module' => 'OnlineShop', 'id' => $order->getId()], 'plugin', true);
            $this->redirect( $url );
        }
    }
}
