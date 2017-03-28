<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');
?>

<ul>
    <?= $this->render(':Advanced:sitemapPartial.html.php', ['doc' => $this->doc]); ?>
</ul>
