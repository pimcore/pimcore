<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('WebsiteDemoBundle::layout.html.php');

?>

<?= $this->template('WebsiteDemoBundle:Includes:content-default.html.php') ?>

<hr />

<div class="row blog">
    <div class="col-md-8 list">
        <?php foreach ($this->articles as $article) { ?>
            <div class="media">
                <?php
                    $detailLink = $this->path("blog", [
                        "id"     => $article->getId(),
                        "text"   => $article->getTitle(),
                        "prefix" => $this->document->getFullPath()
                    ]);
                ?>

                <div class="media-body">
                    <h2 class="media-heading">
                        <a href="<?= $detailLink; ?>"><?= $article->getTitle(); ?></a>
                    </h2>

                    <?= $this->render("WebsiteDemoBundle:Blog:meta.html.php", ["article" => $article]); ?>

                    <hr />

                    <?php if($article->getPosterImage()) { ?>
                        <?= $article->getPosterImage()->getThumbnail("content")->getHTML() ?>
                        <br /><br />
                    <?php } ?>

                    <?= $article->getText(); ?>
                </div>
            </div>
        <?php } ?>


        <!-- pagination start -->
        <?= $this->render(
            "WebsiteDemoBundle:Includes:paging.html.php",
            get_object_vars($this->articles->getPages("Sliding")));
        ?>
        <!-- pagination end -->
    </div>
    <div class="col-md-4 filters">
        <div class="box">
        <h4><?= $this->translate("Categories") ?></h4>
            <ul class="nav nav-pills nav-stacked">
                <li <?php if(!$this->getParam("category")) { ?> class="active"<?php } ?>>
                    <a href="<?= $this->document ?>"><?= $this->translate("All Categories"); ?></a>
                </li>
                <?php foreach ($this->categories as $category) { ?>
                    <li <?php if($this->getParam("category") == $category->getId()) { ?> class="active"<?php } ?>>
                        <a href="?category=<?= $category->getId() ?>">
                            <?= $category->getName() ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>

        <div class="box">
            <h4><?= $this->translate("Archive"); ?></h4>
            <ul class="nav nav-pills nav-stacked">
                <li <?php if(!$this->getParam("archive")) { ?> class="active"<?php } ?>>
                    <a href="<?= $this->document ?>"><?= $this->translate("All Dates"); ?></a>
                </li>
                <?php foreach ($this->archiveRanges as $range) { ?>
                    <li <?php if($this->getParam("archive") == $range) { ?> class="active"<?php } ?>>
                        <a href="?archive=<?= $range ?>">
                            <?php
                                $time = strtotime($range . "-20"); // always use the 20th
                                echo strftime("%B %G", $time);
                            ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>
