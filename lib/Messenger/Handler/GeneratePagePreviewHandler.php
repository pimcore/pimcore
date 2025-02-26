<?php
declare(strict_types=1);

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
