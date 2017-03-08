<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<section class="area-embed" style="margin-top:20px;">

    <div class="row">
        <?php for($i=1;$i<=2;$i++) { ?>
            <div class="col-sm-6">
                <?php while($this->block("contents_".$i)->loop()) { ?>
                    <div class="embed">
                        <?= $this->embed("socialContent", ["width" => 426, "height" => 300]) ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

</section>

