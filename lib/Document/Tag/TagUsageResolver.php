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

namespace Pimcore\Document\Tag;

use Pimcore\Document\Editable\EditableUsageResolver;

@trigger_error(sprintf('Class "%s" is deprecated since v6.8 and will be removed in 7. Use "%s" instead.', TagUsageResolver::class, EditableUsageResolver::class), E_USER_DEPRECATED);

class_exists(EditableUsageResolver::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Document\Editable\TagUsageResolver instead.
     */
    class TagUsageResolver extends EditableUsageResolver
    {
    }
}
