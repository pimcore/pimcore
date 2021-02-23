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

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\PrintConfigEvent;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;

/**
 * @deprecated since version 6.9.0 and will be removed in 10.0.0. use PdfReactor instead.
 */
class PdfReactor8 extends PdfReactor
{
    public function __construct()
    {
        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.9.0 and will be removed in 10.0.0. Use ' . PdfReactor::class . ' instead.',
            E_USER_DEPRECATED
        );
    }
}
