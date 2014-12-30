<?php $this->layout();  // enable the layout engine, it's not necessary to call ->setLayout() if we want to use the default layout ("layout.php") ?>

<?php $this->template("/includes/content-headline.php"); ?>

<?= $this->areablock("content"); ?>

