<?php

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Config;

readonly class ColumnInformation implements \JsonSerializable
{

    public function __construct(
        private string $name,
        private bool   $disableOrderBy = false,
        private bool   $disableFilterable = false,
        private bool   $disableDropdownFilterable = false
    )
    {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDisableOrderBy(): bool
    {
        return $this->disableOrderBy;
    }

    public function isDisableFilterable(): bool
    {
        return $this->disableFilterable;
    }

    public function isDisableDropdownFilterable(): bool
    {
        return $this->disableDropdownFilterable;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'disableOrderBy' => $this->disableOrderBy,
            'disableFilterable' => $this->disableFilterable,
            'disableDropdownFilterable' => $this->disableDropdownFilterable
        ];
    }
}
