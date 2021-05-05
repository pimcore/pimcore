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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension\Templating;

use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\IncludeRenderer;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Twig\Extension\RuntimeExtensionInterface;

class Inc implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

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
     */
    public function __invoke($include, array $params = [], $cacheEnabled = true, $editmode = null)
    {
        if (null === $editmode) {
            $editmode = $this->editmodeResolver->isEditmode();
        }

        return $this->includeRenderer->render($include, $params, $editmode, $cacheEnabled);
    }
}
