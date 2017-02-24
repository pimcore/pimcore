<?php
$renderPartial = true;

$regKey = 'tpl_iteration';

$iteration = 0;
if (\Pimcore\Cache\Runtime::isRegistered($regKey)) {
    $iteration = \Pimcore\Cache\Runtime::get($regKey);
}

$outputIteration = $iteration;

if ($iteration >= 3) {
    $renderPartial = false;
}

$iteration++;
\Pimcore\Cache\Runtime::set($regKey, $iteration);
?>

<?php var_dump($document) ?>

<ul>
    <li>
        <div>
            Ix: <code><?= $index ?></code>
            Vx: <code><?= $view->index ?></code>
            It: <code><?= $outputIteration ?></code>
            <code><?= ($index === $this->index) ? 'true' : 'false' ?></code>
        </div>

        <?php if ($renderPartial): ?>
            <?php echo $view->render('AppBundle:Test:partial.html.php', ['index' => $index + 1]) ?>
        <?php endif; ?>

        <div>
            Ix: <code><?= $index ?></code>
            Vx: <code><?= $view->index ?></code>
            It: <code><?= $outputIteration ?></code>
            <code><?= ($index === $this->index) ? 'true' : 'false' ?></code>
        </div>
    </li>
</ul>
