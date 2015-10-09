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
    <div   class="select rating js_filterparent <?= $this->currentValue ? 'active' : '' ?>">
        <input class="js_optionvaluefield" type="hidden" name="<?= $this->fieldname ?>" value="<?= $this->currentValue ?>" />
        <div class="selection">
            <div class="head">
                <span class="arrow <?= $this->currentValue ? 'js_reset_filter' : '' ?>"></span>
                <span class="name"><?= $this->label ?></span>
            </div>
            <div class="actual">
                <span class="value"></span>
                <span class="text">
                    <span class="starrating">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php if ($this->currentValue <= $i): ?>
                                <!--<span class="star empty"></span>-->
                            <?php else: ?>
                                <span class="star"></span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </span>


                </span>
            </div>
        </div>
        <div class="options">
            <ul>
                <?php foreach($this->values as $rating): ?>
                    <?php $rating = $rating['value'] ?>
                    <?php if($rating > 2) : ?>
                        <li>
                            <span class="option js_optionfilter_option" rel="<?= $rating ?>">
                                <span class="starrating">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?php if ($rating <= $i): ?>
                                            <span class="star empty"></span>
                                        <?php else: ?>
                                            <span class="star"></span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </span>&nbsp;
                            </span>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>