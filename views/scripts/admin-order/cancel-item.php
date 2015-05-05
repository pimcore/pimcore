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
        <h4 class="modal-title"><?= $orderItem->getProductName() ?> <small>Stornieren</small></h4>
    </div>
    <div class="modal-body">
        <div class="alert alert-warning" role="alert">...</div>

        <div class="row form-group">
            <label class="col-sm-12" for="inputBody">Message</label>
            <div class="col-sm-12"><textarea class="form-control" id="inputBody" rows="8"></textarea></div>
        </div>

    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-danger" name="confirmed" value="1">Stornieren</button>
    </div>
</form>