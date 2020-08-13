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

namespace Pimcore\Document\Tag\Block;

use Pimcore\Document\Editable\Block\BlockState as EditableBlockState;

@trigger_error(sprintf('Class "%s" is deprecated since v6.8 and will be removed in 7. Use "%s" instead.', BlockState::class, EditableBlockState::class), E_USER_DEPRECATED);

class_exists(EditableBlockState::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Document\Editable\Block\BlockState instead.
     */
    class BlockState extends EditableBlockState
    {
    }
}
