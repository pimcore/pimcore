<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?= $this->translate("Recently in the Blog") ?></h3>
    </div>
    <div class="panel-body">
        <div class="list-group">
            <?php foreach ($this->articles as $article) { ?>
                <?php
                    $detailLink = $this->url([
                        "id" => $article->getId(),
                        "text" => $article->getTitle(),
                        "prefix" => $this->document->getProperty("blog")->getFullPath()
                    ], "blog", true);
                ?>
                <a href="<?= $detailLink ?>" class="list-group-item">
                    <h5 class="list-group-item-heading"><?= $article->getTitle(); ?></h5>
                    <p class="list-group-item-text">
                        <i class="glyphicon glyphicon-calendar"></i> <?= $article->getDate()->get(Zend_Date::DATETIME_MEDIUM); ?>
                    </p>
                </a>
            <?php } ?>
        </div>
    </div>
</div>
