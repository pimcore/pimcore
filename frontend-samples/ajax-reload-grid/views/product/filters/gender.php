<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


?>

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