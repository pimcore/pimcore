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
 * How a merge writes the objects. Publish writes and publishes; Draft keeps the result as an
 * unpublished version; Save writes and leaves the published flag as it was, so a new object stays
 * unpublished; SaveAndPublishNew saves what exists and publishes what the merge creates.
 */
enum MergeMode: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Save = 'save';
    case SaveAndPublishNew = 'save_publish_new';
}
