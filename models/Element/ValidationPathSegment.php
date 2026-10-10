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

namespace Pimcore\Model\Element;

/**
 * One level of the location of a validation error, e.g. a localized field in a given language,
 * an object brick, a field collection item or a block item.
 */
final readonly class ValidationPathSegment
{
    /**
     * @param string $field container field name (e.g. `localizedfields`, the object bricks field, a block field)
     * @param string|null $title raw, untranslated title from the definition
     * @param string|null $language language of a localized field
     * @param int|null $index zero-based item index in a field collection or block
     * @param string|null $type object brick key or field collection type
     * @param string|null $typeTitle raw, untranslated title of the object brick or field collection definition
     */
    public function __construct(
        public string $field,
        public ?string $title = null,
        public ?string $language = null,
        public ?int $index = null,
        public ?string $type = null,
        public ?string $typeTitle = null,
    ) {
    }

    /**
     * @return array{
     *     field: string,
     *     title: ?string,
     *     language: ?string,
     *     index: ?int,
     *     type: ?string,
     *     typeTitle: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'title' => $this->title,
            'language' => $this->language,
            'index' => $this->index,
            'type' => $this->type,
            'typeTitle' => $this->typeTitle,
        ];
    }
}
