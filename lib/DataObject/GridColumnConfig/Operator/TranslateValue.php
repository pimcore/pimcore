<?php

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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Translation\Translator;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslateValue extends AbstractOperator
{
    /**
     * @var TranslatorInterface|LocaleAwareInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string|null
     */
    private $locale;

    /**
     * {@inheritdoc}
     */
    public function __construct(Translator $translator, \stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->translator = $translator;
        $this->prefix = $config->prefix ?? '';
        if (isset($context['language'])) {
            $this->locale = $context['language'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue($element)
    {
        $childs = $this->getChilds();
        if (isset($childs[0])) {
            $value = $childs[0]->getLabeledValue($element);
            if ((string)$value->value != '') {
                $currentLocale = $this->translator->getLocale();
                if (null != $this->locale) {
                    $this->translator->setLocale($this->locale);
                }

                $value->value = $this->translator->trans($this->prefix .(string)$value->value, []);

                $this->translator->setLocale($currentLocale);
            }

            return $value;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
