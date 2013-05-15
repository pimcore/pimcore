
<section class="area-wysiwyg">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <?php $this->glossary()->start(); ?>
        <?php echo $this->wysiwyg("content"); ?>
    <?php $this->glossary()->stop(); ?>

</section>