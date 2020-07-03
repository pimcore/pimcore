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

namespace Pimcore\Targeting\Condition;

use Pimcore\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Request;

class Language extends AbstractVariableCondition implements ConditionInterface
{
    /**
     * @var string|null
     */
    private $language;

    /**
     * @param null|string $language
     */
    public function __construct(string $language = null)
    {
        $this->language = $language;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['language'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return !empty($this->language);
    }

    /**
     * @inheritDoc
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

    protected function loadLanguage(Request $request)
    {
        // handle override
        $language = OverrideAttributeResolver::getOverrideValue($request, 'language');
        if (!empty($language)) {
            return $language;
        }

        return $request->getPreferredLanguage();
    }
}
