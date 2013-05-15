<?php if($this->editmode) { // styles only for editmode ?>
    <link rel="stylesheet" href="/website/static/css/global.css">
<?php } ?>

<footer class="footer">
    <div class="container">
        <?php echo $this->wysiwyg("text"); ?>
        <ul class="footer-links">
            <?php
                // put the block element into a variable to reuse it also inside the block
                $block = $this->block("links");
                while ($block->loop()) { ?>
                <li>
                    <?php echo $this->link("link"); ?>
                </li>
                <?php
                    // insert the seperator only between the elements, not at the end
                    if(!$this->editmode && $block->getCurrent() < ($block->getCount()-1)) { ?>
                    <li class="muted">&middot;</li>
                <?php } ?>
            <?php } ?>
        </ul>
    </div>
</footer>
