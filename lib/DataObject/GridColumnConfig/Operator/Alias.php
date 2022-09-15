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
final class Alias extends AbstractOperator implements TranslatorAwareOperatorInterface
{
    private TranslatorInterface $translator;

    /**
     * {@inheritdoc}
     */
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue($element, ?string $requestedLanguage = null)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $c = $childs[0];

            $valueArray = [];

            $childResult = $c->getLabeledValue($element);
            $isArrayType = $childResult->isArrayType ?? null;
            $childValues = $childResult->value;

            if($childResult->def instanceof Select) {
                $childValues = $this->translator->trans($childValues, [], 'admin', $requestedLanguage);
            }

            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            if ($childValues) {
                /** @var string $childValue */
                foreach ($childValues as $childValue) {
                    $valueArray[] = $childValue;
                }
            }

            $result->isArrayType = $isArrayType;
            if ($isArrayType) {
                $result->value = $valueArray;
            } else {
                $result->value = $valueArray[0] ?? null;
            }
        }

        return $result;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}
