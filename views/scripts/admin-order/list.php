<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 30.09.2014
 * Time: 09:29
 *
 * @var Zend_Paginator $paginator
 * @var OnlineShop\Framework\OrderManager\IOrderList $listing
 */

$paginator = $this->paginator;
$listing = $this->listing;


$this->headLink()->appendStylesheet('/plugins/OnlineShop/static/vendor/pickadate.classic.css');
$this->headLink()->appendStylesheet('/plugins/OnlineShop/static/vendor/pickadate.classic.date.css');
$this->headScript()->appendFile('/plugins/OnlineShop/static/vendor/picker.v3.5.3.js');
$this->headScript()->appendFile('/plugins/OnlineShop/static/vendor/picker.date.v3.5.3.js');
?>
<div class="page-header">
    <h1><?= $this->translate('online-shop.back-office.order-list') ?></h1>
</div>

<div class="panel panel-default">
    <form class="form" role="search">
        <div class="panel-body">
            <fieldset class="row">
                <div class="form-group col-sm-4">

                    <div class="input-group">
                        <div class="input-group-btn" id="search-filter">
                            <?php
                            $arrFields = [
                                'order' => $this->translate('online-shop.back-office.order-list.filter-order')
//                                , 'product' => $this->translate('online-shop.back-office.order-list.filter-product')
                                , 'productType' => $this->translate('online-shop.back-office.order-list.filter-product-type')
                            ];
                            $selected = $this->getParam('search', 'order');
                            ?>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" data-target="#">
                                <span data-bind="label"><?= $arrFields[$selected] ?></span> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <?php foreach($arrFields as $field => $label): ?>
                                    <li><a href="#" data-value="<?= $field ?>"><?= $label ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <input type="hidden" id="search-query" name="search" value="<?= $selected ?>" />
                        <input type="text" class="form-control" name="q" placeholder="Suche" value="<?= $this->getParam('q') ?>">
                    </div>
                </div>
                <div class="form-group col-sm-2">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></div>
                        <input type="text" class="form-control date" name="from" placeholder="Buchungen von" value="<?= $this->escape($this->getParam('from')) ?>">
                    </div>
                </div>
                <div class="form-group col-sm-2">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></div>
                        <input type="text" class="form-control date" name="till" placeholder="Buchungen bis" value="<?= $this->escape($this->getParam('till')) ?>">
                    </div>
                </div>
                <?php
                $listPricingRule = new OnlineShop_Framework_Impl_Pricing_Rule_List();
                $list = $listPricingRule->load();
                if(count($list) > 0): ?>
                    <div class="form-group col-sm-4">
                        <div class="input-group">
                            <div class="input-group-addon"><span class="glyphicon glyphicon-tag"></span></div>
                            <select class="form-control" name="pricingRule">
                                <option value="">Pricing Rules</option>
                                <?php foreach($list as $item): ?>
                                    <option value="<?= $item->getId() ?>" <?= $item->getId() == $this->getParam('pricingRule') ? 'selected':'' ?>><?= $item->getLabel('de_DE') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </fieldset>
        </div>
        <div class="panel-footer text-center">
            <button type="submit" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-filter"></span> Suchen</button>
        </div>
    </form>
</div>


<table class="table table-striped table-hover">
    <caption>Gefunden: <?= $paginator->getTotalItemCount(); ?></caption>
    <thead>
    <tr>
        <th width="180">Best.-Nr.</th>
        <th width="180">Eingegangen</th>
        <th width="80">Artikel</th>
        <th></th>
        <th width="100">Preis</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $totalSum = new Zend_Currency( OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale() );
    foreach($paginator as $item):
        /* @var \OnlineShop\Framework\OrderManager\IOrderListItem $item */
        $totalSum->add( $item->getTotalPrice() );
        ?>
        <tr>
            <td>
                <?php
                $urlDetail = $this->url(['action' => 'detail', 'controller' => 'admin-order', 'module' => 'OnlineShop', 'id' => $item->getOrderId()], null, true);
                ?>
                <a href="<?= $urlDetail ?>"><?= $item->getOrderNumber() ?></a>
            </td>
            <td>
                <?php
                echo $item->getOrderDate() instanceof Zend_Date
                    ? $item->getOrderDate()
                    : new Zend_Date( $item->getOrderDate() );
                ?>
            </td>
            <td><?= $item->getItems() ?></td>
            <td>
            </td>
            <td class="text-right"><?= $totalSum->toCurrency($item->getTotalPrice()) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="4"></td>
        <td class="text-right">
            <strong><?= $totalSum ?></strong>
        </td>
    </tr>
    </tfoot>
</table>

<?php if($paginator->getPages()->pageCount > 1): ?>
    <div class="text-center">
        <?= $this->paginationControl($paginator, 'Sliding', 'includes/pagination/default.php', $this->getAllParams()); ?>
    </div>
<?php endif; ?>

<script>
    <?php $this->headScript()->captureStart() ?>
    $(document).ready(function() {
        $("input.date").pickadate({
            format: "dd.mm.yyyy"
            , today: ""
            , clear: ""
            , close: false
            , firstDay: 1
            , editable: true
        });


        $('#search-filter .dropdown-menu li').on('click', function (e) {

            $(this).closest( '.input-group-btn' )
                .find( '[data-bind="label"]' ).text( $(this).text() )
                .end()
                .children( '.dropdown-toggle' ).dropdown( 'toggle' );

            $('#search-query').val( $(this).find('[data-value]').data('value') );

            return false;
        });
    });

    <?php $this->headScript()->captureEnd() ?>
</script>
