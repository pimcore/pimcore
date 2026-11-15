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

namespace Pimcore\Contracts\ChangeControl;

/**
 * Where in a data object a value goes.
 */
enum FieldKind: string
{
    /** a field of the class */
    case Field = 'field';

    /** a localized field, in one language */
    case Localized = 'localized';

    /** a field of an object brick */
    case Brick = 'brick';

    /** a key of a classification store, in one language or `default` */
    case Classification = 'classification';
}
