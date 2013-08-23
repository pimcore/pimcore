
<?php $this->template("/includes/content-headline.php"); ?>


<?php echo $this->areablock("content"); ?>

<?php if(!$this->success) { ?>
    <form class="form-horizontal" role="form" action="" method="post">
        <div class="form-group">
            <label class="col-lg-2 control-label"><?php echo $this->translate("Firstname"); ?></label>
            <div class="col-lg-10">
                <input name="firstname" type="text" class="form-control" placeholder="" value="<?php echo $this->firstname; ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label"><?php echo $this->translate("Lastname"); ?></label>
            <div class="col-lg-10">
                <input name="lastname" type="text" class="form-control" placeholder="" value="<?php echo $this->lastname; ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label"><?php echo $this->translate("E-Mail"); ?></label>
            <div class="col-lg-10">
                <input name="email" type="text" class="form-control" placeholder="example@example.com" value="<?php echo $this->email; ?>">
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <div class="checkbox">
                    <label>
                        <input type="checkbox"> <?php echo $this->translate("Check me out"); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <button type="submit" class="btn btn-default"><?php echo $this->translate("Submit"); ?></button>
            </div>
        </div>
    </form>
<?php } else { ?>

    <h2><?php echo $this->translate("Thank you very much"); ?></h2>

    <p>
        We received the following information from you:

        <br />
        <br />

        <b>Firstname: </b> <?php echo $this->firstname; ?><br />
        <b>Lastname: </b> <?php echo $this->lastname; ?><br />
        <b>E-Mail: </b> <?php echo $this->email; ?><br />
    </p>
<?php } ?>
