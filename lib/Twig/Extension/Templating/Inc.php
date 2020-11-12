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

namespace Pimcore\Twig\Extension\Templating;

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Inc.php
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\IncludeRenderer;
use Twig\Extension\RuntimeExtensionInterface;

class Inc implements RuntimeExtensionInterface
{
    /**
     * @var IncludeRenderer
     */
    protected $includeRenderer;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param IncludeRenderer $includeRenderer
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(IncludeRenderer $includeRenderer, EditmodeResolver $editmodeResolver)
    {
        $this->includeRenderer = $includeRenderer;
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @param PageSnippet|int|string $include
     * @param array $params
     * @param bool $cacheEnabled
     * @param bool|null $editmode
     *
     * @return string
========
@trigger_error(
    'Pimcore\Templating\Helper\Inc is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Inc::class . ' instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Twig\Extension\Templating\Inc::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Inc
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Inc.php
     */
    class Inc extends \Pimcore\Twig\Extension\Templating\Inc {

    }
}
