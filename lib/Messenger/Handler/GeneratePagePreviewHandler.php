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
            // The host URL carried by the message is deliberately ignored: any producer (also ones outside of
            // this repository, e.g. the classic admin UI) could have filled it from the inbound request Host,
            // which must never select the network destination of the renderer. The origin is resolved from
            // the trusted configuration instead.
            Service::generatePagePreview($message->getPageId());
        } catch (Exception $e) {
            Logger::err(sprintf('Unable to generate preview of document: %s, reason: %s ', $message->getPageId(), $e->getMessage()));
        }
    }
}
