<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<section class="area-text-accordion">

    <?php
        $id = "accordion-" . uniqid();
    ?>
    <div class="panel-group" id="<?= $id ?>">
        <?php while($this->block("accordion")->loop()) { ?>
            <?php
                $entryId = $id . "-" . $this->block("accordion")->getCurrent();
            ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#<?= $id ?>" href="#<?= $entryId ?>">
                            <?= $this->input("headline") ?>
                        </a>
                    </h4>
                </div>
                <div id="<?= $this->editmode ? "" : $entryId ?>" class="panel-collapse collapse <?= ($this->editmode || $this->block("accordion")->getCurrent() == 0) ? "in" : "" ?>">
                    <div class="panel-body">
                        <?= $this->wysiwyg("text") ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>


</section>

