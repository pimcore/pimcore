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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Exception;

class NameMappingException extends \RuntimeException
{
    /**
     * @var bool
     */
    private $showMessage = false;

    /**
     * @inheritDoc
     */
    public function __construct(string $message, int $code = 1, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function setShowMessage(bool $showMessage)
    {
        $this->showMessage = $showMessage;
    }

    public function getShowMessage(): bool
    {
        return $this->showMessage;
    }
}
