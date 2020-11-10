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

namespace Pimcore\Routing\Redirect;

use League\Csv\EncloseField;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Tool\Admin;
use Pimcore\Tool\ArrayNormalizer;
use Pimcore\Tool\Text;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Csv
{
    /**
     * @var array
     */
    private $columns = [
        'id',
        'type',
        'source',
        'sourceSite',
        'target',
        'targetSite',
        'statusCode',
        'priority',
        'regex',
        'passThroughParameters',
        'active',
        'expiry',
    ];

    /**
     * @var ArrayNormalizer
     */
    private $importNormalizer;

    /**
     * @var OptionsResolver
     */
    private $importResolver;

    public function createExportWriter(Redirect\Listing $list): Writer
    {
        $writer = Writer::createFromPath('php://temp');
        $writer->setDelimiter(';');
        $writer->setOutputBOM(Writer::BOM_UTF8);

        // force "" enclosure as it allows us to just open the file in excel
        EncloseField::addTo($writer, "\t\x1f");

        $writer->insertOne($this->columns);

        /** @var Redirect $redirect */
        foreach ($list->getRedirects() as $redirect) {
            $target = $redirect->getTarget();

            if (is_numeric($redirect->getTarget())) {
                $document = Document::getById((int)$redirect->getTarget());

                if ($document) {
                    $target = $document->getRealFullPath();
                }
            }

            $expiry = null;
            if ($redirect->getExpiry()) {
                $expiry = (new \DateTime('@' . $redirect->getExpiry()))->format('c');
            }

            $data = [
                $redirect->getId(),
                $redirect->getType(),
                $redirect->getSource(),
                $redirect->getSourceSite(),
                $target,
                $redirect->getTargetSite(),
                $redirect->getStatusCode(),
                $redirect->getPriority(),
                $redirect->getRegex(),
                $redirect->getPassThroughParameters(),
                $redirect->getActive(),
                $expiry,
            ];

            $writer->insertOne($data);
        }

        return $writer;
    }

    public function import(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf('`%s`: failed to open stream: No such file or directory', $filename));
        }

        // reading the whole content and converting it to UTF-8 I didn't get the stream filter to work properly
        // TODO check if this can be done without loading the whole file into memory and re-try using a stream filter if necessary
        $content = file_get_contents($filename);
        $content = Text::convertToUTF8($content);

        $dialect = Admin::determineCsvDialect($filename);

        /** @var Reader $reader */
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
            } catch (\Throwable $e) {
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

    private function processImportData(array $data, array &$stats)
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

        $redirect->setValues($data);
        $redirect->save();

        return $redirect;
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
            } elseif (is_string($value)) {
                if ($target = Document::getByPath($value)) {
                    return (int)$target->getId();
                }
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
        $resolver->setRequired($this->columns);

        $resolver->setAllowedTypes('id', ['int', 'null']);

        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedValues('type', Redirect::TYPES);

        $resolver->setAllowedTypes('source', ['string', 'null']);
        $resolver->setAllowedTypes('sourceSite', ['int', 'null']);
        $resolver->setAllowedTypes('target', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('targetSite', ['int', 'null']);

        $resolver->setAllowedTypes('statusCode', ['int']);
        $resolver->setAllowedValues('statusCode', array_map(function ($code) {
            return (int)$code;
        }, array_keys(Redirect::$statusCodes)));

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
