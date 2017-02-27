

<li><a href="<?= $this->doc->getFullpath(); ?>"><?= $this->doc->getProperty("navigation_name") ?></a></li>


<?php if($this->doc->hasChilds()) { ?>
    <ul>
        <?php foreach ($this->doc->getChilds() as $child) { ?>
            <?php if(in_array($child->getType(), ["page","link"])) { ?>
                <?= $this->action("sitemapPartial", "advanced", "websitedemo", ["doc" => $child]) ?>
            <?php } ?>
        <?php } ?>
    </ul>
<?php } ?>


