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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('PimcoreEcommerceFrameworkBundle::back-office.html.php');

$paginator = $this->paginator;
$listing = $this->listing;


$this->headLink()->appendStylesheet('/bundles/pimcoreecommerceframework/vendor/pickadate.classic.css');
$this->headLink()->appendStylesheet('/bundles/pimcoreecommerceframework/vendor/pickadate.classic.date.css');
$this->headScript()->appendFile('/bundles/pimcoreecommerceframework/vendor/picker.v3.5.3.js');
$this->headScript()->appendFile('/bundles/pimcoreecommerceframework/vendor/picker.date.v3.5.3.js');

$formatter = Pimcore::getContainer()->get('pimcore.locale.intl_formatter');

?>
<div class="page-header">
    <h1><?= $this->translateAdmin('online-shop.back-office.order-list') ?></h1>
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
                                'order' => $this->translateAdmin('online-shop.back-office.order-list.filter-order')
                                , 'productType' => $this->translateAdmin('online-shop.back-office.order-list.filter-product-type')
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
                        <input type="text" class="form-control" name="q" placeholder="<?= $this->translateAdmin('online-shop.back-office.order-list.search.placeholder') ?>" value="<?= $this->escape($this->getParam('q')) ?>">
                    </div>
                </div>
                <div class="form-group col-sm-2">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></div>
                        <input type="text" class="form-control date" name="from" placeholder="<?= $this->translateAdmin('online-shop.back-office.order-list.filter-date.from') ?>" value="<?= $this->escape($this->getParam('from')) ?>">
                    </div>
                </div>
                <div class="form-group col-sm-2">
                    <div class="input-group">
                        <div class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></div>
                        <input type="text" class="form-control date" name="till" placeholder="<?= $this->translateAdmin('online-shop.back-office.order-list.filter-date.from') ?>" value="<?= $this->escape($this->getParam('till')) ?>">
                    </div>
                </div>
                <?php
                $listPricingRule = new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Rule\Listing();
                $list = $listPricingRule->load();
                if(count($list) > 0): ?>
                    <div class="form-group col-sm-4">
                        <div class="input-group">
                            <div class="input-group-addon"><span class="glyphicon glyphicon-tag"></span></div>
                            <select class="form-control" name="pricingRule">
                                <option value=""><?= $this->translateAdmin('online-shop.back-office.order-list.filter-pricing-rules') ?></option>
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
            <button type="submit" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-filter"></span> <?= $this->translateAdmin('online-shop.back-office.order-list.search.button') ?></button>
        </div>
    </form>
</div>


<table class="table table-striped table-hover">
    <caption><?= $this->translateAdmin('online-shop.back-office.order-list.result-count') ?>: <?= $paginator->getTotalItemCount(); ?></caption>
    <thead>
    <tr>
        <th width="180"><?= $this->translateAdmin('online-shop.back-office.order') ?></th>
        <th width="180"><?= $this->translateAdmin('online-shop.back-office.order.date') ?></th>
        <th width="80"><?= $this->translateAdmin('online-shop.back-office.order.order-items') ?></th>
        <th width="100"><?= $this->translateAdmin('online-shop.back-office.order.price.total') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $totalSum = 0;
    $defaultCurrency = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getEnvironment()->getDefaultCurrency();
    foreach($paginator as $item):
        /* @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderListItem $item */
        $totalSum += $item->getTotalPrice();
        ?>
        <tr>
            <td>
                <?php
                $urlDetail = $this->path('pimcore_ecommerce_backend_admin-order_detail', ['id' => $item->getOrderId()], null, true);
                ?>
                <a href="<?= $urlDetail ?>"><?= $item->getOrderNumber() ?></a>
            </td>
            <td>
                <?php
                    $date = $item->getOrderDate();
                    if($date instanceof DateTime) {
                        $date = new DateTime();
                        $date->setTimestamp($item->getOrderDate());
                    }

                    echo $formatter->formatDateTime($date, \Pimcore\Bundle\PimcoreBundle\Service\IntlFormatterService::DATETIME_MEDIUM);
                ?>
            </td>
            <td><?= $item->getItems() ?></td>
            </td>
            <td class="text-right"><?= $formatter->formatCurrency($item->getTotalPrice(), $item->getCurrency()) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="3"></td>
        <td class="text-right">
            <strong><?= $defaultCurrency->toCurrency($totalSum) ?></strong>
        </td>
    </tr>
    </tfoot>
</table>

<?php if($paginator->getPages()->pageCount > 1): ?>
    <div class="text-center">
        <?= $this->render(
            "PimcoreEcommerceFrameworkBundle:Includes:paging.html.php",
            get_object_vars($paginator->getPages("Sliding"))
        ); ?>
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
