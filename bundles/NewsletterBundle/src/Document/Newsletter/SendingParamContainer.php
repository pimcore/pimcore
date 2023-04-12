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

namespace Pimcore\Bundle\NewsletterBundle\Document\Newsletter;

class SendingParamContainer
{
    /**
     * @internal
     *
     * @var string
     */
    protected string $email;

    /**
     * @internal
     *
     * @var array|null
     */
    protected ?array $params = null;

    /**
     * SendingParamContainer constructor.
     *
     * @param string $email
     * @param array|null $params
     */
    public function __construct(string $email, array $params = null)
    {
        $this->email = $email;
        $this->params = $params;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): void
    {
        $this->params = $params;
    }
}
