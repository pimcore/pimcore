<?php if($this->editmode) { // styles only for editmode ?>
    <link rel="stylesheet" href="/website/static/css/global.css">
<?php } ?>

<!-- FOOTER -->
<footer>
    <p class="pull-right"><a href="#"><?php echo $this->translate("Back to top"); ?></a></p>
    <p class="links">&copy; <?php echo date("Y"); ?> pimcore GmbH &middot;
        <?php while($this->block("links")->loop()) { ?>
            <?php echo $this->link("link"); ?>
        <?php } ?>
    </p>
</footer>