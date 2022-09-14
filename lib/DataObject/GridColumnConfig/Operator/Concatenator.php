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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class Concatenator extends AbstractOperator implements TranslatorOperatorInterface
{
    /**
     * @var string
     */
    private $glue;

    /**
     * @var bool
     */
    private $forceValue;

    private TranslatorInterface $translator;

    /**
     * {@inheritdoc}
     */
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->glue = $config->glue ?? '';
        $this->forceValue = $config->forceValue ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue($element, ?string $requestedLanguage = null)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $hasValue = true;
        if (!$this->forceValue) {
            $hasValue = false;
        }

        $childs = $this->getChilds();
        $valueArray = [];

        foreach ($childs as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = (array)($childResult->value ?? []);

            if($childResult->def instanceof Select) {
                $childValues[0] = $this->translator->trans($childValues[0], [], 'admin', $requestedLanguage);
            }

            foreach ($childValues as $value) {
                if (!$hasValue) {
                    if (!empty($value) || (method_exists($value, 'isEmpty') && !$value->isEmpty())) {
                        $hasValue = true;
                    }
                }

                if ($value !== null) {
                    $valueArray[] = $value;
                }
            }
        }

        if ($hasValue) {
            $result->value = implode($this->glue, $valueArray);

            return $result;
        }

        $result->empty = true;

        return $result;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}
