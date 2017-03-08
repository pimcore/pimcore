<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('WebsiteDemoBundle::layout.html.php');

?>

<ul>
    <?= $this->action("sitemapPartial", "advanced", "websitedemo", ["doc" => $this->doc]) ?>
</ul>
