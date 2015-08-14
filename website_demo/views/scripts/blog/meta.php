<i class="glyphicon glyphicon-calendar"></i> <?= $this->article->getDate()->get(Zend_Date::DATETIME_MEDIUM); ?>
&nbsp;
<i class="glyphicon glyphicon-list"></i>
<?php foreach ($this->article->getCategories() as $key => $category) { ?>
    <a href="?category=<?= $category->getId() ?>"><?= $category->getName() ?></a><?php
    if (($key+1) < count($this->article->getCategories())) {
        echo ",";
    }
    ?>
<?php } ?>

