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

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model\Webservice\Data\Document;

/**
 * @deprecated
 */
class Printcontainer extends Document\PageSnippet
{
    /**
     * @var int
     */
    public $lastGenerated;

    /**
     * @var bool
     */
    public $inProgress;

    /**
     * @var string
     */
    public $css;

    /**
     * @var string
     */
    public $lastGenerateMessage;
}
