<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<?php
// automatically use the headline as title
$this->headTitle($this->input('headline')->getData());
?>

<div class="page-header">
    <h1><?= $this->input('headline') ?></h1>
</div>
