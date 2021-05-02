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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Embed extends Model\Document\Editable
{
    /**
     * @internal
     *
     * @var string
     */
    protected $url;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'embed';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return [
            'url' => $this->url,
        ];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForResource()
    {
        return [
            'url' => $this->url,
        ];
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function admin()
    {
        $html = parent::admin();

        // get frontendcode for preview
        // put the video code inside the generic code
        $html = str_replace('</div>', $this->frontend() . '</div>', $html);

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = \Pimcore\Tool\Serialize::unserialize($data);
        }

        $this->url = $data['url'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        if ($data['url']) {
            $this->url = $data['url'];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        if ($this->url) {
            return false;
        }

        return true;
    }
}
