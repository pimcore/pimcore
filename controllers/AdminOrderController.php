<?php

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

class OnlineShop_AdminOrderController extends Pimcore\Controller\Action\Admin
{
    public function init()
    {
        parent::init();


        // enable layout only if its a normal request
        if($this->getRequest()->isXmlHttpRequest() === false)
        {
            $this->enableLayout();
            $this->setLayout('back-office');
        }


        // enable inherited values
        Object_Abstract::setGetInheritedValues(true);
        Object_Localizedfield::setGetFallbackValues(true);
    }


    /**
     * Bestellungen auflisten
     */
    public function listAction()
    {
        // load order manager
        $orderManager = OnlineShop_Framework_Factory::getInstance()->getOrderManager();

        // create new order list
        $list = $orderManager->createOrderList();

        // set list type
        $list->setListType( $this->getParam('type', $list::LIST_TYPE_ORDER) );


        // add select fields
        $list->addSelectField('order.OrderDate');
        $list->addSelectField(['OrderNumber' => 'order.orderNumber']);
        $list->addSelectField(['PaymentReference' => 'order.paymentReference']);
        if($list->getListType() == $list::LIST_TYPE_ORDER)
        {
            $list->addSelectField(['TotalPrice' => 'order.totalPrice']);
        }
        else if($list->getListType() == $list::LIST_TYPE_ORDER_ITEM)
        {
            $list->addSelectField(['TotalPrice' => 'orderItem.totalPrice']);
        }


        // Search
        if($this->getParam('q'))
        {
            $q = htmlentities($this->getParam('q'));
            $search = $this->getParam('search');
            switch($search)
            {
                case 'paymentReference':
//                    $list->setFilterPaymentReference( $this->getParam('q') );
//                    break;

                case 'email':
                case 'customer':
                default:
                    $filterCustomer = new Filter\Customer();

                    if($search == 'customer')
                    {
                        $filterCustomer->setName( $q );
                    }
                    if($search == 'email')
                    {
                        $filterCustomer->setEmail( $q );
                    }

                    $list->addFilter( $filterCustomer );
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
            // als default, nehmen wir dem ersten des aktuellen monats
            $from = new Zend_Date();
            $from->setDay(1);

//            $filterDate->setFrom( $from );
//            $this->setParam('from', $from->get(Zend_Date::DATE_MEDIUM));
        }
        $list->addFilter( $filterDate );



        // create paging
        $paginator = Zend_Paginator::factory( $list );
        $paginator->setItemCountPerPage( 10 );
        $paginator->setCurrentPageNumber( $this->getParam('page', 1) );

        // view
        $this->view->paginator = $paginator;
    }


    /**
     * details der bestellung anzeigen
     * @todo
     */
    public function detailAction()
    {
//        // init
//        $order = Object_OnlineShopOrder::getById( $this->getParam('id') );
//        $this->view->order = $order;
//
//
//        if ($this->getRequest()->isPost())
//        {
//            $order->setPaymentState( $this->getParam('order-paymentState') );
//            $order->save();
//        }
    }
}
