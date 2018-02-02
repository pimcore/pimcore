<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<header class="jumbotron subhead">
    <div class="container">
        <h2><?= $this->input('headTitle'); ?></h2>
        <p class="lead"><?= $this->input('headDescription'); ?></p>
    </div>
</header>

<?php
$color = $document->getProperty("headerColor");
if ($color) : // orange is the default color

    $colorMapping = [
        "blue"  => ["#258dc1", "#2aabeb"],
        "green" => ["#278415", "#1a9f00"]
    ];
    $c            = $colorMapping[$color];
    ?>
    <style>
        .jumbotron {
            background: <?= $c[1]; ?>; /* Old browsers */
            background: -moz-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* FF3.6+ */
            background: -webkit-gradient(linear, left bottom, right top, color-stop(0%, <?= $c[0]; ?>), color-stop(100%, <?= $c[1]; ?>)); /* Chrome,Safari4+ */
            background: -webkit-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* Chrome10+,Safari5.1+ */
            background: -o-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* Opera 11.10+ */
            background: -ms-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* IE10+ */
            background: linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* W3C */
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='<?= $c[0]; ?>', endColorstr='<?= $c[1]; ?>', GradientType=1); /* IE6-9 fallback on horizontal gradient */
        }
    </style>
<?php endif; ?>
