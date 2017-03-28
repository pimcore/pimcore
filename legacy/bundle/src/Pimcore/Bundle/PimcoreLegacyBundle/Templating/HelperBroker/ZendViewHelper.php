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

namespace Pimcore\Bundle\PimcoreLegacyBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreLegacyBundle\Zend\View\ViewHelperBridge;
use Pimcore\Templating\HelperBroker\HelperBrokerInterface;
use Pimcore\Templating\PhpEngine;

class ZendViewHelper implements HelperBrokerInterface
{
    /**
     * @var ViewHelperBridge
     */
    protected $viewHelperBridge;

    /**
     * @param ViewHelperBridge $zendViewHelperBridge
     */
    public function __construct(ViewHelperBridge $zendViewHelperBridge)
    {
        $this->viewHelperBridge = $zendViewHelperBridge;
    }

    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        return $this->viewHelperBridge->hasHelper($method);
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        return $this->viewHelperBridge->execute($method, $arguments);
    }
}
