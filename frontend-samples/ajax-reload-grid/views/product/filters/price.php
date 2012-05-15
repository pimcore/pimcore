<div class="filter standard">
    <div class="select price js_filterparent <?= $this->currentValue ? 'active' : '' ?>">
        <input class="js_optionvaluefield" type="hidden" name="<?= $this->fieldname ?>" value="<?= $this->currentValue ?>" />
        <div class="selection">
            <div class="head">
                <span class="arrow js_icon <?= $this->currentValue ? 'js_reset_filter' : '' ?>"></span>
                <span class="name"><?= $this->label ?></span>
            </div>
            <div class="actual">
                <span class="value"></span>
                <?php if(is_array($this->currentValue)) { ?>
                    <span class="text js_curent_selection_text"><?= $this->translate("EUR ") . $this->currentValue ?></span>
                <?php } else { ?>
                    <span class="text js_curent_selection_text"><?= $this->currentValue ?></span>
                <?php } ?>
            </div>
        </div>
        <div class="options">
            <ul>
                <?php foreach($this->values as $value) { ?>
                    <li><span class="option js_optionfilter_option" rel="<?= $value['from'] . "-" . $value['to'] ?>" ><?= $value['label'] ?></span></li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>