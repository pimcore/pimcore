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

namespace Pimcore\Messenger\Handler;

use Pimcore\Config;
use Pimcore\Messenger\GenerateWeb2PrintPdfMessage;
use Pimcore\Web2Print\Exception\NotPreparedException;
use Pimcore\Web2Print\Processor;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class GenerateWeb2PrintPdfHandler
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(GenerateWeb2PrintPdfMessage $message)
    {
        $documentId = $message->getProcessId();

        // check for memory limit
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit !== '-1') {
            $config = Config::getSystemConfiguration();
            $memoryLimitConfig = $config['documents']['web_to_print']['pdf_creation_php_memory_limit'] ?? 0;
            if (!empty($memoryLimitConfig) && filesize2bytes($memoryLimit . 'B') < filesize2bytes($memoryLimitConfig . 'B')) {
                $this->logger->info('Info: PHP:memory_limit set to ' . $memoryLimitConfig . ' from config documents.web_to_print.pdf_creation_php_memory_limit');

                ini_set('memory_limit', $memoryLimitConfig);
            }
        }

        try {
            Processor::getInstance()->startPdfGeneration($documentId);
        } catch (NotPreparedException $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
