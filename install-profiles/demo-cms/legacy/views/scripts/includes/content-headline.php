
<?php
    // automatically use the headline as title
    $this->headTitle($this->input("headline")->getData());
?>

<div class="page-header">
    <h1><?= $this->input("headline"); ?></h1>
</div>

