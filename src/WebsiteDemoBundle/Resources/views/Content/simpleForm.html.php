<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('WebsiteDemoBundle::layout.html.php');

?>

<?= $this->template('WebsiteDemoBundle:Includes:content-default.html.php') ?>

<?php if (!$this->success): ?>
    <form class="form-horizontal" role="form" action="" method="post">
        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate('Firstname'); ?></label>
            <div class="col-lg-10">
                <input name="firstname" type="text" class="form-control" placeholder="" value="<?= $this->firstname; ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate('Lastname'); ?></label>
            <div class="col-lg-10">
                <input name="lastname" type="text" class="form-control" placeholder="" value="<?= $this->lastname; ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label"><?= $this->translate('E-Mail'); ?></label>
            <div class="col-lg-10">
                <input name="email" type="text" class="form-control" placeholder="example@example.com" value="<?= $this->email; ?>">
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <div class="checkbox">
                    <label>
                        <input type="checkbox"> <?= $this->translate('Check me out'); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <button type="submit" class="btn btn-default"><?= $this->translate('Submit'); ?></button>
            </div>
        </div>
    </form>
<?php else: ?>

    <h2><?= $this->translate('Thank you very much'); ?></h2>

    <p>
        We received the following information from you:

        <br/>
        <br/>

        <b>Firstname: </b> <?= $this->firstname; ?><br/>
        <b>Lastname: </b> <?= $this->lastname; ?><br/>
        <b>E-Mail: </b> <?= $this->email; ?><br/>
    </p>
<?php endif; ?>
