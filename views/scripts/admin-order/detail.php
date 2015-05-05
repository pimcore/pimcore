<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 01.10.2014
 * Time: 15:54
 * @var OnlineShop\Framework\Impl\OrderManager\Order\Agent $orderAgent
 */

$orderAgent = $this->orderAgent;
$order = $orderAgent->getOrder();
$currency = $orderAgent->getCurrency();

?>
<div class="row order-detail">
    <div class="col-xs-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <strong>Bestellung: <a href="#" data-action="open" data-id="<?= $order->getId() ?>"><?= $order->getOrdernumber() ?></a></strong>
                    <small><?= $order->getOrderDate() ?></small>
                </h2>
            </div>
        </div>
        <ul class="well well-sm nav nav-justified">
            <li class="col-md-12">
                <a class="text-center" href="#"><span class="glyphicon glyphicon-envelope"></span> <br>Nachricht</a>
            </li>
            <li class="col-md-12">
                <a class="text-center" href="#"><span class="glyphicon glyphicon-print"></span> <br>Tasks</a>
            </li>
            <li class="col-md-12">
                <a class="text-center" href="#"><span class="glyphicon glyphicon-print"></span> <br>Retoure</a>
            </li>
            <li class="col-md-12">
                <a class="text-center text-danger" href="#"><span class="glyphicon glyphicon-remove"></span> <br>Storno</a>
            </li>
        </ul>

        <?php if($orderAgent->hasPayment()): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-credit-card"></span> Zahlungsvorgänge
                </div>
                <table class="table table-condensed">
                    <tbody>
                    <?php foreach($order->getPaymentInfo() as $item):
                        if(!$item->getPaymentFinish())
                        {

                            continue;
                        }

                        switch($item->getPaymentState())
                        {
                            case 'paymentAuthorized':
                                $class = 'bg-info text-info';
                                break;
                            case 'committed':
                                $class = 'bg-success text-success';
                                break;
                            case 'aborted':
                            default:
                                $class = 'bg-danger text-danger';
                                break;
                        }
                        ?>
                        <tr>
                            <td width="130"><small><?= $item->getPaymentFinish() ? $item->getPaymentFinish()->toString(Zend_Date::DATETIME_MEDIUM) : '' ?></small></td>
                            <td width="100">
                                <small>
                                    <?php
                                    $provider[] = 'qpay';
                                    $provider[] = 'datatrans';
                                    $provider[] = 'paypal';

                                    $amount = null;
                                    foreach($provider as $p)
                                    {
                                        $getter = sprintf('getProvider_%s_amount', $p);
                                        if(method_exists($item, $getter))
                                        {
                                            $amount = $item->$getter();
                                            if($amount)
                                            {
                                                echo $amount;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                </small>
                            </td>
                            <td class="<?= $class ?>">
                                <small title="<?= $item->getPaymentState() ?>"><?= $item->getMessage() ?></small>
                            </td>
                            <td class="text-right"><small><?= $item->getPaymentReference() ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- order items -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-list-alt"></span> <?//= $this->translate('online-shop.back-office.order.order-items') ?> Bestellte Artikel

                <?php if($order->getComment()): ?>
                    <button type="button" class="btn btn-xs btn-default pull-right" data-container="body" data-toggle="popover" data-placement="right" title="User Kommentar" data-content="<?= nl2br($order->getComment()) ?>">
                        <span class="glyphicon glyphicon-comment"></span>
                    </button>
                <?php endif; ?>
            </div>
            <table class="table table-order-items">
                <thead>
                <tr>
                    <th width="70">ID</th>
                    <th>Artikel</th>
                    <th class="text-right">Preis</th>
                    <th width="60" class="text-center">Anz</th>
                    <th class="text-right" width="110">Gesamt</th>
                    <th></th>
                </tr>
                </thead>
                <tfoot>
                <tr class="active">
                    <td colspan="6"></td>
                </tr>
                <?php foreach($order->getPriceModifications() as $modification): /* @var \Pimcore\Model\Object\Fieldcollection\Data\OrderPriceModifications $modification */ ?>
                    <tr>
                        <td colspan="4" class="text-right"><?= $modification->getName() ?></td>
                        <th class="text-right"><?= $currency->toCurrency($modification->getAmount()) ?: '-' ?></th>
                    </tr>
                <?php endforeach; ?>
                <tr class="active">
                    <td colspan="4" class="text-right">Total</td>
                    <th class="text-right"><?= $currency->toCurrency($order->getTotalPrice()) ?></th>
                    <th></th>
                </tr>
                </tfoot>
                <tbody>
                <?php foreach($order->getItems() as $item):
                    /* @var Pimcore\Model\Object\OnlineShopOrderItem $item */
                    ?>
                    <tr class="">
                        <td>
                            <a href="#" data-action="open" data-id="<?= $item->getId() ?>"><?= $item->getId() ?></a>
                        </td>
                        <td>
                            <?php
                            echo $item->getOrderState() == 'cancelled'
                                ? sprintf('<s>%s</s>', $item->getProductName())
                                : $item->getProductName()
                            ?>
                        </td>
                        <td class="text-right"><?= $currency->toCurrency($item->getTotalPrice() / $item->getAmount()) ?></td>
                        <td class="text-center"><?= $item->getAmount() ?></td>
                        <td class="text-right"><?= $currency->toCurrency($item->getTotalPrice()) ?></td>
                        <td>
                            <?php if($item->getComment()): ?>
                                <button type="button" class="btn btn-xs btn-default" data-container="body" data-toggle="popover" title="User Kommentar" data-content="<?= nl2br($item->getComment()) ?>">
                                    <span class="glyphicon glyphicon-comment"></span>
                                </button>
                            <?php endif; ?>

                            <?php if($item->isEditAble() && !$item->isCanceled()):
                                $urlCancel = $this->url([
                                    'action' => 'edit-item'
                                    , 'controller' => 'admin-order'
                                    , 'module' => 'OnlineShop'
                                    , 'id' => $item->getId()
                                ]);
                                ?>
                                <a href="<?= $urlCancel ?>" data-toggle="modal" data-target="#popup" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                            <?php endif; ?>

                            <?php if($item->isCancelAble()):
                                $urlCancel = $this->url([
                                    'action' => 'cancel-item'
                                    , 'controller' => 'admin-order'
                                    , 'module' => 'OnlineShop'
                                    , 'id' => $item->getId()
                                ]);
                                ?>
                                <a href="<?= $urlCancel ?>" data-toggle="modal" data-target="#popup" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-remove"></span></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-xs-5">
        <!-- customer infos -->
        <div role="tabpanel" class="tabpanel-customer-info">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#addressInvoice" aria-controls="addressInvoice" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-file"></span> Rechnungsanschrift</a>
                </li>
                <?php if($order->hasDeliveryAddress()) :?>
                    <li role="presentation">
                        <a href="#addressDelivery" aria-controls="addressDelivery" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-home"></span> Lieferanschrift</a>
                    </li>
                <?php endif; ?>
                <li role="presentation" class="pull-right">
                    <a href="#customerDetail" aria-controls="customerDetail" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-user"></span></a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <?php
                /**
                 * print google static map
                 * @param stdClass $geoPoint
                 */
                $printMap = function (stdClass $geoPoint) {
                    $urlLink = sprintf('http://maps.google.de/maps?q=loc:%1$s,%2$s'
                        , $geoPoint->lat
                        , $geoPoint->lng
                    );
                    $urlImage = sprintf('http://maps.googleapis.com/maps/api/staticmap?center=%1$s,%2$s&zoom=11&size=200x200&sensor=false'
                        , $geoPoint->lat
                        , $geoPoint->lng
                    );
                    ?>
                    <a href="<?= $urlLink ?>" target="_blank" class="pull-right address-map">
                        <img src="<?= $urlImage ?>" alt=""/>
                    </a>
                    <?php
                };
                ?>

                <div role="tabpanel" class="tab-pane active" id="addressInvoice">
                    <div class="row">
                        <div class="col-md-6">
                            <address>
                                <?php
                                $address = [];
                                if($order->getCustomerCompany())
                                {
                                    echo sprintf('<h4>%1$s</h4>', nl2br($order->getCustomerCompany()));
                                }
                                if($order->getCustomerName())
                                {
                                    echo sprintf('%1$s<br/>', $order->getCustomerName());
                                }
                                ?>
                                <?= $order->getCustomerStreet() ?><br/>
                                <?= $order->getCustomerZip().' - '.$order->getCustomerCity() ?><br/>
                                <?= strtoupper(Zend_Locale::getTranslation($order->getCustomerCountry(), 'territory')) ?><br/>
                                <?= sprintf('<a href="mailto:%1$s">%1$s</a>', $order->getCustomer()->getEmail()) ?>
                            </address>
                        </div>

                        <?= $this->geoAddressInvoice ? $printMap($this->geoAddressInvoice) : '' ?>
                    </div>
                </div>

                <?php if($order->hasDeliveryAddress()) :?>
                    <div role="tabpanel" class="tab-pane" id="addressDelivery">
                    <div class="row">
                        <div class="col-md-6">
                            <address>
                                <?php
                                $address = [];
                                if($order->getDeliveryCompany())
                                {
                                    echo sprintf('<h4>%1$s</h4>', nl2br($order->getDeliveryCompany()));
                                }
                                if($order->getDeliveryName())
                                {
                                    echo sprintf('%1$s<br/>', $order->getDeliveryName());
                                }
                                ?>
                                <?= $order->getDeliveryStreet() ?><br/>
                                <?= $order->getDeliveryZip().' - '.$order->getDeliveryCity() ?><br/>
                                <?= strtoupper(Zend_Locale::getTranslation($order->getDeliveryCountry(), 'territory')) ?><br/>
                            </address>
                        </div>

                        <?= $this->geoAddressDelivery ? $printMap($this->geoAddressDelivery) : '' ?>
                    </div>
                </div>
                <?php endif; ?>

                <div role="tabpanel" class="tab-pane" id="customerDetail">

                    <h4>Kundenkonto</h4>
                    E-Mail: sag@ag.ag <br/>
                    Registriert Seite: 14.12.2014 <br/>
                    Bestellungen: 147

                </div>
            </div>
        </div>

        <!-- timeline -->
        <?php
        $arrTimeline = [
            [
                'icon' => 'glyphicon glyphicon-credit-card'
                , 'type' => 'success'
                , 'date' => '27.03.2015'
                , 'user' => 'http://api.randomuser.me/portraits/women/11.jpg'
                , 'message' => 'Geldeingang: 2.000,39 €'
            ]
            , [
                'icon' => 'glyphicon glyphicon-plane'
                , 'type' => 'success'
                , 'date' => '27.03.2015'
                , 'user' => 'http://api.randomuser.me/portraits/women/11.jpg'
                , 'message' => 'Bestellung versendet'
            ]
            , [
                'icon' => 'glyphicon glyphicon-remove'
                , 'type' => 'info'
                , 'date' => '30.03.2015'
                , 'user' => 'http://api.randomuser.me/portraits/women/11.jpg'
                , 'message' => 'Storniert: Wandgarderobe'
            ]
            , [
                'icon' => 'glyphicon glyphicon-share-alt'
                , 'type' => 'warning'
                , 'date' => '30.03.2015'
                , 'user' => 'http://api.randomuser.me/portraits/women/11.jpg'
                , 'message' => 'Retoure gestartet'
            ]
        ];

        uasort($arrTimeline, function ($a, $b) {

            $date1 = new Zend_Date($a['date']);
            $date2 = new Zend_Date($b['date']);

            return $date1->isEarlier( $date2 );
        });

        $arrTimelineGrouped = [];
        foreach($arrTimeline as $item)
        {
            $arrTimelineGrouped[ $item['date'] ][] = $item;
        }
        ?>
        <div class="timeline">

            <!-- Line component -->
            <div class="line text-muted"></div>


            <?php foreach($arrTimelineGrouped as $group): ?>
                <!-- Separator -->
                <div class="separator text-muted">
                    <time><?= $item['date'] ?></time>
                </div>
                <?php foreach($group as $item): ?>
                    <!-- Panel -->
                    <article class="panel panel-<?= $item['type'] ?>">

                        <!-- Icon -->
                        <div class="panel-heading icon">
                            <i class="<?= $item['icon'] ?>"></i>
                        </div>
                        <!-- /Icon -->

                        <!-- Body -->
                        <div class="panel-body">
                            <div class="media ng-scope">
                                <img src="<?= $item['user'] ?>" width="40" class="img-circle pull-left">
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <div class="ng-binding"><?= $item['message'] ?></div>
                                    </h4>
                                    <small class="ng-binding">2 minutes ago</small>
                                </div>
                            </div>
                        </div>
                        <!-- /Body -->

                    </article>
                    <!-- /Panel -->
                <?php endforeach; ?>

            <?php endforeach; ?>

            <!-- Separator -->
            <div class="separator text-muted">
                <time>25.03.2015</time>
            </div>
            <!-- /Separator -->

            <!-- Panel -->
            <article class="panel panel-default panel-outline">

                <!-- Icon -->
                <div class="panel-heading icon">
                    <i class="glyphicon glyphicon-shopping-cart"></i>
                </div>
                <!-- /Icon -->

                <!-- Body -->
                <div class="panel-body">
                    Bestellung eingegangen
                </div>
                <!-- /Body -->

            </article>
            <!-- /Panel -->
        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal" id="popup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>


<script type="text/javascript">
    <?php $this->headScript()->captureStart(); ?>
    $(function () {

        // pimcore open object
        $('[data-action=open]').click(function () {

            pimcore.helpers.openObject( $(this).data('id') , "object");

        });


        // enable popover
        $('[data-toggle="popover"]').popover({html: true});


        $('body').on('hidden.bs.modal', '.modal', function () {
            $(this).removeData('bs.modal');
        });

    });
    <?php $this->headScript()->captureEnd(); ?>
</script>