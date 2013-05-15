
<?php $this->template("/content/default.php"); ?>


<?php if($this->documents->getTotalCount() > 0) { ?>
    <h3><?php echo $this->translate("Total: %s", $this->documents->getTotalCount()); ?></h3>
    <ul>
        <?php foreach($this->documents as $doc) { ?>
            <li><a href="<?php echo $doc; ?>"><?php echo $doc->getProperty("navigation_name"); ?></a></li>
        <?php } ?>
    </ul>
<?php } ?>

