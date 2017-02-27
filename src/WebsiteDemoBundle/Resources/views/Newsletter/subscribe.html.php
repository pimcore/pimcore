<?php $this->extend('WebsiteDemoBundle::layout.html.php') ?>

<?= $this->template('WebsiteDemoBundle:Includes:content-default.html.php') ?>

<?php if(!$this->success) { ?>

    <?php if($this->getParam("submit")) { ?>
        <div class="alert alert-danger">
            <?= $this->translate("Sorry, something went wrong, please check the data in the form and try again!"); ?>
        </div>
        <br />
        <br />
    <?php } ?>

    <form class="form-horizontal" role="form" action="" method="post">
        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate("Gender"); ?></label>
            <div class="col-lg-10">
                <select name="gender" class="form-control">
                    <option value="male"<?php if($this->getParam("gender") == "male") { ?> selected="selected"<?php } ?>>Male</option>
                    <option value="female"<?php if($this->getParam("gender") == "female") { ?> selected="selected"<?php } ?>>Female</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate("Firstname"); ?></label>
            <div class="col-lg-10">
                <input name="firstname" type="text" class="form-control" placeholder="" value="<?= $this->escape($this->getParam("firstname")); ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate("Lastname"); ?></label>
            <div class="col-lg-10">
                <input name="lastname" type="text" class="form-control" placeholder="" value="<?= $this->escape($this->getParam("lastname")); ?>">
            </div>
        </div>
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
    <div class="alert alert-success"><?= $this->translate("Success, Please check your mailbox!"); ?></div>
<?php } ?>
