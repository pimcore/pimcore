
<?php if(!$this->getParam("q")) { ?>
    <?php $this->template("/content/default.php"); ?>
<?php } else { ?>
    <?php $this->template("/includes/content-headline.php"); ?>
<?php } ?>

<div>

    <form class="form-inline" role="form">
        <div class="form-group">
            <input type="text" name="q" class="form-control" placeholder="<?php echo $this->translate("Keyword"); ?>">
        </div>
        <button type="submit" name="submit" class="btn btn-default"><?php echo $this->translate("Search"); ?></button>
    </form>

    <?php if ($this->paginator) { ?>

        <?php $facets = $this->result->getFacets(); ?>
        <?php if(!empty($facets)) { ?>
            <div class="row" style="margin-top: 20px">
                Facets:
                <?php foreach ($facets as $label => $anchor) { ?>
                    <a class="btn btn-default btn-xs" href="<?= $this->url(array('facet' => $label, "page" => null)); ?>"><?= $anchor ?></a>
                <?php } ?>
            </div>
        <?php } ?>

        <?php foreach ($this->paginator as $item) { ?>
            <!-- see class Pimcore_Google_Cse_Item for all possible properties -->
            <div class="media <?php echo $item->getType(); ?>">
                <?php if($item->getImage()) { ?>
                    <!-- if an image is present this can be simply a string or an internal asset object -->

                    <?php if($item->getImage() instanceof Asset) { ?>
                        <a class="pull-left" href="<?php echo $item->getLink() ?>">
                            <img class="media-object" src="<?php echo $item->getImage()->getThumbnail("newsList"); ?>">
                        </a>
                    <?php } else { ?>
                        <a class="pull-left" href="<?php echo $item->getLink() ?>">
                            <img width="64" src="<?php echo $item->getImage() ?>" />
                        </a>
                    <?php } ?>
                <?php } ?>


                <div class="media-body">
                    <h4 class="media-heading">
                        <a href="<?= $item->getLink() ?>">
                            <!-- if there's a document set for this result use the original title without suffixes ... -->
                            <!-- the same can be done with the description and every other element relating to the document -->
                            <?php if($item->getDocument() && $item->getDocument()->getTitle()) { ?>
                                <?= $item->getDocument()->getTitle() ?>
                            <?php } else { ?>
                                <?= $item->getTitle() ?>
                            <?php } ?>
                        </a>
                    </h4>
                    <?= $item->getHtmlSnippet() ?>
                    <br />
                    <small><?= $item->getHtmlFormattedUrl(); ?></small>
                </div>
            </div>
        <?php } ?>
        <?= $this->paginationControl($this->paginator, "Sliding", "includes/paging.php"); ?>
    <?php } else if ($this->getParam("q")) { ?>
        <div>
            Sorry, something seems to went wrong ...
        </div>
    <?php } else { ?>
        <div>
            Type your keyword and press search
        </div>
    <?php } ?>
</div>


