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