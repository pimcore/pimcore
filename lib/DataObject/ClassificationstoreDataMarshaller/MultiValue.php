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

namespace Pimcore\DataObject\ClassificationstoreDataMarshaller;

use Pimcore\Element\MarshallerService;
use Pimcore\Marshaller\MarshallerInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueField;

/**
 * Packs the value of a multi-value key into the two value columns the EAV table has, through the
 * marshaller of the key's inner type - the columns belong to the inner value, not to the wrapper.
 * An inner type without a marshaller of its own goes into `value` as plain normalized data, which
 * is what the engine does for an unwrapped key of that type.
 *
 * So this marshaller packs NOTHING of its own, and its registration for the format says nothing
 * about the shape it produces: handed a structure for an inner type that has no marshaller, it
 * returns that structure in the `value` member. Whoever has to know whether a value ends up in the
 * two scalar columns asks the whole delegate chain rather than this registration,
 * {@see \Pimcore\Model\DataObject\Classificationstore\Service::marshallerPacksIntoValueColumns()} -
 * {@see \Pimcore\Model\DataObject\Classificationstore\Engine\EavEngine} refuses such a shape and
 * {@see \Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine} envelopes it, so the
 * structure below never reaches an encryption that would cast it to the string `'Array'`.
 *
 * @internal
 */
class MultiValue implements MarshallerInterface
{
    private const MARSHALLER_FORMAT = 'classificationstore';

    public function __construct(private readonly MarshallerService $marshallerService)
    {
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        $delegate = $this->getDelegate($params);
        if ($delegate === null) {
            return ['value' => $value];
        }

        return $this->marshallerService
            ->buildFieldefinitionMarshaller(self::MARSHALLER_FORMAT, $delegate->getFieldType())
            ->marshal($value, $this->delegateParameters($delegate, $params));
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        $delegate = $this->getDelegate($params);
        if ($delegate === null) {
            return is_array($value) ? ($value['value'] ?? null) : null;
        }

        return $this->marshallerService
            ->buildFieldefinitionMarshaller(self::MARSHALLER_FORMAT, $delegate->getFieldType())
            ->unmarshal($value, $this->delegateParameters($delegate, $params));
    }

    /**
     * The parameters of the delegate's own call: its definition in place of the wrapper's, and the
     * object the call was made for, which a delegate that marshals before encryption is handed.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function delegateParameters(Data $delegate, array $params): array
    {
        return [
            'object' => $params['object'] ?? null,
            'fieldDefinition' => $delegate,
            'format' => self::MARSHALLER_FORMAT,
        ];
    }

    /**
     * The inner type, when it carries a classification store marshaller of its own.
     *
     * @param array<string, mixed> $params
     */
    private function getDelegate(array $params): ?Data
    {
        $fieldDefinition = $params['fieldDefinition'] ?? null;
        if (!$fieldDefinition instanceof MultiValueField) {
            return null;
        }

        $delegate = $fieldDefinition->getDelegate();
        if (!$delegate instanceof Data) {
            return null;
        }

        if (!$this->marshallerService->supportsFielddefinition(self::MARSHALLER_FORMAT, $delegate->getFieldType())) {
            return null;
        }

        return $delegate;
    }
}
