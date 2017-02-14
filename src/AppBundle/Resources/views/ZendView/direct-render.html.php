<?php
if ($this->_layout) {
    $this->extend($this->_layout);
}
?>

<h2>Zend Direct Render</h2>

<?php var_dump($this->document->getId()); ?>
<?php var_dump($this->getViewModel()->getAllParameters()); ?>
<?php var_dump($this); ?>
