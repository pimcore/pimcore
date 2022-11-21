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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class LocaleSwitcher extends AbstractOperator
{
    private \stdClass|LocaleServiceInterface $localeService;

    private ?string $locale = null;

    public function __construct(LocaleServiceInterface $localeService, \stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->localeService = $localeService;
        $this->locale = $config->locale ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): \Pimcore\DataObject\GridColumnConfig\ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        } else {
            $c = $children[0];

            $currentLocale = $this->localeService->getLocale();

            $this->localeService->setLocale($this->locale);

            $result = $c->getLabeledValue($element);

            $this->localeService->setLocale($currentLocale);
        }

        return $result;
    }
}
