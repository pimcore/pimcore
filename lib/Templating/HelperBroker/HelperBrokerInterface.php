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

namespace Pimcore\Templating\HelperBroker;

use Pimcore\Templating\PhpEngine;

/**
 * @deprecated
 */
interface HelperBrokerInterface
{
    /**
     * Determines if broker supports method
     *
     * @param PhpEngine $engine
     * @param string $method
     *
     * @return bool
     */
    public function supports(PhpEngine $engine, $method);

    /**
     * Runs helper
     *
     * @param PhpEngine $engine
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function helper(PhpEngine $engine, $method, array $arguments);
}
