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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage;

/**
 * @internal
 */
interface CurrentMessageProviderInterface
{
    public function getTranslationMessages(
        string $key,
        array $parameters = [],
        ?string $domain = null
    ): MessageInterface;

    public function getPlainMessage(string $message): MessageInterface;

    /**
     * If string is a valid json translation object it will be converted to TranslationMessage
     * otherwise it will be converted to PlainMessage
     */
    public function getMessageFromSerializedString(string $message): MessageInterface;
}
