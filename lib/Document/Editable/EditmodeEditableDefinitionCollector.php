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

namespace Pimcore\Document\Editable;


use Pimcore\Model\Document\Editable;

final class EditmodeEditableDefinitionCollector
{
    /**
     * @var Editable[]
     */
    private array $editables = [];

    public function add(Editable $editable): void
    {
        $this->editables[$editable->getName()] = $editable;
    }

    public function remove(Editable $editable): void
    {
        if(isset($this->editables[$editable->getName()])) {
            unset($this->editables[$editable->getName()]);
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function clearConfig($value)
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->clearConfig($item);
            }
        } elseif (!is_scalar($value)) {
            $value = null;
        }

        return $value;
    }

    public function getCode(): string
    {
        $configs = [];
        foreach($this->editables as $editable) {
            $configs[] = $this->clearConfig($editable->getEditmodeDefinition());
        }

        $code = '
            <script>
                var editableDefinitions = ' . json_encode($configs, JSON_PRETTY_PRINT) . ';
            </script>
        ';

        if (json_last_error()) {
            throw new \Exception('json encode failed: ' . json_last_error_msg());
        }

        return $code;
    }
}
