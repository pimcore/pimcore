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

use JsonException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\InvalidJsonMessageException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class CurrentMessageProvider implements CurrentMessageProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getTranslationMessages(string $key, array $parameters = [], ?string $domain = null): MessageInterface
    {
        return new TranslationMessage($key, $parameters, $domain, $this->translator);
    }

    public function getPlainMessage(string $message): MessageInterface
    {
        return new PlainMessage($message);
    }

    public function getMessageFromSerializedString(string $message): MessageInterface
    {
        try {
            $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($message['key'], $message['domain'], $message['params']) || !is_array($message['params'])) {
                throw new InvalidJsonMessageException(
                    'Message is not a valid json translation object. Missing key, parameters or domain.'
                );
            }

            return new TranslationMessage($message['key'], $message['params'], $message['domain'], $this->translator);

        } catch (JsonException) {
            return new PlainMessage($message);
        }
    }
}
