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