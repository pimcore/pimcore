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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Pdf extends Model\Document\Editable
{
    /**
     * @var int|null
     */
    public $id;

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'pdf';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return [
            'id' => $this->id,
        ];
    }

    /**
     * @return array
     */
    public function getDataForResource()
    {
        return [
            'id' => $this->id,
        ];
    }

    /**
     * @return array
     */
    public function getDataEditmode()
    {
        $pages = 0;

        if ($asset = Asset\Document::getById($this->id)) {
            $pages = $asset->getPageCount();
        }

        return [
            'id' => $this->id,
            'pageCount' => $pages,
        ];
    }

    /**
     * @param Model\Document\PageSnippet $ownerDocument
     * @param array $tags
     *
     * @return array|mixed
     */
    public function getCacheTags($ownerDocument, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        $asset = Asset::getById($this->id);
        if ($asset instanceof Asset) {
            if (!array_key_exists($asset->getCacheTag(), $tags)) {
                $tags = $asset->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [];

        $asset = Asset::getById($this->id);
        if ($asset instanceof Asset) {
            $key = 'asset_' . $asset->getId();
            $dependencies[$key] = [
                'id' => $asset->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if (!empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent asset with id [' . $this->id . ']');
                $this->id = null;
            }
        }

        return $sane;
    }

    /**
     * @see EditableInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = \Pimcore\Tool\Serialize::unserialize($data);
        }

        $this->id = $data['id'];

        return $this;
    }

    /**
     * @return bool
     */
    public function getEditmode()
    {
        return parent::getEditmode();
    }

    /**
     * @see EditableInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $pdf = Asset::getById($data['id']);
        if ($pdf instanceof Asset\Document) {
            $this->id = $pdf->getId();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function frontend()
    {
        $asset = Asset::getById($this->id);

        $config = $this->getConfig();
        $thumbnailConfig = ['width' => 1000];
        if (isset($config['thumbnail'])) {
            $thumbnailConfig = $config['thumbnail'];
        }

        if ($asset instanceof Asset\Document && $asset->getPageCount()) {
            $divId = 'pimcore-pdf-' . uniqid();
            $pdfPath = $asset->getFullPath();
            $thumbnailPath = $asset->getImageThumbnail($thumbnailConfig, 1, true);

            $code = <<<HTML
            <div id="$divId" class="pimcore-pdfViewer">
                <a href="$pdfPath" target="_blank"><img src="$thumbnailPath"></a>
            </div>
HTML;

            return $code;
        } else {
            return $this->getErrorCode('Preview in progress or not a valid PDF file');
        }
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public function getErrorCode($message = '')
    {
        // only display error message in debug mode
        if (!\Pimcore::inDebugMode()) {
            $message = '';
        }

        $code = '
        <div id="pimcore_pdf_' . $this->getName() . '" class="pimcore_tag_pdf pimcore_editable_pdf">
            <div class="pimcore_tag_video_error pimcore_editable_video_error" style="line-height: 50px; text-align:center; width: 100%; min-height: 50px; background: #ececec;">
                ' . $message . '
            </div>
        </div>';

        return $code;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if ($this->id) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated
     *
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param Model\Document\PageSnippet $document
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $this->sanitizeWebserviceData($wsElement->value);
        if ($data->id) {
            $asset = Asset::getById($data->id);
            if (!$asset) {
                throw new \Exception('Referencing unknown asset with id [ '.$data->id.' ] in webservice import field [ '.$data->name.' ]');
            } else {
                $this->id = $data->id;
            }
        }
    }

    /**
     * @return Asset
     */
    public function getElement()
    {
        $data = $this->getData();

        return Asset::getById($data['id']);
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)  $this->id;
    }
}

class_alias(Pdf::class, 'Pimcore\Model\Document\Tag\Pdf');
