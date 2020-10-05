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
class DocumentMethod implements HelperBrokerInterface
{
    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        $document = $engine->getViewParameter('document');
        if (null !== $document && method_exists($document, $method)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        $document = $engine->getViewParameter('document');

        return call_user_func_array([$document, $method], $arguments);
    }
}
