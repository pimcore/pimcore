<section class="area-embed" style="margin-top:20px;">

    <div class="row">
        <?php for($i=1;$i<=2;$i++) { ?>
            <div class="col-sm-6">
                <?= $this->embed("socialContent_" . $i, ["width" => 426]) ?>
            </div>
        <?php } ?>
    </div>

</section>

