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
 * Translation keys of the validation errors raised by Pimcore core,
 * see StructuredValidationException::setTranslation().
 * The field itself is never a parameter: Pimcore Studio injects the translated field label as `field`.
 */
enum ValidationMessageKey: string
{
    case MANDATORY = 'validation.mandatory';

    /** parameters: regex, value */
    case REGEX_MISMATCH = 'validation.regex_mismatch';

    /** parameters: max */
    case MAX_LENGTH = 'validation.max_length';

    /** parameters: min */
    case MIN_LENGTH = 'validation.min_length';

    /** parameters: value */
    case NOT_NUMERIC = 'validation.not_numeric';

    /** parameters: value */
    case NOT_INTEGER = 'validation.not_integer';

    /** parameters: value */
    case NOT_UNSIGNED = 'validation.not_unsigned';

    /** parameters: min, value */
    case MIN_VALUE = 'validation.min_value';

    /** parameters: max, value */
    case MAX_VALUE = 'validation.max_value';

    case RANGE_START_AFTER_END = 'validation.range_start_after_end';

    /** parameters: value */
    case INVALID_EMAIL = 'validation.invalid_email';

    /** parameters: value */
    case INVALID_OPTION = 'validation.invalid_option';

    /** parameters: value */
    case INVALID_TIME = 'validation.invalid_time';

    case INVALID_RELATION = 'validation.invalid_relation';

    /** parameters: max */
    case MAX_RELATIONS = 'validation.max_relations';

    /** parameters: max */
    case MAX_ITEMS = 'validation.max_items';

    /** parameters: value */
    case SLUG_NOT_UNIQUE = 'validation.slug_not_unique';

    case SLUG_INVALID = 'validation.slug_invalid';

    /** parameters: characters */
    case SLUG_RESERVED_CHARACTERS = 'validation.slug_reserved_characters';

    case UNIQUE_CONSTRAINT = 'validation.unique_constraint';
    case MISSING_REQUIRED_EDITABLE = 'validation.missing_required_editable';

    /** parameters: filename, max_megapixels, suggested_width, suggested_height */
    case IMAGE_TOO_LARGE = 'validation.image_too_large';
}
