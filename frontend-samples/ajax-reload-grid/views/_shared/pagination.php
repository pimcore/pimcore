<?php if($this->pageCount > 1) { ?>
    <div class="paginationCol">
        <?php if (isset($this->previous)) { ?>
            <a href="<?= OnlineShop_Framework_FilterService_Helper::createPagingQuerystring($this->previous) ?>"
               class="pageLeft"
            >
                back
            </a>
        <?php } ?>

        <?php foreach ($this->pagesInRange as $page) { ?>
            <?php if($this->current == $page) { ?>
                <span><?= $page ?></span>
            <?php } else { ?>
                <a href="<?= OnlineShop_Framework_FilterService_Helper::createPagingQuerystring($page) ?>">
                    <?= $page ?>
                </a>
            <?php } ?>

        <?php } ?>

        <?php if (isset($this->next)) { ?>
            <a href="<?= OnlineShop_Framework_FilterService_Helper::createPagingQuerystring($this->next) ?>"
               class="pageRight"
            >
                next
            </a>
        <?php } ?>
    </div>
<?php } ?>