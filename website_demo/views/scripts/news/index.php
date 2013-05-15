

<?php $this->template("/includes/content-headline.php"); ?>

<?php echo $this->areablock("content"); ?>

<?php foreach ($this->news as $news) { ?>
    <div class="media">
        <?php
            $detailLink = $this->url(array(
                "id" => $news->getId(),
                "text" => $news->getTitle(),
                "prefix" => $this->document->getFullPath()
            ), "news");
        ?>
        <?php if($news->getImage_1()) { ?>
            <a class="pull-left" href="<?php echo $detailLink; ?>">
                <img class="media-object" src="<?php echo $news->getImage_1()->getThumbnail("newsList"); ?>">
            </a>
        <?php } ?>

        <div class="media-body">
            <h4 class="media-heading">
                <a href="<?php echo $detailLink; ?>"><?php echo $news->getTitle(); ?></a>
                <small><?php echo $news->getDate()->get(Zend_Date::DATE_SHORT); ?></small>
            </h4>
            <?php echo $news->getShortText(); ?>
        </div>
    </div>
<?php } ?>


<!-- pagination start -->
<?php echo $this->paginationControl($this->news, 'Sliding', 'includes/paging.php', array(
   'urlprefix' => $this->document->getFullPath() . '?page=',
   'appendQueryString' => true
)); ?>
<!-- pagination end -->