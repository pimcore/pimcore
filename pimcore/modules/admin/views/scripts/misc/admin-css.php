/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
 /* THIS FILE IS GENERATED DYNAMICALLY BECAUSE OF DYNAMIC CSS CLASSES IN THE ADMIN */
 

<?php // custom views ?>

<?php if (is_array($this->customviews)) { ?>
    <?php foreach ($this->customviews as $cv) { ?>
    
    <?php if ($cv["icon"]) { ?>
    .pimcore_object_customviews_icon_<?php echo $cv["id"]; ?> {
        background: url(<?php echo $cv["icon"]; ?>) left center no-repeat !important;
    }
    <?php } ?> 
    
    <?php } ?>
<?php } ?>

