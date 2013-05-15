
<?php $this->template("/includes/content-headline.php"); ?>

<?php echo $this->areablock("content"); ?>



<div class="row-fluid">
    <div class="span6">
        <h2><?php echo $this->translate("Download compiled"); ?></h2>

        <p><?php echo $this->translate("Fastest way to get started: get the compiled and minified versions of our CSS, JS, and images. No docs or original source files."); ?></p>

        <p><a class="btn btn-large btn-primary" href="#"><?php echo $this->translate("Download"); ?></a></p>
    </div>
    <div class="span6">
        <?php /* placeholder example */ ?>
        <h3><?php echo $this->translate("Download Now (%s)", Zend_Date::now()->get(Zend_Date::DATE_MEDIUM)); ?></h3>
        <p><?php echo $this->translate("Get the original files for all CSS and JavaScript, along with a local copy of the docs by downloading the latest version directly from GitHub."); ?></p>

        <p><a class="btn btn-large" href="#"><?php echo $this->translate("Download"); ?></a></p>
    </div>
</div>

<?php echo $this->areablock("contentBottom"); ?>

