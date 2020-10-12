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

use Pimcore\Templating\PhpEngine;
use Symfony\Component\Templating\Helper\HelperInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Delegates calls to PHP templating helpers. Use this only with templating helpers which do not rely
 * on PHP rendering!
 *
 * @deprecated
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

    public function getFunctions(): array
    {
        $helperNames = [
            'headLink' => 'pimcore_head_link',
            'headMeta' => 'pimcore_head_meta',
            'headScript' => 'pimcore_head_script',
            'headStyle' => 'pimcore_head_style',
            'headTitle' => 'pimcore_head_title',
            'inlineScript' => 'pimcore_inline_script',
            'breachAttackRandomContent' => 'pimcore_breach_attack_random_content',
            'placeholder' => 'pimcore_placeholder',
            'cache' => 'pimcore_cache',
            'device' => 'pimcore_device',
            'pimcoreUrl' => [
                'name' => 'pimcore_url',
                'is_safe' => null,
            ],
        ];

        $functions = [];
        foreach ($helperNames as $helperName => $helperOptions) {
            $functionName = null;
            $options = [
                'is_safe' => ['html'],
            ];

            if (is_string($helperOptions)) {
                $functionName = $helperOptions;
            } else {
                if (!isset($helperOptions['name'])) {
                    throw new \LogicException('A helper declaration needs to define a Twig function name');
                }

                $functionName = $helperOptions['name'];
                unset($helperOptions['name']);

                $options = array_merge($options, $helperOptions);
            }

            $callable = function () use ($helperName) {
                return $this->callHelper($helperName, func_get_args());
            };

            $functions[] = new TwigFunction($functionName, $callable, $options);
        }

        return $functions;

        // those are just for auto-complete, not nice, but works ;-)
        new TwigFunction('pimcore_head_link');
        new TwigFunction('pimcore_head_meta');
        new TwigFunction('pimcore_head_script');
        new TwigFunction('pimcore_head_style');
        new TwigFunction('pimcore_head_title');
        new TwigFunction('pimcore_inline_script');
        new TwigFunction('pimcore_placeholder');
        new TwigFunction('pimcore_cache');
        new TwigFunction('pimcore_device');
        new TwigFunction('pimcore_url');
        new TwigFunction('pimcore_breach_attack_random_content');
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
