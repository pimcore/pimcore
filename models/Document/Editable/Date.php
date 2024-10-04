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

namespace Pimcore\Model\Document\Editable;

use Carbon\Carbon;
use DateTimeInterface;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Date extends Model\Document\Editable implements EditmodeDataInterface
{
    /**
     * Contains the date
     *
     * @internal
     *
     */
    protected ?\Carbon\Carbon $date = null;

    public function getType(): string
    {
        return 'date';
    }

    public function getData(): mixed
    {
        return $this->date;
    }

    public function getDate(): ?\Carbon\Carbon
    {
        return $this->getData();
    }

    public function getDataEditmode(): ?int
    {
        if ($this->date) {
            return $this->date->getTimestamp();
        }

        return null;
    }

    public function frontend()
    {
        if ($this->date instanceof Carbon) {
            if (isset($this->config['outputIsoFormat']) && $this->config['outputIsoFormat']) {
                return $this->date->isoFormat($this->config['outputIsoFormat']);
            } elseif (isset($this->config['outputFormat']) && $this->config['outputFormat']) {
                trigger_deprecation(
                    'pimcore/pimcore',
                    '11.2',
                    'Using "outputFormat" config for %s editable is deprecated, use "outputIsoFormat" config instead.',
                    __CLASS__
                );

                return $this->date->formatLocalized($this->config['outputFormat']);
            } else {
                if (isset($this->config['format']) && $this->config['format']) {
                    $format = $this->config['format'];
                } else {
                    $format = DateTimeInterface::ATOM;
                }

                return $this->date->format($format);
            }
        }

        return '';
    }

    public function getDataForResource(): mixed
    {
        if ($this->date) {
            return $this->date->getTimestamp();
        }

        return null;
    }

    public function setDataFromResource(mixed $data): static
    {
        if ($data) {
            $this->setDateFromTimestamp((int)$data);
        }

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if (strlen((string) $data) > 5) {
            $timestamp = strtotime($data);
            $this->setDateFromTimestamp($timestamp);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        if ($this->date) {
            return false;
        }

        return true;
    }

    private function setDateFromTimestamp(int $timestamp): void
    {
        $this->date = new Carbon();
        $this->date->setTimestamp($timestamp);
    }
}
