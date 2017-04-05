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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


/**
 * @var \Pimcore\Templating\PhpEngine $this
 */

?>

<head>
    <link href="/bundles/pimcoreecommerceframework/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


<div class="alert alert-danger">
    <?php if (is_array($this->errors)) { ?>
        <?php foreach ($this->errors as $error) { ?>
            <?= $this->translateAdmin($error) ?>
        <?php } ?>
    <?php } ?>
</div>



</body>
