<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');

?>

<?php if((!$this->error && !$this->success)|| $this->editmode) { ?>
    <?= $this->template('Includes/content-default.html.php') ?>
<?php } ?>

<?php if($this->error || $this->editmode) { ?>
    <br />
    <div class="alert alert-error">
        <?= $this->input("errorMessage"); ?>
    </div>
<?php } ?>

<?php if(!$this->success) { ?>
    <form class="form-horizontal" role="form" action="" method="post">
            <div class="form-group">
                <label class="col-lg-2 control-label"><?= $this->translate("Gender"); ?></label>
                <div class="col-lg-10">
                    <select name="gender" class="form-control">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"><?= $this->translate("Firstname"); ?></label>
                <div class="col-lg-10">
                    <input name="firstname" type="text" class="form-control" placeholder="" value="<?= $this->firstname; ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"><?= $this->translate("Lastname"); ?></label>
                <div class="col-lg-10">
                    <input name="lastname" type="text" class="form-control" placeholder="" value="<?= $this->lastname; ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"><?= $this->translate("E-Mail"); ?></label>
                <div class="col-lg-10">
                    <input name="email" type="text" class="form-control" placeholder="example@example.com" value="<?= $this->email; ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"><?= $this->translate("Message"); ?></label>
                <div class="col-lg-10">
                    <textarea name="message" type="text" class="form-control" placeholder="" value="<?= $this->message; ?>"></textarea>
                </div>
            </div>

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <div class="checkbox">
                        <label>
                            <input name="terms" type="checkbox"> <?= $this->translate("I accept the terms of use"); ?>
                        </label>
                    </div>
                </div>
            </div>

            <br />

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <button type="submit" class="btn btn-default"><?= $this->translate("Submit"); ?></button>
                </div>
            </div>
    </form>
<?php } else { ?>

    <h2><?= $this->translate("Thank you very much"); ?></h2>

    <p>
        We received the following information from you:

        <br />
        <br />

        <b>Firstname: </b> <?= $this->firstname; ?><br />
        <b>Lastname: </b> <?= $this->lastname; ?><br />
        <b>E-Mail: </b> <?= $this->email; ?><br />
    </p>
<?php } ?>
