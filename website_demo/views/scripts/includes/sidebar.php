<div class="teasers">
    <?php while($this->block("teasers")->loop()) { ?>
        <?= $this->snippet("teaser"); ?>
    <?php } ?>
</div>
