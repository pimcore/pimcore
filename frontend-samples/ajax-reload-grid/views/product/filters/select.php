<div class="filter standard">
    <div class="select js_filterparent <?= $this->currentValue ? 'active' : '' ?>">
        <input class="js_optionvaluefield" type="hidden" name="<?= $this->fieldname ?>" value="<?= $this->currentValue ?>" />
        <div class="selection">
            <div class="head">
                <span class="arrow js_icon <?= $this->currentValue ? 'js_reset_filter' : '' ?>"></span>
                <span class="name"><?= $this->label ?></span>
            </div>
            <div class="actual">
                <span class="value"></span>
                <span class="text"><?= $this->translate($this->fieldname . ".filter." . $this->currentValue) ?></span>
            </div>
        </div>
        <div class="options">
            <ul>
                <?php foreach($this->values as $value) { ?>
                    <?php if(!empty($value['value'])) { ?>
                        <li><span class="option js_optionfilter_option" rel="<?= $value['value'] ?>"><?= $this->translate($this->fieldname . ".filter." . $value['value']) ?> ( <?= $value['count'] ?> )</span></li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>