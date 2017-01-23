<?php
/** @var \Symfony\Component\Templating\PhpEngine $view */
$view->extend('AppBundle::test-layout.html.php');
?>

<?php $view['slots']->start('content') ?>

    <h1>CONTENT</h1>
    fooblah

    <hr>
    <?= $view['pimcore_tag']->render('input', 'foobar') ?>
    <hr>
    <?= $view['pimcore_tag']->render('wysiwyg', 'wysiwyg') ?>
    <hr>

    DEVICE: <?= $view['zend']->render('device') ?><br>
    IS DESKTOP: <?= $view['zend']->render('device')->isDesktop() ? 'YES' : 'NO' ?><br>

    <br>
    URL: <?= $view['zend']->render('url', [
            'id'     => 4,
            'text'   => 'In enim justo',
            'prefix' => '/en/basic-examples/news'
        ], 'news', true); ?>

<?php $view['slots']->stop() ?>
