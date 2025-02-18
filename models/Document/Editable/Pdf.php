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

use Pimcore;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Pdf extends Model\Document\Editable implements EditmodeDataInterface
{
    /**
     * @internal
     *
     */
    protected ?int $id = null;

    public function getType(): string
    {
        return 'pdf';
    }

    public function getData(): mixed
    {
        return [
            'id' => $this->id,
        ];
    }

    public function getDataForResource(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    public function getDataEditmode(): array
    {
        $pages = 0;

        if ($this->id && $asset = Asset\Document::getById($this->id)) {
            $pages = $asset->getPageCount();
        }

        return [
            'id' => $this->id,
            'pageCount' => $pages,
        ];
    }

    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        $asset = $this->id ? Asset::getById($this->id) : null;
        if ($asset instanceof Asset) {
            if (!array_key_exists($asset->getCacheTag(), $tags)) {
                $tags = $asset->getCacheTags($tags);
            }
        }

        return $tags;
    }

    public function resolveDependencies(): array
    {
        $dependencies = [];

        $asset = $this->id ? Asset::getById($this->id) : null;
        if ($asset instanceof Asset) {
            $key = 'asset_' . $asset->getId();
            $dependencies[$key] = [
                'id' => $asset->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    public function checkValidity(): bool
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

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->id = $unserializedData['id'] ?? null;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $pdf = $data['id'] ? Asset::getById($data['id']) : null;
        if ($pdf instanceof Asset\Document) {
            $this->id = $pdf->getId();
        }

        return $this;
    }

    public function frontend()
    {
        $asset = $this->id ? Asset::getById($this->id) : null;

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

    private function getErrorCode(string $message = ''): string
    {
        // only display error message in debug mode
        if (!Pimcore::inDebugMode()) {
            $message = '';
        }

        $code = '
        <div id="pimcore_pdf_' . $this->getName() . '" class="pimcore_editable_pdf">
            <div class="pimcore_editable_video_error" style="line-height: 50px; text-align:center; width: 100%; min-height: 50px; background: #ececec;">
                ' . $message . '
            </div>
        </div>';

        return $code;
    }

    public function isEmpty(): bool
    {
        if ($this->id) {
            return false;
        }

        return true;
    }

    public function getElement(): ?Asset
    {
        $data = $this->getData();

        return Asset::getById($data['id']);
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
