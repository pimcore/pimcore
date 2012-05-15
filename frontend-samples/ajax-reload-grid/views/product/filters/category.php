<div class="filter standard">
    <div class="select category js_filterparent <?= $this->currentValue ? 'active' : '' ?>">
        <input class="js_optionvaluefield" type="hidden" name="<?= $this->fieldname ?>" value="<?= $this->currentValue ?>" />
        <div class="selection">
            <div class="head">
                <span class="arrow js_icon <?= $this->currentValue ? 'js_reset_filter' : '' ?>"></span>
                <span class="name"><?= $this->label ?></span>
            </div>
            <div class="actual">
                <span class="value"></span>
                <?php
                    $current = "";
                    $currentCategory = Object_ProductCategory::getById($this->currentValue);
                    if($currentCategory) {
                        $current = $currentCategory->getName();
                    }
                ?>
                <span class="text js_curent_selection_text"><?= $current?></span>
            </div>
        </div>
        <div class="options js_options">
            <ul>
                <?php foreach($this->values as $value) { ?>
                    <?php $cat = Object_ProductCategory::getById($value['value']); ?>
                    <?php if($cat->isPublished()) { ?>
                        <li><span class="option js_optionfilter_option" rel="<?= $value['value'] ?>"><?= $cat->getName() ?>  ( <?= $value['count'] ?> ) </span></li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>