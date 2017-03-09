<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');

?>

<?= $this->template('Includes/content-default.html.php') ?>

<?php if(!$this->success) { ?>
    <form class="form-horizontal" role="form" action="" method="post">
        <div class="row">
            <div class="col-md-9">
                <div class="form-group">
                    <label class="col-lg-2 control-label"><?= $this->translate("Gender"); ?></label>
                    <div class="col-lg-10">
                        <select name="gender" class="form-control">
                            <option value="male"<?php if($this->gender == "male") { ?> selected="selected" <?php } ?>>Male</option>
                            <option value="female"<?php if($this->gender == "female") { ?> selected="selected" <?php } ?>>Female</option>
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
            </div>
            <div class="col-md-3">
                <h4><?= $this->translate("Click one of the following logos to auto-fill the form with your data") ?>.</h4>
                <a href="?provider=Facebook"><img style="max-width: 20%;" src="/static/img/social-icons/facebook.png"></a>
                <a href="?provider=Twitter"><img style="max-width: 20%;" src="/static/img/social-icons/twitter.png"></a>
                <a href="?provider=Google"><img style="max-width: 20%;" src="/static/img/social-icons/google.png"></a>
                <a href="?provider=Google"><img style="max-width: 20%;" src="/static/img/social-icons/youtube.png"></a>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="form-group">
                <h2><?= $this->translate("Message"); ?></h2>
                <textarea name="message" type="text" style="height: 300px" class="form-control" placeholder="" value="<?= $this->message; ?>"></textarea>
            </div>
        </div>

        <br />

        <div class="col-lg-12 form-group">
            <button type="submit" class="btn btn-default"><?= $this->translate("Submit"); ?></button>
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
