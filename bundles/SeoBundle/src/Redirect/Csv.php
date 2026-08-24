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

namespace Pimcore\Bundle\SeoBundle\Redirect;

use DateTime;
use InvalidArgumentException;
use League\Csv\EncloseField;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model\Element\Service;
use Pimcore\Tool\Admin;
use Pimcore\Tool\ArrayNormalizer;
use Pimcore\Tool\Text;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

/**
 * @internal
 */
class Csv
{
    /**
     * @var string[]
     */
    private array $columns = [
        'id',
        'type',
        'source',
        'sourceSite',
        'target',
        'targetType',
        'targetSite',
        'statusCode',
        'priority',
        'regex',
        'passThroughParameters',
        'active',
        'expiry',
    ];

    private ?ArrayNormalizer $importNormalizer = null;

    private ?OptionsResolver $importResolver = null;

    /**
     *
     *
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     */
    public function createExportWriter(Redirect\Listing $list): Writer
    {
        $writer = Writer::createFromPath('php://temp');
        $writer->setDelimiter(';');
        $writer->setOutputBOM(Writer::BOM_UTF8);

        // force "" enclosure as it allows us to just open the file in excel
        EncloseField::addTo($writer, "\t\x1f");

        $writer->insertOne($this->columns);

        foreach ($list->getRedirects() as $redirect) {
            $target = $redirect->getTarget();
            $targetType = $redirect->getTargetType();

            if (is_numeric($target)) {
                // legacy rows without an explicit type point to documents
                $element = Service::getElementById($targetType ?: Redirect::TARGET_TYPE_DOCUMENT, (int) $target);

                if ($element) {
                    $target = $element->getRealFullPath();
                }
            }

            $expiry = null;
            if ($redirect->getExpiry()) {
                $expiry = (new DateTime('@' . $redirect->getExpiry()))->format('c');
            }

            $data = [
                $redirect->getId(),
                $redirect->getType(),
                $redirect->getSource(),
                $redirect->getSourceSite(),
                $target,
                $targetType,
                $redirect->getTargetSite(),
                $redirect->getStatusCode(),
                $redirect->getPriority(),
                $redirect->getRegex(),
                $redirect->getPassThroughParameters(),
                $redirect->getActive(),
                $expiry,
            ];
            $data = Service::escapeCsvRecord($data);

            $writer->insertOne($data);
        }

        return $writer;
    }

    /**
     *
     *
     * @throws \League\Csv\Exception
     */
    public function import(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new InvalidArgumentException(sprintf('`%s`: failed to open stream: No such file or directory', $filename));
        }

        // reading the whole content and converting it to UTF-8 I didn't get the stream filter to work properly
        // TODO check if this can be done without loading the whole file into memory and re-try using a stream filter if necessary
        $content = file_get_contents($filename);
        $content = Text::convertToUTF8($content);

        $dialect = Admin::determineCsvDialect($filename);

        $reader = Reader::createFromString($content);
        $reader->setOutputBOM(Reader::BOM_UTF8);
        $reader->setDelimiter($dialect->delimiter);
        $reader->setHeaderOffset(0);

        $stmt = new Statement();
        $result = $stmt->process($reader);

        $stats = [
            'total' => $result->count(),
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'errored' => 0,
        ];

        $errors = [];
        foreach ($result as $line => $record) {
            try {
                $data = $this->preprocessImportData($record);
                $this->processImportData($data, $stats);

                $stats['imported']++;
            } catch (Throwable $e) {
                $stats['errored']++;
                $errors[$line] = $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            $stats['errors'] = $errors;
        }

        return $stats;
    }

    private function preprocessImportData(array $record): array
    {
        // normalize data to types (string, int, ...) or null
        $data = $this->getImportNormalizer()->normalize($record);

        // validate data
        $data = $this->getImportResolver()->resolve($data);

        return $data;
    }

    private function processImportData(array $data, array &$stats): void
    {
        $redirect = null;

        if ($data['id']) {
            $redirect = Redirect::getById($data['id']);
            if ($redirect instanceof Redirect) {
                $stats['updated']++;
            }
        }

        if (!$redirect instanceof Redirect) {
            $redirect = new Redirect();
            $stats['created']++;
        }

        // ID is already set or will be generated
        unset($data['id']);

        // resolve a path-based target to its element ID using the (optional) target type;
        // legacy exports without a target type point to documents
        if (is_string($data['target']) && '' !== $data['target']) {
            $targetType = $data['targetType'] ?: Redirect::TARGET_TYPE_DOCUMENT;
            if ($element = Service::getElementByPath($targetType, $data['target'])) {
                $data['target'] = (string) $element->getId();
                $data['targetType'] = $targetType;
            }
        }

        $redirect->setValues($data);
        $redirect->save();
    }

    private function getImportNormalizer(): ArrayNormalizer
    {
        if (null !== $this->importNormalizer) {
            return $this->importNormalizer;
        }

        $normalizer = new ArrayNormalizer();

        $normalizer->addNormalizer(['id', 'sourceSite', 'targetSite', 'statusCode', 'priority'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return (int)$value;
        });

        $normalizer->addNormalizer(['type', 'source'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return (string)$value;
        });

        $normalizer->addNormalizer(['target'], function ($value) {
            if (empty($value)) {
                return null;
            }

            if (is_numeric($value)) {
                return (int)$value;
            }

            // a path is resolved to an element ID later, once the target type is known
            return (string)$value;
        });

        $normalizer->addNormalizer(['targetType'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return (string)$value;
        });

        $normalizer->addNormalizer(['regex', 'passThroughParameters', 'active'], function ($value) {
            if (empty($value)) {
                return false;
            }

            return (bool)$value;
        });

        $normalizer->addNormalizer(['expiry'], function ($value) {
            if (empty($value)) {
                return null;
            }

            return strtotime($value);
        });

        $this->importNormalizer = $normalizer;

        return $this->importNormalizer;
    }

    private function getImportResolver(): OptionsResolver
    {
        if (null !== $this->importResolver) {
            return $this->importResolver;
        }

        $resolver = new OptionsResolver();
        // targetType is optional so CSV files exported before it existed can still be imported
        $resolver->setRequired(array_values(array_diff($this->columns, ['targetType'])));

        $resolver->setAllowedTypes('id', ['int', 'null']);

        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedValues('type', Redirect::TYPES);

        $resolver->setAllowedTypes('source', ['string', 'null']);
        $resolver->setAllowedTypes('sourceSite', ['int', 'null']);
        $resolver->setAllowedTypes('target', ['string', 'int', 'null']);
        $resolver->setDefault('targetType', null);
        $resolver->setAllowedTypes('targetType', ['string', 'null']);
        $resolver->setAllowedValues('targetType', array_merge([null], Redirect::TARGET_TYPES));
        $resolver->setAllowedTypes('targetSite', ['int', 'null']);

        $resolver->setAllowedTypes('statusCode', ['int']);
        $resolver->setAllowedValues('statusCode', array_map(function ($code) {
            return (int)$code;
        }, array_keys(Redirect::getStatusCodes())));

        $resolver->setAllowedTypes('priority', ['int']);
        $resolver->setAllowedValues('priority', array_merge(range(1, 10), [99]));

        $resolver->setAllowedTypes('regex', ['bool']);
        $resolver->setAllowedTypes('passThroughParameters', ['bool']);
        $resolver->setAllowedTypes('active', ['bool']);
        $resolver->setAllowedTypes('expiry', ['int', 'null']);

        $this->importResolver = $resolver;

        return $this->importResolver;
    }
}
