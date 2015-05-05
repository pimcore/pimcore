<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 02.10.2014
 * Time: 12:53
 *
 * @var Website_Shop_OrderItem $orderItem;
 */

$orderItem = $this->orderItem;
$product = $orderItem->getProduct();

$urlSave = $this->url();
?>
<form action="<?= $urlSave ?>" method="post">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title"><?= $orderItem->getProductName() ?> <small>Bearbeiten</small></h4>
    </div>
    <div class="modal-body">



    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary" name="confirmed" value="1">Speichern</button>
    </div>
</form>