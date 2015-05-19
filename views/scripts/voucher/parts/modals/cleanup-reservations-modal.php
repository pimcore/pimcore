<div id="cleanup-reservations" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- dialog body -->
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="clearfix"></div>
            </div>
            <div class="modal-body-content">
                <form class="form-horizontal js-cleanup-modal-form"
                      action="/plugin/OnlineShop/voucher/cleanup-
                      reservations?id=<?= $this->getParam('id') ?>">
                    <h3>Release Tokens</h3>

                    <div class="form-group" style="margin-top: 20px">
                        <div class="col col-sm-8">
                            <label>Older than ... minutes.</label>
                            <input type="number" name="duration" class="form-control form-control-25 text-center" min="0" value ="5">
                        </div>
                        <input type="hidden" name="id" value="<?= $this->getParam('id') ?>">
                    </div>
                </form>
            </div>

            <!-- dialog buttons -->
            <div class="modal-footer">
                <div class="col col-sm-6 text-left">
                    <p>
                        The Cleanup will remove token reservations that are older than than the specified duration.
                    </p>
                </div>
                <button onclick="$('.js-cleanup-modal-form').submit()" class="btn btn-primary js-loading"
                        data-msg="Cleaning up Tokens, please wait.">Cleanup Reservations
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>