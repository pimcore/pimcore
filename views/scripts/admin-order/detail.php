<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 01.10.2014
 * Time: 15:54
 * @var Website_Shop_Order $order
 */

$order = $this->order;

?>
<div class="row" xmlns="http://www.w3.org/1999/html">
    <div class="col-xs-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <strong>Bestellung: <?= $order->getOrdernumber() ?></strong>
                    <?php if($order->getAgentId()):
                        $agent = User::getById( $order->getAgentId() );
                        ?>
                        <small>Erfasst von <?= $agent->getName() ?></small>
                    <?php endif; ?>
                </h2>
            </div>
            <?php if($order->getAgentId()): ?>
                <div class="panel-body">
                    <form role="form" class="form-inline" method="post">
                        <div class="form-group">
                            <label for="paymentState">Bezahlt</label>
                            <select id="paymentState" name="order-paymentState" class="form-control input-sm">
                                <option>Nein</option>
                                <option value="cleared" <?= $order->isPaid() ? 'selected':'' ?>>Ja</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-default btn-sm">setzen</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php if($order->hasPayment()): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-credit-card"></span> Zahlungsvorgänge
                    <?php if($order->hasSplitOrderPayment()): ?>
                        <span class="pull-right"><?= $order->getSplitOrderPaymentList()->count() ?> Teilzahlungen</span>
                    <?php endif; ?>
                </div>
                <table class="table table-condensed">
                    <tbody>
                    <?php foreach($order->getPaymentInfoHistory() as $item):
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
                            <td width="40" class="text-center">
                                <?php
                                $icon = strpos($item->getMessage(), 'settlement') !== false
                                    ? 'glyphicon glyphicon-plus-sign text-success'
                                    :
                                        (
                                            strpos($item->getMessage(), 'credit') !== false
                                            ? 'glyphicon glyphicon-minus-sign text-danger'
                                            : ''
                                        )
                                ;
                                if($item->getPaymentState() == 'committed' && $icon)
                                {
                                    echo sprintf('<span class="%s"></span>', $icon);
                                }
                                ?>
                            </td>
                            <td width="100">
                                <small><?= $item->getProvider_datatrans_amount() ?></small>
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

        <?php if($order->getUserComment()): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-bell"></span> User Kommentar
            </div>
            <div class="panel-body">
                <?= nl2br($order->getUserComment()) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-xs-5">
        <div class="panel panel-default">
            <div class="panel-heading"><span class="glyphicon glyphicon-user"></span> Kunde</div>
            <div class="panel-body row">
                <div class="col-xs-6">
                    <h4>Lieferanschrift</h4>
                    <address>
                        <?= $order->getDeliveryFirstname().' '.$order->getDeliveryLastname() ?><br>
                        <?= $order->getDeliveryStreet() ?><br>
                        <?= $order->getDeliveryZip().' - '.$order->getDeliveryCity() ?><br>
                        <?= strtoupper(Zend_Locale::getTranslation($order->getDeliveryCountry(), 'territory', $this->language)) ?><br/>
                        <a href="mailto:<?= $order->getInvoiceEMail()?>"><?= $order->getInvoiceEMail()?></a><br>
                    </address>
                </div>
                <?php if($order->hasInvoiceAddress()): ?>
                    <div class="col-xs-6">
                        <h4>Rechnungsanschrift</h4>
                        <address>
                            <?= $order->getInvoiceFirstname().' '.$order->getInvoiceLastname() ?><br>
                            <?= $order->getInvoiceStreet() ?><br>
                            <?= $order->getInvoiceZip().' - '.$order->getInvoiceCity() ?><br>
                            <?= strtoupper(Zend_Locale::getTranslation($order->getInvoiceCountry(), 'territory', $this->language)) ?><br/>
                        </address>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="panel panel-default">
    <div class="panel-heading"><span class="glyphicon glyphicon-list-alt"></span> Bestellte Artikel</div>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Artikel</th>
            <th>Zusatzinfos</th>
            <th class="text-center">Anz</th>
            <th>Gesamt</th>
            <th></th>
        </tr>
        </thead>
        <tfoot>
        <!--
        <tr class="active">
            <td colspan="6"></td>
        </tr>
        <?php foreach($order->getPriceModifications() as $modification): /* @var Object_Fieldcollection_Data_OrderPriceModifications $modification */ ?>
            <tr>
                <td colspan="4" class="text-right"><?= $modification->getName() ?></td>
                <th colspan="2"><strong><?= $order->getCurrency()->toCurrency( $modification->getAmount() ) ?></strong></th>
            </tr>
        <?php endforeach; ?>
        -->
        <tr class="active">
            <td colspan="4" class="text-right">Total</td>
            <th colspan="2"><strong><?= $order->getCurrency()->toCurrency( $order->getTotalPrice() ) ?></strong></th>
        </tr>
        </tfoot>
        <tbody>
        <?php foreach($order->getItems() as $item):
            /* @var Website_Shop_OrderItem $item */
            $product = $item->getProduct();
            ?>
            <tr>
                <td><a href="#" data-action="edit" data-id="<?= $item->getId() ?>"><?= $item->getId() ?></a></td>
                <td>
                    <?php
                    echo $item->getOrderState() == 'cancel'
                        ? sprintf('<s>%s</s>', $item->getProductName())
                        : $item->getProductName()
                    ?>
                </td>
                <td>
                    <?php
                    if($product instanceof Website_Shop_Event)
                    {
                        $orderDate = new Website_Shop_Event_Date( $product->getOrderDate() );
                        echo sprintf('<span class="label label-default">Am</span> <small>%s</small> <span class="label label-default">Erwachsene</span> <small>%s</small> <span class="label label-default">Kinder</span> <small>%s</small>'
                            , $orderDate
                            , $product->getOrderAdultCount()
                            , $product->getOrderChildCount()
                        );
                    }
                    else if($product instanceof Website_Shop_Package_Hotel)
                    {
                        echo sprintf('<span class="label label-default">Ankunft</span> <small>%s</small> <span class="label label-default">Hotel</span> <small>%s</small> <span class="label label-default">Nächte</span> <small>%s</small> <span class="label label-default">Ski Tage</span> <small>%s</small>'
                            , $product->getOrderArrival()->get(Zend_Date::DATE_MEDIUM)
                            , $product->getOrderHotel()->getName()
                            , $product->getOrderHotelNights()
                            , $product->getOrderSkiDays()
                        );
                    }
                    else if($product instanceof Website_Shop_TicketingCatalogTicketProduct)
                    {
                        echo sprintf('<span class="label label-default">Gruppe</span> <small>%s</small> <span class="label label-default">Erster Skitag</span> <small>%s</small>'
                            , $product->getOrderAgeGroup()->getName()
                            , $product->getOrderStart()->get(Zend_Date::DATE_MEDIUM)
                        );
                    }
                    else if($product instanceof Website_Shop_Rent_Product)
                    {
                        echo sprintf('<span class="label label-default">Tage</span> <small>%s</small>'
                            , $product->getOrderDays()
                        );
                    }
                    else if($product instanceof Website_Shop_Course)
                    {
                        echo sprintf('<span class="label label-default">Am</span> <small>%s</small> <span class="label label-default">Tage</span> <small>%s</small> <span class="label label-default">Schule</span> <small>%s</small>'
                            , $product->getOrderDate()->get(Zend_Date::DATE_MEDIUM)
                            , $product->getOrderDays()
                            , $product->getOrderSchool()->getHeadlineH1()
                        );
                        if($product->getOrderAdultCount())
                        {
                            echo sprintf(' <span class="label label-default">Erwachsene</span> <small>%s</small>'
                                , $product->getOrderAdultCount()
                            );
                        }
                        if($product->getOrderYouthCount())
                        {
                            echo sprintf(' <span class="label label-default">Jugendliche</span> <small>%s</small>'
                                , $product->getOrderYouthCount()
                            );
                        }
                        if($product->getOrderChildCount())
                        {
                            echo sprintf(' <span class="label label-default">Kinder</span> <small>%s</small>'
                                , $product->getOrderChildCount()
                            );
                        }
                    }
                    else
                    {
                        echo $item->getComment();
                    }
                    ?>
                </td>
                <td class="text-center"><?= $item->getAmount() ?></td>
                <td>
                    <?php
                    echo sprintf('<span class="label %2$s">%1$s</span>'
                        , $order->getCurrency()->toCurrency( $item->getTotalPrice() )
                        , $item->isPaid()
                            ? 'label-success'
                            : ( $item->isCanceled()
                                ? 'label-danger'
                                : 'label-warning'
                            )
                    );
                    ?>
                </td>

                <td class="text-right">
                    <div class="btn-group">
                        <?php if($item->getForwardingReference()): ?>
                            <a href="https://matterhornparadise.skiticketshop.com/admin/order/?action=detail&order.id=<?= $item->getForwardingReference() ?>" class="btn btn-xs btn-default" target="_blank" ><span class="glyphicon glyphicon-link"></span></a>
                        <?php endif; ?>

                        <?php $urlVoucher = $item->getVoucherLink($this);
                        if ($urlVoucher) : ?>
                            <a href="<?= $urlVoucher ?>" target="_blank" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></a>
                        <?php endif; ?>

                        <?php
                        $urlDetails = $this->url([
                            'action' => 'item-detail'
                            , 'controller' => 'admin-order'
                            , 'module' => 'BackOffice'
                            , 'id' => $item->getId()
                        ], null, true);
                        ?>
                        <a href="<?= $urlDetails ?>" class="btn btn-xs btn-default" data-toggle="modal" data-target="#popup"><span class="glyphicon glyphicon-eye-open"></a>

                        <?php if($order->hasSplitOrderPayment() && $item->hasPaymentInfo()):
                            $urlPaymentInfo = $this->url([
                            'action' => 'item-payment-info'
                            , 'controller' => 'admin-order'
                            , 'module' => 'BackOffice'
                            , 'id' => $item->getId()
                            ], null, true);
                            ?>
                            <a href="<?= $urlPaymentInfo ?>" class="btn btn-xs btn-default" data-toggle="modal" data-target="#popup"><span class="glyphicon glyphicon-credit-card"></a>
                        <?php endif; ?>
                    </div>

                    <?php if($item->isCancelAble() && !$item->isCanceled()):
                        $urlCancel = $this->url([
                            'action' => 'cancel-item'
                            , 'controller' => 'admin-order'
                            , 'module' => 'BackOffice'
                            , 'id' => $item->getId()
                        ]);
                        ?>
                        <a href="<?= $urlCancel ?>" data-toggle="modal" data-target="#popup" class="btn btn-xs btn-danger <?= $item->getOrderState() == 'cancel' ? 'disabled' : '' ?>"><span class="glyphicon glyphicon-remove"></span></a>
                    <?php endif; ?>

                    <?php if($item->getForwardingState() == 'error'): ?>
                        <span class="glyphicon glyphicon-exclamation-sign text-danger" title="Fehler bei der Übertragung"></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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
    jQuery(document).ready(function() {

        $('body').on('hidden.bs.modal', '.modal', function () {
            $(this).removeData('bs.modal');
        });


        $('[data-action=edit]').click(function () {

            pimcore.helpers.openObject( $(this).data('id') , "object");

        });

    });
    <?php $this->headScript()->captureEnd(); ?>
</script>