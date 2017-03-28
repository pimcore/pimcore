<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<i class="glyphicon glyphicon-calendar"></i> <?= $this->article->getDate()->format("d/m/Y"); ?>&nbsp;
<i class="glyphicon glyphicon-list"></i>

<?php foreach ($this->article->getCategories() as $key => $category) { ?>
    <a href="?category=<?= $category->getId() ?>"><?= $category->getName() ?></a><?php
    if (($key+1) < count($this->article->getCategories())) {
        echo ",";
    }
    ?>
<?php } ?>
