

<?php
$this->extend('WebsiteDemoBundle::layout.html.php')
?>

<ul>

    <?= $this->action("sitemapPartial", "advanced", "websitedemo", ["doc" => $this->doc]) ?>


</ul>
