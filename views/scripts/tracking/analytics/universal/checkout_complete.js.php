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

ga('require', 'ecommerce', 'ecommerce.js');

<?php foreach ($this->calls as $call => $callData): ?>
    <?php foreach ($callData as $cd): ?>
        ga('<?= $call ?>', <?= json_encode($cd); ?>);
    <?php endforeach; ?>
<?php endforeach; ?>

ga('ecommerce:send');