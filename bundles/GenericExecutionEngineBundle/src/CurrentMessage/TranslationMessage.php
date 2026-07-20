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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslationMessage implements MessageInterface
{
    public function __construct(
        private readonly string $key,
        private readonly array $params,
        private readonly string $domain,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getSerializedString(): string
    {
        try {
            return json_encode($this->buildMessageArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '';
        }
    }

    private function buildMessageArray(): array
    {
        return [
            'key' => $this->key,
            'params' => $this->params,
            'domain' => $this->domain,
        ];
    }

    public function getMessage(): string
    {
        return $this->translator->trans($this->key, $this->params, $this->domain);
    }
}
