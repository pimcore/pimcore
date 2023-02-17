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

namespace Pimcore\Bundle\WebToPrintBundle\Messenger\Handler;

use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Bundle\WebToPrintBundle\Exception\NotPreparedException;
use Pimcore\Bundle\WebToPrintBundle\Messenger\GenerateWeb2PrintPdfMessage;
use Pimcore\Bundle\WebToPrintBundle\Processor;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class GenerateWeb2PrintPdfHandler
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(GenerateWeb2PrintPdfMessage $message): void
    {
        $documentId = $message->getProcessId();

        // check for memory limit
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit !== '-1') {
            $config = Config::getWeb2PrintConfig();
            $memoryLimitConfig = $config['pdf_creation_php_memory_limit'] ?? 0;
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
