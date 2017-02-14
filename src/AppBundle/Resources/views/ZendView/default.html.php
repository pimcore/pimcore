<?php
if ($this->_layout) {
    $this->extend($this->_layout);
}
?>

<h2>Default</h2>

<p><code><?= $this->fooBar() ?></code></p>

<?php var_dump($this->document->getId()); ?>
<?php var_dump($this->getViewModel()->getAllParameters()); ?>
<?php var_dump($this); ?>
