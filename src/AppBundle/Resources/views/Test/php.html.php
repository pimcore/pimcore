<?php
/** @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this */
/** @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view */
$view->extend('AppBundle::test-layout.html.php');
?>

<?php $view['slots']->start('content') ?>

    <h1>CONTENT</h1>
    fooblah

    <hr>

    <?php echo $view->render('AppBundle:Test:partial.html.php', ['index' => 0, 'document' => $document]) ?>

    <hr>

    <?php var_dump($document ? get_class($document) : null); ?>
    <?php var_dump($this->document ? get_class($this->document) : null); ?>
    <?php var_dump($document === $this->document) ?>

    <?php // echo $this->foo() ?>

    <?php /*
    <?php var_dump($document) ?>
    <?php var_dump($editmode) ?>
    <?php var_dump($this->foo) ?>
    <?php var_dump($this->document) ?>
    <?php var_dump($this->editmode) ?>
    */ ?>

    <hr>
    <?= $view->input('foobar') ?>
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
