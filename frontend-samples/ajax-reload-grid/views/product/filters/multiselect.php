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

<div class="filter">
    <div class="filter_headline">
        <div class="top"></div>
        <div class="middle">
            <div class="txt"><span class="label"><?= $this->label ?></span> <a href="#" class="selected"><?= implode(', ', $this->currentValue) ?></a></div>
        </div>
        <div class="bottom"></div>
    </div>
    <div class="list-available">
        <ul class="list-available-items">
            <?php
            foreach($values as $value):
                $id = md5($this->fieldname.$value['value']);
                ?>
                <li>
                    <input type="checkbox" name="<?= $this->fieldname ?>[]" value="<?= $value['value'] ?>" <?= in_array($value['value'], $this->currentValue) ? 'checked="checked"' : '' ?> id="filter<?= $id ?>" />
                    <label for="filter<?= $id ?>"><?= $value['value'] ?> (<?= $value['count'] ?>)</label>
                </li>
            <?php endforeach; ?>
        </ul>

        <p><span class="btn" style="float: right;"><a href="#" class="apply">Übernehmen</a></span></p>
    </div>
</div>