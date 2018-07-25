<?php
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
