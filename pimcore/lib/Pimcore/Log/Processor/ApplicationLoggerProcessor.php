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

namespace Pimcore\Log\Processor;

use Pimcore\Log\FileObject;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;

/**
 * Make sure you add this processor when using the ApplicationLoggerDb handler as is
 * prepares data to be written by the handler. This replicates the functionalty implemented
 * in ApplicationLogger, but makes it available when using the ApplicationLoggerDb handler
 * in a pure PSR-3 handler context configured as monolog channel handler instead of
 * the ApplicationLogger class.
 */
class ApplicationLoggerProcessor
{
    public function __invoke(array $record): array
    {
        $record = $this->processFileObject($record);
        $record = $this->processRelatedObject($record);
        $record = $this->processLoggingSource($record);

        return $record;
    }

    private function processFileObject(array $record): array
    {
        $context = $record['context'] ?? [];

        if (isset($context['fileObject'])) {
            if (is_string($context['fileObject'])) {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']);
            } elseif ($context['fileObject'] instanceof FileObject) {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']->getFilename());
            }
        }

        $record['context'] = $context;

        return $record;
    }

    private function processRelatedObject(array $record): array
    {
        if (!isset($record['context']['relatedObject'])) {
            // remove related object type if no object is set
            if (isset($record['context']['relatedObjectType'])) {
                unset($record['context']['relatedObjectType']);
            }

            return $record;
        }

        $relatedObject     = $record['context']['relatedObject'] ?? null;
        $relatedObjectType = null;

        if (null !== $relatedObject && is_object($relatedObject)) {
            if ($relatedObject instanceof AbstractObject) {
                $relatedObject     = $relatedObject->getId();
                $relatedObjectType = 'object';
            } elseif ($relatedObject instanceof Asset) {
                $relatedObject     = $relatedObject->getId();
                $relatedObjectType = 'asset';
            } elseif ($relatedObject instanceof Document) {
                $relatedObject     = $relatedObject->getId();
                $relatedObjectType = 'document';
            }
        }

        $record['context']['relatedObject']     = $relatedObject;
        $record['context']['relatedObjectType'] = $relatedObjectType;

        return $record;
    }

    private function processLoggingSource(array $record): array
    {
        $source = $record['context']['source'] ?? null;
        if ($source) {
            return $record;
        }

        $extra = $record['extra'];
        if (!isset($extra['file']) || !isset($extra['line'])) {
            $record['context']['source'] = null;

            return $record;
        }

        if (isset($extra['class']) && isset($extra['function'])) {
            // called from a class method
            // ClassName->methodName():line
            $source = sprintf(
                '%s::%s:%d',
                $extra['class'],
                $extra['function'],
                $extra['line']
            );
        } elseif (isset($extra['function'])) {
            // called from a function
            // filename.php::functionName():line
            $source = sprintf(
                '%s::%s:%d',
                $this->normalizeFilename($extra['file']),
                $extra['function'],
                $extra['line']
            );
        } else {
            // we don't have a previous call when the logger was directly called
            // from a standalone PHP file (e.g. from a CLI script)
            // filename.php:line
            $source = sprintf(
                '%s:%d',
                $this->normalizeFilename($extra['file']),
                $extra['line']
            );
        }

        $record['context']['source'] = $source;

        return $record;
    }

    private function normalizeFilename($filename)
    {
        return str_replace(PIMCORE_PROJECT_ROOT . '/', '', $filename);
    }
}
