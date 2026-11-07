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

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * How a classification store key of a given field type can be filtered.
 *
 * This is a declaration and nothing else. Core ships no grid filter and no index mapping for a
 * classification store key: its values never reach `object_query_<CLASS>`, because
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore} persists them itself
 * and implements neither persistence-aware interface, and this repository carries no index mapping
 * code at all. The index side - the generic data index bundle and the Studio backend bundle, in
 * their own sibling ticket - is what consumes the declaration and what owns not withdrawing a
 * filter users already have.
 *
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data::getClassificationStoreKeyFilter()} answers
 * it, and answers {@see self::None} for every type that does not override it, so a third-party type
 * keeps working without knowing this exists.
 */
enum KeyFilter
{
    /**
     * Core declares no filter for this type.
     */
    case None;

    /**
     * Equality against the stored value, or against one of a set of them.
     */
    case Term;

    /**
     * A substring or text match over the stored text.
     */
    case FullText;

    /**
     * A numeric comparison, and a range built from two of them.
     */
    case Numeric;

    /**
     * A date comparison, and a range built from two of them.
     */
    case Date;

    /**
     * True against false.
     */
    case Boolean;
}
