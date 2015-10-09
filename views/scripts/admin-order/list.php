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
                        <input type="text" class="form-control" name="q" placeholder="<?= $this->translate('online-shop.back-office.order-list.search.placeholder') ?>" value="<?= $this->escape($this->getParam('q')) ?>">
                    </div>
                </div>
                <div class="form-group col-sm-2">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></div>
                        <input type="text" class="form-control date" name="from" placeholder="<?= $this->translate('online-shop.back-office.order-list.filter-date.from') ?>" value="<?= $this->escape($this->getParam('from')) ?>">
                    </div>
                </div>
                <div class="form-group col-sm-2">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></div>
                        <input type="text" class="form-control date" name="till" placeholder="<?= $this->translate('online-shop.back-office.order-list.filter-date.from') ?>" value="<?= $this->escape($this->getParam('till')) ?>">
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
                                <option value=""><?= $this->translate('online-shop.back-office.order-list.filter-pricing-rules') ?></option>
                                <?php foreach($list as $item): ?>
                                    <option value="<?= $item->getId() ?>" <?= $item->getId() == $this->getParam('pricingRule') ? 'selected':'' ?>><?= $item->getLabel() ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </fieldset>
        </div>
        <div class="panel-footer text-center">
            <button type="submit" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-filter"></span> <?= $this->translate('online-shop.back-office.order-list.search.button') ?></button>
        </div>
    </form>
</div>


<table class="table table-striped table-hover">
    <caption><?= $this->translate('online-shop.back-office.order-list.result-count') ?>: <?= $paginator->getTotalItemCount(); ?></caption>
    <thead>
    <tr>
        <th width="180"><?= $this->translate('online-shop.back-office.order') ?></th>
        <th width="180"><?= $this->translate('online-shop.back-office.order.date') ?></th>
        <th width="80"><?= $this->translate('online-shop.back-office.order.order-items') ?></th>
        <th></th>
        <th width="100"><?= $this->translate('online-shop.back-office.order.price.total') ?></th>
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
