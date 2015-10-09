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