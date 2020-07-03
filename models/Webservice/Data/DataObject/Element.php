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
 * @package    Webservice
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data\DataObject;

use Pimcore\Model;

/**
 * @deprecated
 */
class Element extends Model\Webservice\Data
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var object[]
     */
    public $value;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $language;
}
