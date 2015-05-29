<div id="cleanUp" class="modal fade">
    <div class="modal-dialog">
        <form class="form-horizontal js-cleanup-modal-form"
              action="<?=$this->url(array_merge($this->urlParams, ['action' => 'cleanup']))?>" method="get">
        <div class="modal-content">
            <!-- dialog body -->
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="clearfix"></div>
            </div>
            <div class="modal-body-content">

                    <h3><?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-headline')?></h3>

                    <div class="form-group">
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default cleanup-radio">
                                <input type="radio" name="usage" value='used' class="form-control">
                                <?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-used-checkbox')?>
                            </label>

                            <label class="btn btn-default cleanup-radio">
                                <input type="radio" name="usage" value='unused' class="form-control">
                                <?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-unused-checkbox')?>
                            </label>
                            <label class="btn btn-default cleanup-radio">
                                <input type="radio" name="usage" value='both' class="form-control">
                                <?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-both-checkbox')?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px">
                        <div class="col col-sm-6">
                            <label><?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-older-than')?></label>
                            <input type="text" name="olderThan" class="form-control js-datepicker">
                        </div>
                    </div>

            </div>

            <!-- dialog buttons -->
            <div class="modal-footer">
                <div class="col col-sm-6 text-left">
                    <p>
                        <?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-infotext')?>
                    </p>
                </div>
                <button onclick="$('.js-cleanup-modal-form').submit()" type="submit" class="btn btn-primary js-loading" data-msg="<?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-loadingtext')?>">
                    <?=$this->ts('plugin_onlineshop_voucherservice_modal_cleanup-submit-button')?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->ts('plugin_onlineshop_voucherservice_modal_cancle')?></button>
            </div>
        </div>
        </form>
    </div>
</div>