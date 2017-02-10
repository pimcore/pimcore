<?php
$renderPartial = true;

$regKey = 'tpl_iteration';

$iteration = 0;
if (Zend_Registry::isRegistered($regKey)) {
    $iteration = Zend_Registry::get($regKey);
}

$outputIteration = $iteration;

if ($iteration >= 3) {
    $renderPartial = false;
}

$iteration++;
Zend_Registry::set($regKey, $iteration);
?>

<ul>
    <li>
        <div>
            Ix: <code><?= $index ?></code>
            Vx: <code><?= $view->index ?></code>
            It: <code><?= $outputIteration ?></code>
        </div>

        <?php if ($renderPartial): ?>
            <?php echo $view->render('AppBundle:Test:partial.html.php', ['index' => $index + 1]) ?>
        <?php endif; ?>

        <div>
            Ix: <code><?= $index ?></code>
            Vx: <code><?= $view->index ?></code>
            It: <code><?= $outputIteration ?></code>
        </div>
    </li>
</ul>
