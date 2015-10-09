<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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