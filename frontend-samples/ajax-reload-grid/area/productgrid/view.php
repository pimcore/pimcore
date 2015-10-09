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


if($this->filterDefinitionObject && $this->filterDefinitionObject->getAjaxReload()) { ?>
    <?php $this->inlineScript()->appendFile("/static/js/lib/jquery.form.js"); ?>
    <?php $this->inlineScript()->appendFile("/static/js/lib/jquery.address-1.4.min.js"); ?>
    <?php $this->inlineScript()->appendFile("/static/js/gridfilters_ajax.js"); ?>
<?php } else { ?>
    <?php $this->inlineScript()->appendFile("/static/js/gridfilters.js"); ?>
<?php } ?>




<?php if ($this->editmode) { ?>
    <div>
        <h2>ProductFilter Object</h2>

        <div>
            <?php echo $this->href('productFilter', array('types' => array('object'), 'subtypes' => array('object' => array('object')), 'classes' => array('FilterDefinition'))); ?>
        </div>
    </div>

<?php } ?>

<?php if (!$this->href("productFilter")->isEmpty()) { ?>

    <?= $this->action("grid", "ajax", null, array("filterdefinition" => $this->filterDefinitionObject)) ?>

<?php } ?>