<?php if($this->editmode || !$this->input("headline")->isEmpty()) { ?>
    <div class="page-header">
        <h2><?php echo $this->input("headline"); ?></h2>
    </div>
<?php } ?>

<?php if($this->editmode || !$this->wysiwyg("lead")->isEmpty()) { ?>
    <div class="lead">
        <?php echo $this->wysiwyg("lead", array("height" => 100)); ?>
    </div>
<?php } ?>

