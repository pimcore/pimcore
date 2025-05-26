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

namespace Pimcore\Messenger\Handler;

use Exception;
use Pimcore\Logger;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Model\Document\Service;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class GeneratePagePreviewHandler
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(GeneratePagePreviewMessage $message): void
    {
        try {
            Service::generatePagePreview($message->getPageId(), null, $message->getHostUrl());
        } catch (Exception $e) {
            Logger::err(sprintf('Unable to generate preview of document: %s, reason: %s ', $message->getPageId(), $e->getMessage()));
        }
    }
}
