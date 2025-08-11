<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event\Cache\FullPage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class PrepareTagsEvent extends Event
{
    /**
     * Tags associated with the cache entry
     *
     * @var string[]
     */
    private array $tags = [];

    public function __construct(
        private readonly Request $request,
        private readonly Response $response
    ) {
    }

    public function addTag(string $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
