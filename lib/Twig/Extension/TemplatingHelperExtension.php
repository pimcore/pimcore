<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Templating\PhpEngine;
use Symfony\Component\Templating\Helper\HelperInterface;
use Twig\Extension\AbstractExtension;

/**
 * @deprecated use \Pimcore\Twig\Extension\HeaderExtension instead.
 * @deprecated use \Pimcore\Twig\Extension\PimcoreToolExtension instead.
 * @deprecated use \Pimcore\Twig\Extension\HelpersExtension instead.
 * @deprecated use \Pimcore\Twig\Extension\CacheExtension instead.
 */
class TemplatingHelperExtension extends AbstractExtension
{
    /**
     * @var PhpEngine
     */
    private $phpEngine;

    /**
     * @param PhpEngine $phpEngine
     */
    public function __construct(PhpEngine $phpEngine)
    {
        $this->phpEngine = $phpEngine;
    }

    /**
     * Calls a helper with arguments
     *
     * @param string $helperName
     * @param array $arguments
     *
     * @return mixed|HelperInterface
     *
     * @deprecated
     */
    public function callHelper(string $helperName, array $arguments = [])
    {
        @trigger_error(
            sprintf(
                'Class "%s" is deprecated since v6.9 and will be removed in Pimcore 10. Use one of these "%s", "%s", "%s", "%s" instead.',
                TemplatingHelperExtension::class,
                HeaderExtension::class,
                PimcoreToolExtension::class,
                HelpersExtension::class,
                CacheExtension::class
            ),
            E_USER_DEPRECATED
        );

        $helper = $this->phpEngine->get($helperName);

        // helper implements __invoke -> run it directly
        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }
}
