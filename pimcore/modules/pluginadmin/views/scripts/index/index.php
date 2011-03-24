<?php
    $this->layout()->setLayout('install');
?>

<?php $translate = Zend_Registry::get("translate"); ?>

<h1><?php echo $translate->_("pimcore_setup");  ?></h1>

<?php if (is_array($this->errorMessages)) { ?>
<div class="error">
<?php foreach ($this->errorMessages as $message) { ?>
<?php if (!empty($message)) { ?>
<?php echo $translate->_($message); ?><br/>
<?php } ?>
<?php } ?>
</div>
<?php } ?>

<?php if (is_array($this->warnings)) { ?>
<div class="warning">
<?php foreach ($this->warnings as $message) { ?>
<?php if (!empty($message)) { ?>
<?php echo $translate->_($message); ?><br/>
<?php } ?>
<?php } ?>
</div>
<?php } ?>

<?php if ($this->allowSetup and $this->readyForSetup) { ?>
<?php echo $this->installForm; ?>
<?php } else { ?>
<?php if (!$this->allowSetup) {
    echo $translate->_("pimcore_setup_not_allowed");
} ?><br/>
<?php if ($this->allowSetup and !$this->readyForSetup) {
    echo $translate->_("pimcore_setup_not_ready");
} ?><br/>
<a href="/admin/"><?php echo $translate->_("login");  ?></a>
<?php } ?>


