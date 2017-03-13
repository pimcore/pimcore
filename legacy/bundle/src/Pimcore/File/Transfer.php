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

namespace Pimcore\File;

class Transfer extends \Zend_File_Transfer
{

    /**
     * @param string $adapter
     * @param bool $direction
     * @param array $options
     */
    public function __construct($adapter = "\\Pimcore\\File\\Transfer\\Adapter\\Http", $direction = false, $options = [])
    {
        parent::__construct($adapter, $direction, $options);
    }
}
