
<?php if($this->initial) { ?>

<?php $this->template("/content/default.php"); ?>

<ul>
    <?php } ?>

    <li><a href="<?= $this->doc->getFullpath(); ?>"><?= $this->doc->getProperty("navigation_name") ?></a></li>


    <?php if($this->doc->hasChilds()) { ?>
        <ul>
            <?php foreach ($this->doc->getChilds() as $child) { ?>
                <?php if(in_array($child->getType(), ["page","link"])) { ?>
                    <?= $this->action("sitemap", "advanced", null, ["doc" => $child]) ?>
                <?php } ?>
            <?php } ?>
        </ul>
    <?php } ?>


    <?php if($this->initial) { ?>
</ul>
<?php } ?>