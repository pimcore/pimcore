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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage;

/**
 * @internal
 */
interface CurrentMessageProviderInterface
{
    public function getTranslationMessages(
        string $key,
        array $parameters = [],
        string $domain = null
    ): MessageInterface;

    public function getPlainMessage(string $message): MessageInterface;

    /**
     * If string is a valid json translation object it will be converted to TranslationMessage
     * otherwise it will be converted to PlainMessage
     */
    public function getMessageFromSerializedString(string $message): MessageInterface;
}
