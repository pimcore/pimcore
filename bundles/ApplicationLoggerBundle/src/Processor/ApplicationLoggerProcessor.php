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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Processor;

use Monolog\LogRecord;
use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger;
use Pimcore\Bundle\ApplicationLoggerBundle\FileObject;
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
    public function __invoke(LogRecord $record): LogRecord
    {
        $record = $this->processFileObject($record);
        $record = $this->processRelatedObject($record);
        $record = $this->processLoggingSource($record);

        return $record;
    }

    private function processFileObject(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (isset($context['fileObject'])) {
            if (is_string($context['fileObject'])) {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']);
            } elseif ($context['fileObject'] instanceof FileObject) {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']->getFilename());
            }
        }

        return $record->with(context: $context);
    }

    private function processRelatedObject(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (!isset($context['relatedObject'])) {
            // remove related object type if no object is set
            if (isset($context['relatedObjectType'])) {
                unset($context['relatedObjectType']);
            }

            return $record->with(context: $context);
        }

        $relatedObject = $context['relatedObject'];
        $relatedObjectType = $context['relatedObjectType'] ?? null;

        if (null !== $relatedObject && is_object($relatedObject)) {
            if ($relatedObject instanceof AbstractObject) {
                $relatedObject = $relatedObject->getId();
                $relatedObjectType = 'object';
            } elseif ($relatedObject instanceof Asset) {
                $relatedObject = $relatedObject->getId();
                $relatedObjectType = 'asset';
            } elseif ($relatedObject instanceof Document) {
                $relatedObject = $relatedObject->getId();
                $relatedObjectType = 'document';
            }
        }

        $context['relatedObject'] = $relatedObject;
        $context['relatedObjectType'] = $relatedObjectType;

        return $record->with(context: $context);
    }

    private function processLoggingSource(LogRecord $record): LogRecord
    {
        $context = $record->context;
        $source = $context['source'] ?? null;
        if ($source) {
            return $record;
        }

        $extra = $record->extra;
        if (!isset($extra['file']) || !isset($extra['line'])) {
            $context['source'] = null;

            return $record->with(context: $context);
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

        $context['source'] = $source;

        return $record->with(context: $context);
    }

    private function normalizeFilename(string $filename): string
    {
        return str_replace(PIMCORE_PROJECT_ROOT . '/', '', $filename);
    }
}
