<div class="filter gender">
    <div class="icons js_filterparent">
        <input class="js_optionvaluefield" type="hidden" name="<?= $this->fieldname ?>" value="<?= $this->currentValue ?>" />

        <div class="adults">
            <div title="<?= $this->translate('filters.gender.women');?>" rel="w" class="icon female showtooltip <?= $this->currentValue == 'w' ? 'active' : '' ?> js_genderfilter_option"></div>
            <div title="<?= $this->translate('filters.gender.men');?>" rel="m" class="icon male showtooltip <?= $this->currentValue == 'm' ? 'active' : '' ?> js_genderfilter_option"></div>
            <div title="<?= $this->translate('filters.gender.adults');?>" class="icon man"></div>
        </div>
        <div class="youths">
            <div title="<?= $this->translate('filters.gender.youth');?>" class="icon man"></div>
            <div title="<?= $this->translate('filters.gender.girls');?>" rel="g" class="icon female showtooltip <?= $this->currentValue == 'g' ? 'active' : '' ?> js_genderfilter_option"></div>
            <div title="<?= $this->translate('filters.gender.boys');?>" rel="b" class="icon male showtooltip <?= $this->currentValue == 'b' ? 'active' : '' ?> js_genderfilter_option"></div>
        </div>
    </div>
</div>