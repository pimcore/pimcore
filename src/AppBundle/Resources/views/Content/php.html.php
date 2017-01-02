<?php
/** @var \Symfony\Component\Templating\PhpEngine $view */
$view->extend('AppBundle::layout.html.php');
?>

<?php $view['slots']->start('content') ?>

    <h1>CONTENT</h1>
    fooblah

    <?php // echo $view['pimcore_tag']->render('input', 'foobar') ?>

    DEVICE: <?= $view['zend']->render('device') ?><br>
    IS DESKTOP: <?= $view['zend']->render('device')->isDesktop() ? 'YES' : 'NO' ?><br>

<?php $view['slots']->stop() ?>
