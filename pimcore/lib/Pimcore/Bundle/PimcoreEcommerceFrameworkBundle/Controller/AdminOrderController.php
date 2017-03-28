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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderManager;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\OrderDateTime;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\OrderSearch;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\ProductType;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\Localizedfield;
use Pimcore\Model\Object\OnlineShopOrder;
use Pimcore\Model\Object\OnlineShopOrderItem;
use Pimcore\Model\User;
use Pimcore\Service\IntlFormatterService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Paginator\Paginator;

/**
 * Class AdminOrderController
 * @Route("/admin-order")
 */
class AdminOrderController extends FrontendController
{
    /**
     * @var IOrderManager
     */
    protected $orderManager;


    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // set language
        $user = $this->get('pimcore_admin.security.token_storage_user_resolver')->getUser();
        if ($user) {
            $this->get("translator")->setLocale($user->getLanguage());
            $event->getRequest()->setLocale($user->getLanguage());
        }

        // enable inherited values
        AbstractObject::setGetInheritedValues(true);
        Localizedfield::setGetFallbackValues(true);

        $this->orderManager = Factory::getInstance()->getOrderManager();

        // enable view auto-rendering
        $this->setViewAutoRender($event->getRequest(), true, 'php');
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }

    /**
     * Bestellungen auflisten
     * @Route("/list", name="pimcore_ecommerce_backend_admin-order_list")
     */
    public function listAction(Request $request)
    {
        // create new order list
        $list = $this->orderManager->createOrderList();

        // set list type
        $list->setListType($request->get('type', $list::LIST_TYPE_ORDER));

        // set order state
        $list->setOrderState(AbstractOrder::ORDER_STATE_COMMITTED);


        // add select fields
        $list->addSelectField('order.OrderDate');
        $list->addSelectField(['OrderNumber' => 'order.orderNumber']);
        if ($list->getListType() == $list::LIST_TYPE_ORDER) {
            $list->addSelectField(['TotalPrice' => 'order.totalPrice']);
        } elseif ($list->getListType() == $list::LIST_TYPE_ORDER_ITEM) {
            $list->addSelectField(['TotalPrice' => 'orderItem.totalPrice']);
        }
        $list->addSelectField(['Items' => 'count(orderItem.o_id)']);


        // Search
        if ($request->get('q')) {
            $q = htmlentities($request->get('q'));
            $search = $request->get('search');
            switch ($search) {
                case 'productType':
                    $filterProductType = new ProductType();
                    $filterProductType->setTypes([$q]);
                    $list->addFilter($filterProductType);
                    break;

                case 'order':
                default:
                    $filterOrder = new OrderSearch();
                    $filterOrder->setKeyword($q);
                    $list->addFilter($filterOrder);
                    break;
            }
        }


        // add Date Filter
        if ($request->query->has('from') === false && $request->query->has('till') === false) {
            // als default, nehmen wir den ersten des aktuellen monats
            $from = new \DateTime('first day of this month');
            $request->query->set('from', $from->format('d.m.Y'));
        }

        $filterDate = new OrderDateTime();
        if ($request->get('from') || $request->get('till')) {
            $from = $request->get('from') ? new \DateTime($request->get('from')) : null;
            $till = $request->get('till') ? new \DateTime($request->get('till')) : null;
            if ($till) {
                $till->add(new \DateInterval('P1D'));
            }

            if ($from) {
                $filterDate->setFrom($from);
            }
            if ($till) {
                $filterDate->setTill($till);
            }
        }
        $list->addFilter($filterDate);


        // set default order
        $list->setOrder('order.orderDate desc');


        // create paging
        $paginator = new Paginator($list);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($request->get('page', 1));

        // view
        $this->view->paginator = $paginator;
    }


    /**
     * details der bestellung anzeigen
     * @Route("/detail", name="pimcore_ecommerce_backend_admin-order_detail")
     */
    public function detailAction(Request $request)
    {
        $dateFormatter = $this->get('pimcore.locale.intl_formatter');

        // init
        $order = OnlineShopOrder::getById($request->get('id'));
        /* @var AbstractOrder $order */
        $orderAgent = $this->view->orderAgent = $this->orderManager->createOrderAgent($order);


        /**
         * @param array $address
         *
         * @return string
         */
        $geoPoint = function (array $address) {
            // https://developers.google.com/maps/documentation/geocoding/index?hl=de#JSON
            $url = sprintf('http://maps.googleapis.com/maps/api/geocode/json?address=%1$s&sensor=false', urlencode(
                    $address[0]
                    . ' ' . $address[1]
                    . ' ' . $address[2]
                    . ' ' . $address[3]
                )
            );
            $json = json_decode(file_get_contents($url));

            return $json->results[0]->geometry->location;
        };


        // get geo point
        $this->view->geoAddressInvoice = $geoPoint([$order->getCustomerStreet(), $order->getCustomerZip(), $order->getCustomerCity(), $order->getCustomerCountry()]);
        if ($order->getDeliveryStreet() && $order->getDeliveryZip()) {
            $this->view->geoAddressDelivery = $geoPoint([$order->getDeliveryStreet(), $order->getDeliveryZip(), $order->getDeliveryCity(), $order->getDeliveryCountry()]);
        }


        // get customer info
        if ($order->getCustomer()) {
            // init
            $arrCustomerAccount = [];
            $customer = $order->getCustomer();


            // register
            $register = new \DateTime($order->getCreationDate());
            $arrCustomerAccount['created'] = $dateFormatter->formatDateTime($register, IntlFormatterService::DATE_MEDIUM);


            // mail
            if (method_exists($customer, 'getEMail')) {
                $arrCustomerAccount['email'] = $customer->getEMail();
            }


            // order count
            $addOrderCount = function () use ($customer, &$arrCustomerAccount) {
                $order = new OnlineShopOrder();
                $field = $order->getClass()->getFieldDefinition('customer');
                if ($field instanceof \Pimcore\Model\Object\ClassDefinition\Data\Href) {
                    if (count($field->getClasses()) == 1) {
                        $class = 'Pimcore\Model\Object\\' . reset($field->getClasses())['classes'];
                        /* @var \Pimcore\Model\Object\Concrete $class */

                        $orderList = $this->orderManager->createOrderList();
                        $orderList->joinCustomer($class::classId());

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
        $date = new \DateTime();
        foreach ($orderAgent->getFullChangeLog() as $note) {
            /* @var \Pimcore\Model\Element\Note $note */

            // get avatar
            $user = User::getById($note->getUser());
            /* @var \Pimcore\Model\User $user */
            $avatar = $user ? sprintf('/admin/user/get-image?id=%d', $user->getId()) : null;


            // group events
            $date->setTimestamp($note->getDate());
            $group = $dateFormatter->formatDateTime($date, IntlFormatterService::DATE_MEDIUM);


            // load reference
            $reference = Concrete::getById($note->getCid());
            $title = $reference instanceof AbstractOrderItem
                ? $reference->getProduct()->getOSName()
                : null
            ;


            // add
            $arrTimeline[ $group ][] = [
                'icon' => $arrIcons[ $note->getTitle() ]
                , 'context' => $arrContext[ $note->getTitle() ] ?: 'default'
                , 'type' => $note->getTitle()
                , 'date' => $dateFormatter->formatDateTime($date->setTimestamp($note->getDate()), IntlFormatterService::DATETIME_MEDIUM)
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
     * @Route("/item-cancel", name="pimcore_ecommerce_backend_admin-order_item-cancel")
     */
    public function itemCancelAction(Request $request)
    {
        // init
        $this->view->orderItem = $orderItem = OnlineShopOrderItem::getById($request->get('id'));
        /* @var \Pimcore\Model\Object\OnlineShopOrderItem $orderItem */
        $order = $orderItem->getOrder();


        if ($request->get('confirmed') && $orderItem->isCancelAble()) {
            // init
            $agent = $this->orderManager->createOrderAgent($order);

            // cancel
            $note = $agent->itemCancel($orderItem);

            // extend log
            $note->addData('message', 'text', $request->get('message'));
            $note->save();


            // redir
            $url = $this->generateUrl("pimcore_ecommerce_backend_admin-order_detail", ['id' => $order->getId()]);

            return $this->redirect($url);
        }
    }


    /**
     * edit item
     * @Route("/item-edit", name="pimcore_ecommerce_backend_admin-order_item-edit")
     */
    public function itemEditAction(Request $request)
    {
        // init
        $this->view->orderItem = $orderItem = OnlineShopOrderItem::getById($request->get('id'));
        /* @var \Pimcore\Model\Object\OnlineShopOrderItem $orderItem */
        $order = $orderItem->getOrder();


        if ($request->get('confirmed')) {
            // change item
            $agent = $this->orderManager->createOrderAgent($order);
            $note = $agent->itemChangeAmount($orderItem, $request->get('quantity'));

            // extend log
            $note->addData('message', 'text', $request->get('message')); // 'text','date','document','asset','object','bool'
            $note->save();


            // redir
            $url = $this->generateUrl("pimcore_ecommerce_backend_admin-order_detail", ['id' => $order->getId()]);

            return $this->redirect($url);
        }
    }


    /**
     * complaint item
     * @Route("/item-complaint", name="pimcore_ecommerce_backend_admin-order_item-complaint")
     */
    public function itemComplaintAction(Request $request)
    {
        // init
        $this->view->orderItem = $orderItem = OnlineShopOrderItem::getById($request->get('id'));
        /* @var \Pimcore\Model\Object\OnlineShopOrderItem $orderItem */
        $order = $orderItem->getOrder();


        if ($request->get('confirmed')) {
            // change item
            $agent = $this->orderManager->createOrderAgent($order);
            $note = $agent->itemComplaint($orderItem, $request->get('quantity'));

            // extend log
            $note->addData('message', 'text', $request->get('message'));
            $note->save();


            // redir
            $url = $this->generateUrl("pimcore_ecommerce_backend_admin-order_detail", ['id' => $order->getId()]);

            return $this->redirect($url);
        }
    }
}
