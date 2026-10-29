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
 * concrete engine class. The EAV engine offers none of them, and nothing consumes supports() yet.
 *
 * PerKeyLocalization is the one the existing value table could also serve - its primary key already
 * carries the language - so the EAV answer to it is expected to change when the capability lands,
 * rather than being a property of the storage.
 *
 * @internal
 */
enum EngineFeature
{
    case MultiValue;
    case Variants;
    case PerKeyLocalization;
    case Relations;
}
