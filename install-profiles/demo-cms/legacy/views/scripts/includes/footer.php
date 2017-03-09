<?php if($this->editmode) { // styles only for editmode ?>
    <link rel="stylesheet" href="/website/static/css/global.css">
<?php } ?>

<!-- FOOTER -->
<footer>
    <p class="pull-right"><a href="#"><?= $this->translate("Back to top"); ?></a></p>
    <p class="links">&copy; <?= date("Y"); ?> pimcore GmbH &middot;
        <?php while($this->block("links")->loop()) { ?>
            <?= $this->link("link"); ?>
        <?php } ?>
    </p>
</footer>