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

namespace Pimcore\Templating\Helper\Traits;

use Pimcore\Templating\PhpEngine;

/**
 * @deprecated
 */
trait TemplatingEngineAwareHelperTrait
{
    /**
     * @var PhpEngine
     */
    protected $templatingEngine;

    /**
     * @param PhpEngine $engine
     */
    public function setTemplatingEngine(PhpEngine $engine)
    {
        $this->templatingEngine = $engine;

        $this->setCharset($engine->getCharset());
    }
}
