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
 * @package    Asset
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace {
// this is just an alias ;-)
    class_alias("Pimcore\\Model\\Object\\AbstractObject", "Pimcore\\Model\\Object");
}


// the following is for IDEs to support auto-complete

namespace Pimcore\Model {
    if (!\Pimcore\Tool::classExists("Pimcore\\Model\\Object")) {
        class Object extends \Pimcore\Model\Object\AbstractObject
        {
        }
    }
}
