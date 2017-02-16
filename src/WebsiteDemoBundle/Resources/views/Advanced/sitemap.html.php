<?php if($this->initial) { ?>
    <?php
    // TODO a template can either extend a template or not (not conditionally on the sub-request)
    // move the list rendering to a partial which is called from here
    // $this->extend('WebsiteDemoBundle::layout.html.php')
    ?>

<ul>
    <?php } ?>

    <li><a href="<?= $this->doc->getFullpath(); ?>"><?= $this->doc->getProperty("navigation_name") ?></a></li>


    <?php if($this->doc->hasChilds()) { ?>
        <ul>
            <?php foreach ($this->doc->getChilds() as $child) { ?>
                <?php if(in_array($child->getType(), ["page","link"])) { ?>
                    <?= $this->action("sitemap", "advanced", "websitedemo", ["doc" => $child]) ?>
                <?php } ?>
            <?php } ?>
        </ul>
    <?php } ?>


    <?php if($this->initial) { ?>
</ul>
<?php } ?>
