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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Request;

class Language extends AbstractVariableCondition implements ConditionInterface
{
    private ?string $language = null;

    /**
     * @param null|string $language
     */
    public function __construct(string $language = null)
    {
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(array $config): static
    {
        return new static($config['language'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function canMatch(): bool
    {
        return !empty($this->language);
    }

    /**
     * {@inheritdoc}
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $request = $visitorInfo->getRequest();

        $language = $this->loadLanguage($request);
        if (empty($language)) {
            return false;
        }

        if ($language === $this->language) {
            $this->setMatchedVariable('language', $language);

            return true;
        }

        // only check the language without territory if configured
        if (false === strpos($this->language, '_') && false !== strpos($language, '_')) {
            $normalizedLanguage = explode('_', $language)[0];

            if ($normalizedLanguage === $this->language) {
                $this->setMatchedVariable('language', $language);

                return true;
            }
        }

        return false;
    }

    protected function loadLanguage(Request $request): ?string
    {
        // handle override
        $language = OverrideAttributeResolver::getOverrideValue($request, 'language');
        if (!empty($language)) {
            return $language;
        }

        return $request->getPreferredLanguage();
    }
}
