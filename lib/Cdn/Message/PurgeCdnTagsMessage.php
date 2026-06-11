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

namespace Pimcore\Cdn\Message;

/**
 * Carries all surrogate-key tags of one purge-triggering event as a single message,
 * so one event costs one transport insert and one (chunked) provider request instead
 * of one per tag.
 */
final readonly class PurgeCdnTagsMessage
{
    /**
     * @param string[] $tags
     */
    public function __construct(public array $tags)
    {
    }
}
