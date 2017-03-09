<?php $this->template("/content/default.php"); ?>

<?php if(!$this->success) { ?>

    <?php if ($this->unsubscribeMethod) { ?>
        <div class="alert alert-danger">
            <?php if ($this->unsubscribeMethod == "email") { ?>
                Sorry, we don't have your address in our database.
            <?php } else { ?>
                Sorry, your unsubscribe token is invalid, try to remove your address manually:
            <?php } ?>
        </div>
    <?php } ?>


    <form class="form-horizontal" role="form" action="" method="post">

        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate("E-Mail"); ?></label>
            <div class="col-lg-10">
                <input name="email" type="text" class="form-control" placeholder="example@example.com" value="<?= $this->escape($this->getParam("email")); ?>">
            </div>
        </div>

        <br />

        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <input type="submit" name="submit" class="btn btn-default" value="<?= $this->translate("Submit"); ?>">
            </div>
        </div>
    </form>
<?php } else { ?>
    <div class="alert alert-success">
        <h2>Unsubscribed</h2>
    </div>
<?php } ?>