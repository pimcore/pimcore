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

class TemplatingHelper implements HelperBrokerInterface
{
    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        if ($engine->has($method)) {
            return true;
        }

        return false;
    }

    /**
     * Run or return a native view helper
     *
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        $helper = $engine->get($method);

        // helper implements __invoke -> run it directly
        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }
}
