<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GoogleMarketingBundle\Event;

final class GoogleTagManagerEvents
{
    /**
     * Triggered before the tag manager head code block is rendered. Can be used to add additional code
     * snippets to the head code.
     *
     * @Event("Pimcore\Bundle\GoogleMarketingBundle\Model\Event\CodeEvent")
     *
     * @var string
     */
    const CODE_HEAD = 'pimcore.analytics.google.tag_manager.code_head';

    /**
     * Triggered before the tag manager body code is rendered. Can be used to add additional code
     * snippets to the body code.
     *
     * @Event("Pimcore\Bundle\GoogleMarketingBundle\Model\Event\TagManager\CodeEvent")
     *
     * @var string
     */
    const CODE_BODY = 'pimcore.analytics.google.tag_manager.code_body';
}
