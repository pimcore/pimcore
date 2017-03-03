<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Embed extends Model\Document\Tag
{

    /**
     * @var string
     */
    public $url;

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "embed";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return [
            "url" => $this->url
        ];
    }

    /**
     *
     */
    public function getDataForResource()
    {
        return [
            "url" => $this->url
        ];
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend()
    {
        if ($this->url) {
            $config = $this->getOptions();
            if (!isset($config["params"])) {
                $config["params"] = [];
            }

            foreach (["width", "height"] as $property) {
                if (isset($config[$property])) {
                    $config["params"][$property] = $config[$property];
                }
            }

            $cacheKey = "doc_embed_" . crc32(serialize([$this->url, $config]));

            if (!$html = \Pimcore\Cache::load($cacheKey)) {
                $embera = new \Embera\Embera($config);
                $html = $embera->autoEmbed($this->url);

                \Pimcore\Cache::save($html, $cacheKey, ["embed"], 86400, 1, true);
            }

            return $html;
        }

        return "";
    }

    /**
     * @see Document\Tag\TagInterface::admin
     * @return string
     */
    public function admin()
    {
        $html = parent::admin();

        // get frontendcode for preview
        // put the video code inside the generic code
        $html = str_replace("</div>", $this->frontend() . "</div>", $html);

        return $html;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = \Pimcore\Tool\Serialize::unserialize($data);
        }

        $this->url = $data["url"];

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        if ($data["url"]) {
            $this->url = $data["url"];
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        if ($this->url) {
            return false;
        }

        return true;
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param mixed $params
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->url) {
            $this->url = $data->url;
        }
    }
}
