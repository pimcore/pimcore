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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Translation;

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class AdminUserTranslator implements TranslatorInterface, LocaleAwareInterface
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

    /**
     * @return string|null
     */
    private function getUserLocale()
    {
        if (null !== $user = $this->userLoader->getUser()) {
            return $user->getLanguage();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null)
    {
        $domain = $domain ?? 'admin';
        $locale = $locale ?? $this->getUserLocale();

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        if ($this->translator instanceof LocaleAwareInterface) {
            return $this->translator->getLocale();
        }

        return \Pimcore\Tool::getDefaultLanguage();
    }
}
