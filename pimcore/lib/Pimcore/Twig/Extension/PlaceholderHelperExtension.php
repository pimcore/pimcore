<?php

declare(strict_types=1);

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

namespace Pimcore\Twig\Extension;

use function foo\func;
use Pimcore\Templating\PhpEngine;
use Symfony\Component\Templating\Helper\HelperInterface;

class PlaceholderHelperExtension extends \Twig_Extension
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

    public function getFunctions()
    {
        $helperNames = [
            'headLink'     => 'pimcore_head_link',
            'headMeta'     => 'pimcore_head_meta',
            'headScript'   => 'pimcore_head_script',
            'headStyle'    => 'pimcore_head_style',
            'headTitle'    => 'pimcore_head_title',
            'inlineScript' => 'pimcore_inline_script',
            'placeholder'  => 'pimcore_placeholder'
        ];

        $functions = [];
        foreach ($helperNames as $helperName => $functionName) {
            $callable = function () use ($helperName) {
                return $this->callHelper($helperName, func_get_args());
            };

            $functions[] = new \Twig_SimpleFunction($functionName, $callable, [
                'is_safe' => ['html']
            ]);
        }

        return $functions;
    }

    /**
     * Calls a helper with arguments
     *
     * @param string $helperName
     * @param array $arguments
     *
     * @return mixed|HelperInterface
     */
    public function callHelper(string $helperName, array $arguments = [])
    {
        $helper = $this->phpEngine->get($helperName);

        // helper implements __invoke -> run it directly
        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }
}
