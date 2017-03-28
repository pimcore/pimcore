<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<li><a href="<?= $this->doc->getFullpath(); ?>"><?= $this->doc->getProperty("navigation_name") ?></a></li>

<?php if ($this->doc->hasChilds()) { ?>
    <ul>
        <?php foreach ($this->doc->getChilds() as $child) { ?>
            <?php if (in_array($child->getType(), ["page", "link"])) { ?>
                <?= $this->render(':Advanced:sitemapPartial.html.php', ['doc' => $child]); ?>
            <?php } ?>
        <?php } ?>
    </ul>
<?php } ?>
