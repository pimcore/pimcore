<div id="cleanUp" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- dialog body -->
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="clearfix"></div>
            </div>
            <div class="modal-body-content">
                <form class="form-horizontal js-cleanup-modal-form"
                      action="/plugin/OnlineShop/voucher/cleanup?id=<?= $this->getParam('id') ?>">
                    <h3>Remove Tokens</h3>

                    <div class="form-group">

                        <div class="col col-sm-4">
                            <label>Used</label>
                            <input type="checkbox" name="used" class="form-control">
                        </div>
                        <div class="col col-sm-4">
                            <label>Unused</label>
                            <input type="checkbox" name="unused" class="form-control">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px">
                        <div class="col col-sm-8">
                            <label>Older than</label>
                            <input type="text" name="olderThan" class="form-control js-datepicker">
                        </div>
                        <input type="hidden" name="id" value="<?= $this->getParam('id') ?>">
                    </div>
                </form>
            </div>

            <!-- dialog buttons -->
            <div class="modal-footer">
                <div class="col col-sm-6 text-left">
                    <p>
                        The Cleanup will remove tokens in your series, to improve performance for creating new tokens
                        and ...
                    </p>
                </div>
                <button onclick="$('.js-cleanup-modal-form').submit()" class="btn btn-primary js-loading"
                        data-msg="Cleaning up Tokens, please wait.">Cleanup Tokens
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>