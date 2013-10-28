<div class="teasers">
    <?php while($this->block("teasers")->loop()) { ?>
        <?php echo $this->snippet("teaser"); ?>
    <?php } ?>
</div>
