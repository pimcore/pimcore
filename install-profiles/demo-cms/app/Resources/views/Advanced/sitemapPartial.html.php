<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

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


