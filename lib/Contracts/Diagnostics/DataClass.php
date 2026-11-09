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

namespace Pimcore\Contracts\Diagnostics;

/**
 * The kinds of data a diagnostic check may deliver. There is deliberately no case for secrets: credentials,
 * tokens, keys and DSNs with passwords can never be declared and must never be returned.
 */
enum DataClass: string
{
    case Version = 'version';
    case Configuration = 'configuration';
    case Infrastructure = 'infrastructure';
    case Metric = 'metric';
    case Identifier = 'identifier';
    case Digest = 'digest';
    case Text = 'text';
    case PersonalData = 'personal_data';
}
