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

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Embed extends Model\Document\Editable
{
    /**
     * @internal
     */
    protected ?string $url = null;

    public function getType(): string
    {
        return 'embed';
    }

    public function getData(): mixed
    {
        return [
            'url' => $this->url,
        ];
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getDataForResource(): array
    {
        return [
            'url' => $this->url,
        ];
    }

    public function frontend()
    {
        if ($this->url) {
            $config = $this->getConfig();
            if (!isset($config['params'])) {
                $config['params'] = [];
            }

            foreach (['width', 'height'] as $property) {
                if (isset($config[$property])) {
                    $config['params'][$property] = $config[$property];
                }
            }

            $cacheKey = 'doc_embed_' . crc32(serialize([$this->url, $config]));

            if (!$html = \Pimcore\Cache::load($cacheKey)) {
                $embera = new \Embera\Embera($config);
                $html = $embera->autoEmbed($this->url);

                \Pimcore\Cache::save($html, $cacheKey, ['embed'], 86400, 1, true);
            }

            return $html;
        }

        return '';
    }

    public function admin()
    {
        $html = parent::admin();

        // get frontendcode for preview
        // put the video code inside the generic code
        $html = str_replace('</div>', $this->frontend() . '</div>', $html);

        return $html;
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->url = $unserializedData['url'] ?? null;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if ($data['url']) {
            $this->url = $data['url'];
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        if ($this->url) {
            return false;
        }

        return true;
    }
}
