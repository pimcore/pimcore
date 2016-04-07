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
                    $currentCategory = \Pimcore\Model\Object\ProductCategory::getById($this->currentValue);
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
                    <?php $cat = \Pimcore\Model\Object\ProductCategory::getById($value['value']); ?>
                    <?php if($cat->isPublished()) { ?>
                        <li><span class="option js_optionfilter_option" rel="<?= $value['value'] ?>"><?= $cat->getName() ?>  ( <?= $value['count'] ?> ) </span></li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>