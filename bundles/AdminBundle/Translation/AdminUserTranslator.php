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

namespace Pimcore\Bundle\AdminBundle\Translation;

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Symfony\Component\Translation\TranslatorInterface;

class AdminUserTranslator implements TranslatorInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserLoader
     */
    private $userLoader;

    public function __construct(TranslatorInterface $translator, UserLoader $userLoader)
    {
        $this->translator = $translator;
        $this->userLoader = $userLoader;
    }

    private function getUserLocale()
    {
        if (null !== $user = $this->userLoader->getUser()) {
            return $user->getLanguage();
        }
    }

    /**
     * @inheritDoc
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $domain = $domain ?? 'admin';
        $locale = $locale ?? $this->getUserLocale();

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @inheritDoc
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $domain = $domain ?? 'admin';
        $locale = $locale ?? $this->getUserLocale();

        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * @inheritDoc
     */
    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    /**
     * @inheritDoc
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }
}
