<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<?= $this->template('Includes/content-headline.html.php'); ?>
<?= $this->areablock('content'); ?>
