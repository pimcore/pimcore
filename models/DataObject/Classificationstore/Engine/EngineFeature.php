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

namespace Pimcore\Model\DataObject\Classificationstore\Engine;

/**
 * Capabilities a classification store engine may offer on top of the storage every engine provides.
 *
 * They exist so that a caller can ask an engine what it is able to do instead of testing for a
 * concrete engine class. Two write paths consume supports() and both refuse a value rather than
 * storing something other than what was asked for: the relation check in
 * {@see \Pimcore\Model\DataObject\Classificationstore\RelationResolver::assertStorable()}, and the
 * qualified-value API of {@see \Pimcore\Model\DataObject\Classificationstore}.
 *
 * The document engine offers every capability there is. MultiValue and Variants because a value
 * carries the qualifiers that place it as members of its own entry; Relations and StructuredValues
 * because a document holds whatever normalize() returns, so a relation's element descriptors and a
 * structured value's members need no addressing of their own. The EAV engine offers none of the
 * four: the primary key of its value table has room for the language and for nothing else.
 *
 * PerKeyLocalization is the one the existing value table always served - its primary key already
 * carries the language - so both engines answer it, as it is a property of the key configuration
 * rather than of the storage.
 *
 * PerKeyLocalization is the one the existing value table always served - its primary key already
 * carries the language - so both engines answer it, as it is a property of the key configuration
 * rather than of the storage.
 *
 * @internal
 */
enum EngineFeature
{
    case MultiValue;
    case Variants;
    case PerKeyLocalization;
    case Relations;

    /**
     * The engine holds a value that is not a scalar, which is what decides the field types a key of
     * one of its stores may be defined with.
     *
     * The one capability that is consumed: it is read by
     * {@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()}, together with
     * {@see \Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport}.
     */
    case StructuredValues;
}
