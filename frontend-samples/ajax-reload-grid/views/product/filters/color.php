<div class="filter standard">
    <div class="select color js_filterparent <?= $this->currentValue ? 'active' : '' ?>">
        <input class="js_optionvaluefield" type="hidden" name="<?= $this->fieldname ?>" value="<?= $this->currentValue ?>" />
        <div class="selection">
            <div class="head">
                <span class="arrow js_icon <?= $this->currentValue ? 'js_reset_filter' : '' ?>"></span>
                <span class="name"><?= $this->label ?></span>
            </div>
            <div class="actual">
                <span class="value"><?= $this->currentValue ?></span>
                <span class="text"><?= $this->currentValue ?></span>
            </div>
        </div>
        <div class="options colors js_options">
            <?php foreach($this->values as $value) { ?>
                <span title="<?= $value['value'] ?>" rel="<?= $value['value'] ?>" class="option <?= $value['value'] ?> js_optionfilter_option">
                    <span class="value"><?= $value['value'] ?></span>
                    <span class="text"></span>
                </span>
            <?php }?>
        </div>
    </div>
</div>