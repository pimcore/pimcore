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

namespace Pimcore\Event;

final class TranslationEvents
{
    /**
     * @Event("Pimcore\Event\Model\TranslationEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.translation.preSave';

    /**
     * @Event("Pimcore\Event\Model\TranslationEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.translation.postSave';

    /**
     * @Event("Pimcore\Event\Model\TranslationEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.translation.preDelete';

    /**
     * @Event("Pimcore\Event\Model\TranslationEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.translation.postDelete';
}
