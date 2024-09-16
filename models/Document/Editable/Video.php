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

use DateTime;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Video extends Model\Document\Editable implements IdRewriterInterface
{
    public const TYPE_ASSET = 'asset';

    public const TYPE_YOUTUBE = 'youtube';

    public const TYPE_VIMEO = 'vimeo';

    public const TYPE_DAILYMOTION = 'dailymotion';

    public const ALLOWED_TYPES = [
        self::TYPE_ASSET,
        self::TYPE_YOUTUBE,
        self::TYPE_VIMEO,
        self::TYPE_DAILYMOTION,
    ];

    /**
     * contains depending on the type of the video the unique identifier eg. "http://www.youtube.com", "789", ...
     *
     * @internal
     *
     */
    protected string|int|null $id = null;

    /**
     * one of self::ALLOWED_TYPES
     *
     * @internal
     *
     */
    protected ?string $type = null;

    /**
     * asset ID of poster image
     *
     * @internal
     *
     */
    protected ?int $poster = null;

    /**
     * @internal
     *
     */
    protected string $title = '';

    /**
     * @internal
     *
     */
    protected string $description = '';

    /**
     * @internal
     *
     */
    protected ?array $allowedTypes = null;

    /**
     * @return $this
     */
    public function setId(int|string|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        if (!$this->title && $this->getVideoAsset()) {
            // default title for microformats
            return $this->getVideoAsset()->getFilename();
        }

        return $this->title;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        if (!$this->description) {
            // default description for microformats
            return $this->getTitle();
        }

        return $this->description;
    }

    public function setPoster(?int $id): static
    {
        $this->poster = $id;

        return $this;
    }

    public function getPoster(): ?int
    {
        return $this->poster;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return 'video';
    }

    public function setAllowedTypes(array $allowedTypes): static
    {
        $this->allowedTypes = $allowedTypes;

        return $this;
    }

    public function getAllowedTypes(): array
    {
        if ($this->allowedTypes === null) {
            $this->updateAllowedTypesFromConfig($this->getConfig());
        }

        return $this->allowedTypes;
    }

    public function getData(): mixed
    {
        $path = $this->id;
        if ($this->id && $this->type === self::TYPE_ASSET && ($video = Asset::getById((int)$this->id))) {
            $path = $video->getFullPath();
        }

        $allowedTypes = $this->getAllowedTypes();

        if (
            empty($this->type) === true
            || in_array($this->type, $allowedTypes, true) === false
        ) {
            // Set the first type in array as default selection for dropdown
            $this->type = $allowedTypes[0];

            // Reset "id" and "path" to prevent invalid references
            $this->id   = '';
            $path       = '';
        }

        $poster = $this->poster ? Asset::getById($this->poster) : null;

        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'allowedTypes' => $allowedTypes,
            'title'        => $this->title,
            'description'  => $this->description,
            'path'         => $path,
            'poster'       => $poster ? $poster->getFullPath() : '',
        ];
    }

    protected function getDataEditmode(): mixed
    {
        $data = $this->getData();

        $poster = $this->poster ? Asset::getById($this->poster) : null;
        if ($poster) {
            $data['poster'] = $poster->getRealFullPath();
        }

        if ($this->type === self::TYPE_ASSET && ($video = Asset::getById((int)$this->id))) {
            $data['path'] = $video->getRealFullPath();
        }

        return $data;
    }

    public function getDataForResource(): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'allowedTypes' => $this->getAllowedTypes(),
            'title'        => $this->title,
            'description'  => $this->description,
            'poster'       => $this->poster,
        ];
    }

    public function frontend()
    {
        $inAdmin = false;
        $args    = func_get_args();
        if (array_key_exists(0, $args)) {
            $inAdmin = $args[0];
        }

        if (
            empty($this->id) === true
            || empty($this->type) === true
            || in_array($this->type, $this->getAllowedTypes(), true) === false
        ) {
            return $this->getEmptyCode();
        } elseif ($this->type === self::TYPE_ASSET) {
            return $this->getAssetCode($inAdmin);
        } elseif ($this->type === self::TYPE_YOUTUBE) {
            return $this->getYoutubeCode($inAdmin);
        } elseif ($this->type === self::TYPE_VIMEO) {
            return $this->getVimeoCode($inAdmin);
        } elseif ($this->type === self::TYPE_DAILYMOTION) {
            return $this->getDailymotionCode($inAdmin);
        } elseif ($this->type === 'url') {
            return $this->getUrlCode();
        }

        return $this->getEmptyCode();
    }

    public function resolveDependencies(): array
    {
        $dependencies = [];

        if (
            $this->id &&
            $this->type === self::TYPE_ASSET &&
            $asset = Asset::getById((int)$this->id)) {
            $key = 'asset_' . $asset->getId();
            $dependencies[$key] = [
                'id' => $asset->getId(),
                'type' => self::TYPE_ASSET,
            ];
        }

        if ($this->poster && $poster = Asset::getById($this->poster)) {
            $key = 'asset_' . $poster->getId();
            $dependencies[$key] = [
                'id' => $poster->getId(),
                'type' => self::TYPE_ASSET,
            ];
        }

        return $dependencies;
    }

    public function checkValidity(): bool
    {
        $valid = true;
        if ($this->type === self::TYPE_ASSET && $this->id) {
            $el = Asset::getById((int)$this->id);
            if (!$el instanceof Asset) {
                $valid = false;
                Logger::notice(
                    'Detected invalid relation, removing reference to non existent asset with id ['.$this->id.']'
                );
                $this->id   = null;
                $this->type = null;
            }
        }

        if ($this->poster && !Asset::getById($this->poster)) {
            $valid = false;
            Logger::notice(
                'Detected invalid relation, removing reference to non existent asset with id ['.$this->id.']'
            );
            $this->poster = null;
        }

        return $valid;
    }

    public function admin()
    {
        $html = parent::admin();

        // get frontendcode for preview
        // put the video code inside the generic code
        $html = str_replace('</div>', $this->frontend(true) . '</div>', $html);

        return $html;
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->id = $unserializedData['id'] ?? null;
        $this->type = $unserializedData['type'] ?? null;
        $this->poster = $unserializedData['poster'] ?? null;
        $this->title = $unserializedData['title'] ?? '';
        $this->description = $unserializedData['description'] ?? '';

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if (isset($data['type'])
            && in_array($data['type'], self::ALLOWED_TYPES, true) === true
        ) {
            $this->type = $data['type'];
        }

        if (isset($data['title'])) {
            $this->title = $data['title'];
        }

        if (isset($data['description'])) {
            $this->description = $data['description'];
        }

        // this is to be backward compatible to <= v 1.4.7
        if (isset($data['id']) && $data['id']) {
            $data['path'] = $data['id'];
        }

        $video = Asset::getByPath((string)$data['path']);
        if ($video instanceof Asset\Video) {
            $this->id = $video->getId();
        } else {
            $this->id = $data['path'];
        }

        $this->poster = null;
        $poster = Asset::getByPath($data['poster']);
        if ($poster instanceof Asset\Image) {
            $this->poster = $poster->getId();
        }

        return $this;
    }

    public function getWidth(): int|string
    {
        return $this->getConfig()['width'] ?? '100%';
    }

    private function getWidthWithUnit(): string
    {
        $width = $this->getWidth();

        if (is_numeric($width)) {
            $width .= 'px';
        }

        return $width;
    }

    private function getHeightWithUnit(): string
    {
        $height = $this->getHeight();

        if (is_numeric($height)) {
            $height .= 'px';
        }

        return $height;
    }

    public function getHeight(): int|string
    {
        return $this->getConfig()['height'] ?? 300;
    }

    private function getAssetCode(bool $inAdmin = false): string
    {
        $asset = Asset::getById((int)$this->id);
        $config = $this->getConfig();
        $thumbnailConfig = $config['thumbnail'] ?? null;

        // compatibility mode when FFMPEG is not present or no thumbnail config is given
        if (!\Pimcore\Video::isAvailable() || !$thumbnailConfig) {
            if ($asset instanceof Asset\Video && preg_match("/\.(f4v|flv|mp4)/i", $asset->getFullPath())) {
                $image = $this->getPosterThumbnailImage($asset);

                return $this->getHtml5Code(['mp4' => (string) $asset], $image);
            }

            return $this->getErrorCode('Asset is not a video, or missing thumbnail configuration');
        }

        if ($asset instanceof Asset\Video) {
            $thumbnail = $asset->getThumbnail($thumbnailConfig);
            if ($thumbnail) {
                $image = $this->getPosterThumbnailImage($asset);

                if ($inAdmin && isset($config['editmodeImagePreview']) && $config['editmodeImagePreview']) {
                    $code = '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">';
                    $code .= '<img width="' . $this->getWidth() . '" src="' . $image . '" />';
                    $code .= '</div>';

                    return $code;
                }

                if ($thumbnail['status'] === 'finished') {
                    return $this->getHtml5Code($thumbnail['formats'], $image);
                }

                if ($thumbnail['status'] === 'inprogress') {
                    // disable the output-cache if enabled
                    $cacheService = Pimcore::getContainer()->get(FullPageCacheListener::class);
                    $cacheService->disable('Video rendering in progress');

                    return $this->getProgressCode((string)$image);
                }

                return $this->getErrorCode(
                    'The video conversion failed, please see the log files in /var/log for more details.'
                );
            }

            return $this->getErrorCode("The given thumbnail doesn't exist: '" . $thumbnailConfig . "'");
        }

        return $this->getEmptyCode();
    }

    private function getPosterThumbnailImage(Asset\Video $asset): Asset\Video\ImageThumbnailInterface|Asset\Image\ThumbnailInterface
    {
        $config = $this->getConfig();
        if (!array_key_exists('imagethumbnail', $config) || empty($config['imagethumbnail'])) {
            $thumbnailConfig = $asset->getThumbnailConfig($config['thumbnail'] ?? null);

            if ($thumbnailConfig instanceof Asset\Video\Thumbnail\Config) {
                // try to get the dimensions out ouf the video thumbnail
                $imageThumbnailConf = $thumbnailConfig->getEstimatedDimensions();
                $imageThumbnailConf['format'] = 'JPEG';
            }
        } else {
            $imageThumbnailConf = $config['imagethumbnail'];
        }

        if (empty($imageThumbnailConf)) {
            $imageThumbnailConf['width'] = 800;
            $imageThumbnailConf['format'] = 'JPEG';
        }

        if ($this->poster && ($poster = Asset\Image::getById($this->poster))) {
            return $poster->getThumbnail($imageThumbnailConf);
        }
        if (
            $asset->getCustomSetting('image_thumbnail_asset') &&
            ($customPreviewAsset = Asset\Image::getById($asset->getCustomSetting('image_thumbnail_asset')))
        ) {
            return $customPreviewAsset->getThumbnail($imageThumbnailConf);
        }

        return $asset->getImageThumbnail($imageThumbnailConf);
    }

    private function getUrlCode(): string
    {
        return $this->getHtml5Code(['mp4' => (string) $this->id]);
    }

    private function getErrorCode(string $message = ''): string
    {
        $width = $this->getWidth();
        // If contains at least one digit (0-9), then assume it is a value that can be calculated,
        // otherwise it is likely be `auto`,`inherit`,etc..
        if (preg_match('/[\d]/', (string) $width)) {
            // when is numeric, assume there are no length units nor %, and considering the value as pixels
            if (is_numeric($width)) {
                $width .= 'px';
            }
            $width = 'calc(' . $width . ' - 1px)';
        }

        $height = $this->getHeight();
        if (preg_match('/[\d]/', (string) $height)) {
            if (is_numeric($height)) {
                $height .= 'px';
            }
            $height = 'calc(' . $height . ' - 1px)';
        }

        // only display error message in debug mode
        if (!Pimcore::inDebugMode()) {
            $message = '';
        }

        $code = '
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video">
            <div class="pimcore_editable_video_error" style="text-align:center; width: ' . $width . '; height: ' . $height . '; border:1px solid #000; background: url(/bundles/pimcoreadmin/img/filetype-not-supported.svg) no-repeat center center #fff;">
                ' . $message . '
            </div>
        </div>';

        return $code;
    }

    private function parseYoutubeId(): string
    {
        $youtubeId = '';
        if ($this->type === self::TYPE_YOUTUBE) {
            if ($youtubeId = $this->id) {
                if (str_contains($youtubeId, '//')) {
                    $parts = parse_url($this->id);
                    if (array_key_exists('query', $parts)) {
                        parse_str($parts['query'], $vars);

                        if (isset($vars['v']) && $vars['v']) {
                            $youtubeId = $vars['v'];
                        }
                    }

                    //get youtube id if form urls like  http://www.youtube.com/embed/youtubeId
                    if (str_contains($this->id, 'embed')) {
                        $explodedPath = explode('/', $parts['path']);
                        $youtubeId = $explodedPath[array_search('embed', $explodedPath) + 1];
                    }

                    if (isset($parts['host']) && $parts['host'] === 'youtu.be') {
                        $youtubeId = trim($parts['path'], ' /');
                    }
                }
            }
        }

        return $youtubeId;
    }

    private function getYoutubeCode(bool $inAdmin = false): string
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $config = $this->getConfig();
        $code = '';

        $youtubeId = $this->parseYoutubeId();
        if (!$youtubeId) {
            return $this->getEmptyCode();
        }

        if ($inAdmin && isset($config['editmodeImagePreview']) && $config['editmodeImagePreview'] === true) {
            return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                <img src="https://img.youtube.com/vi/' . $youtubeId . '/0.jpg">
            </div>';
        }

        $width = '100%';
        if (array_key_exists('width', $config)) {
            $width = $config['width'];
        }

        $height = '300';
        if (array_key_exists('height', $config)) {
            $height = $config['height'];
        }

        $wmode = '?wmode=transparent';
        $seriesPrefix = '';
        if (str_starts_with($youtubeId, 'PL')) {
            $wmode = '';
            $seriesPrefix = 'videoseries?list=';
        }

        //todo: move this to symfony config
        $validYoutubeParams = [
            'autohide',
            'autoplay',
            'cc_load_policy',
            'color',
            'controls',
            'disablekb',
            'enablejsapi',
            'end',
            'fs',
            'playsinline',
            'hl',
            'iv_load_policy',
            'list',
            'listType',
            'loop',
            'modestbranding',
            'mute',
            'origin',
            'playerapiid',
            'playlist',
            'rel',
            'showinfo',
            'start',
            'theme',
        ];
        $additionalParams = '';

        $clipConfig = [];
        if (isset($config['config']['clip']) && is_array($config['config']['clip'])) {
            $clipConfig = $config['config']['clip'];
        }

        // this is to be backward compatible to <= v 1.4.7
        $configurations = $clipConfig;
        if (array_key_exists(self::TYPE_YOUTUBE, $config) && is_array($config[self::TYPE_YOUTUBE])) {
            $configurations = array_merge($clipConfig, $config[self::TYPE_YOUTUBE]);
        }

        if (!empty($configurations)) {
            foreach ($configurations as $key => $value) {
                if (in_array($key, $validYoutubeParams)) {
                    if (is_bool($value)) {
                        if ($value) {
                            $additionalParams .= '&'.$key.'=1';
                        } else {
                            $additionalParams .= '&'.$key.'=0';
                        }
                    } else {
                        $additionalParams .= '&'.$key.'='.$value;
                    }
                }
            }
        }

        $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
            <iframe width="' . $width . '" height="' . $height . '" src="https://www.youtube-nocookie.com/embed/' . $seriesPrefix . $youtubeId . $wmode . $additionalParams . '" title="YouTube video" allow="fullscreen" data-type="pimcore_video_editable"></iframe>
        </div>';

        return $code;
    }

    private function getVimeoCode(bool $inAdmin = false): string
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $config = $this->getConfig();
        $code = '';

        // get vimeo id
        if (preg_match("@vimeo.*/([\d]+)@i", $this->id, $matches)) {
            $vimeoId = (int)$matches[1];
        } else {
            // for object-videos
            $vimeoId = $this->id;
        }

        if (ctype_digit($vimeoId)) {
            if ($inAdmin && isset($config['editmodeImagePreview']) && $config['editmodeImagePreview'] === true) {
                return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                    <img src="https://vumbnail.com/' . $vimeoId . '.jpg">
                </div>';
            }

            $width = '100%';
            if (array_key_exists('width', $config)) {
                $width = $config['width'];
            }

            $height = '300';
            if (array_key_exists('height', $config)) {
                $height = $config['height'];
            }

            //todo: move this to symfony config
            $validVimeoParams = [
                'autoplay',
                'background',
                'loop',
                'muted',
            ];

            $additionalParams = '';

            $clipConfig = [];
            if (isset($config['config']['clip']) && is_array($config['config']['clip'])) {
                $clipConfig = $config['config']['clip'];
            }

            // this is to be backward compatible to <= v 1.4.7
            $configurations = $clipConfig;
            if (isset($config[self::TYPE_VIMEO]) && is_array($config[self::TYPE_VIMEO])) {
                $configurations = array_merge($clipConfig, $config[self::TYPE_VIMEO]);
            }

            if (!empty($configurations)) {
                foreach ($configurations as $key => $value) {
                    if (in_array($key, $validVimeoParams)) {
                        if (is_bool($value)) {
                            if ($value) {
                                $additionalParams .= '&'.$key.'=1';
                            } else {
                                $additionalParams .= '&'.$key.'=0';
                            }
                        } else {
                            $additionalParams .= '&'.$key.'='.$value;
                        }
                    }
                }
            }

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                <iframe src="https://player.vimeo.com/video/' . $vimeoId . '?dnt=1&title=0&amp;byline=0&amp;portrait=0' . $additionalParams . '" width="' . $width . '" height="' . $height . '" title="Vimeo video" allow="fullscreen" data-type="pimcore_video_editable"></iframe>
            </div>';

            return $code;
        }

        // default => return the empty code
        return $this->getEmptyCode();
    }

    private function getDailymotionCode(bool $inAdmin = false): string
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $config = $this->getConfig();
        $code = '';

        // get dailymotion id
        if (preg_match('@dailymotion.*/video/([^_]+)@i', $this->id, $matches)) {
            $dailymotionId = $matches[1];
        } else {
            // for object-videos
            $dailymotionId = $this->id;
        }

        if ($dailymotionId) {
            if ($inAdmin && isset($config['editmodeImagePreview']) && $config['editmodeImagePreview'] === true) {
                return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                    <img src="https://www.dailymotion.com/thumbnail/video/' . $dailymotionId . '">
                </div>';
            }
            $width = $config['width'] ?? '100%';

            $height = $config['height'] ?? '300';

            //todo: move this to symfony config
            $validDailymotionParams = [
                'autoplay',
                'loop',
                'mute', ];

            $additionalParams = '';

            $clipConfig =
                isset($config['config']['clip']) &&
                is_array($config['config']['clip']) ? $config['config']['clip'] : [];

            // this is to be backward compatible to <= v 1.4.7
            $configurations = $clipConfig;
            if (isset($config[self::TYPE_DAILYMOTION]) && is_array($config[self::TYPE_DAILYMOTION])) {
                $configurations = array_merge($clipConfig, $config[self::TYPE_DAILYMOTION]);
            }

            if (!empty($configurations)) {
                foreach ($configurations as $key => $value) {
                    if (in_array($key, $validDailymotionParams)) {
                        if (is_bool($value)) {
                            if ($value) {
                                $additionalParams .= '&'.$key.'=1';
                            } else {
                                $additionalParams .= '&'.$key.'=0';
                            }
                        } else {
                            $additionalParams .= '&'.$key.'='.$value;
                        }
                    }
                }
            }

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                <iframe src="https://www.dailymotion.com/embed/video/' . $dailymotionId . '?' . $additionalParams . '" width="' . $width . '" height="' . $height . '" title="DailyMotion video" allow="fullscreen" data-type="pimcore_video_editable"></iframe>
            </div>';

            return $code;
        }

        // default => return the empty code
        return $this->getEmptyCode();
    }

    private function getHtml5Code(
        array $urls = [],
        Asset\Video\ImageThumbnailInterface|Asset\Image\ThumbnailInterface $thumbnail = null
    ): string {
        $code = '';
        $video = $this->getVideoAsset();
        if ($video) {
            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video">' . "\n";

            $uploadDate = new DateTime();
            $uploadDate->setTimestamp($video->getCreationDate());

            $jsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $this->getTitle(),
                'description' => $this->getDescription(),
                'uploadDate' => $uploadDate->format('Y-m-d\TH:i:sO'),
            ];
            $duration = $video->getDuration();

            if ($duration !== null) {
                $jsonLd['duration'] = $this->getDurationString($duration);
            }

            if (!$thumbnail) {
                $thumbnail = $video->getImageThumbnail([]);
            }

            $jsonLd['contentUrl'] = $urls['mp4'];
            if (!preg_match('@https?://@', $urls['mp4'])) {
                $jsonLd['contentUrl'] = Tool::getHostUrl() . $urls['mp4'];
            }

            $thumbnailUrl = (string)$thumbnail;
            $jsonLd['thumbnailUrl'] = $thumbnailUrl;
            if (!preg_match('@https?://@', $thumbnailUrl)) {
                $jsonLd['thumbnailUrl'] = Tool::getHostUrl() . $thumbnailUrl;
            }

            $code .= "\n\n<script type=\"application/ld+json\">\n" . json_encode($jsonLd) . "\n</script>\n\n";

            // default attributes
            $attributesString = '';
            $attributes = [
                'width' => $this->getWidth(),
                'height' => $this->getHeight(),
                'poster' => $thumbnailUrl,
                'controls' => 'controls',
                'class' => 'pimcore_video',
            ];

            $config = $this->getConfig();

            if (array_key_exists('attributes', $config)) {
                $attributes = array_merge($attributes, $config['attributes']);
            }

            if (isset($config['removeAttributes']) && is_array($config['removeAttributes'])) {
                foreach ($config['removeAttributes'] as $attribute) {
                    unset($attributes[$attribute]);
                }
            }

            // do not allow an empty controls editable
            if (isset($attributes['controls']) && !$attributes['controls']) {
                unset($attributes['controls']);
            }

            if (isset($urls['mpd'])) {
                $attributes['data-dashjs-player'] = null;
            }

            foreach ($attributes as $key => $value) {
                $attributesString .= ' ' . $key;
                if (!empty($value)) {
                    $quoteChar = '"';
                    if (is_string($value) && strpos($value, '"')) {
                        $quoteChar = "'";
                    }
                    $attributesString .= '=' . $quoteChar . $value . $quoteChar;
                }
            }

            $code .= '<video' . $attributesString . '>' . "\n";

            foreach ($urls as $type => $url) {
                if ($type == 'medias') {
                    foreach ($url as $format => $medias) {
                        foreach ($medias as $media => $mediaUrl) {
                            $code .= '<source type="video/' . $format . '" src="' . $mediaUrl . '" media="' . $media . '"  />' . "\n";
                        }
                    }
                } else {
                    $code .= '<source type="video/' . $type . '" src="' . $url . '" />' . "\n";
                }
            }

            $code .= '</video>' . "\n";
            $code .= '</div>' . "\n";
        }

        return $code;
    }

    private function getDurationString(float $duration): string
    {
        $duration = ceil($duration);
        $durationParts = ['PT'];

        // hours
        if ($duration / 3600 >= 1) {
            $hours = floor($duration / 3600);
            $durationParts[] = $hours . 'H';
            $duration = $duration - $hours * 3600;
        }

        // minutes
        if ($duration / 60 >= 1) {
            $minutes = floor($duration / 60);
            $durationParts[] = $minutes . 'M';
            $duration = $duration - $minutes * 60;
        }

        $durationParts[] = $duration . 'S';

        return implode('', $durationParts);
    }

    private function getProgressCode(string $thumbnail = null): string
    {
        $uid = $this->getUniqId();
        $code = '
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video">
            <style type="text/css">
                #' . $uid . ' .pimcore_editable_video_progress_status {
                    box-sizing:content-box;
                    background:#fff url(/bundles/pimcoreadmin/img/video-loading.gif) center center no-repeat;
                    width:66px;
                    height:66px;
                    padding:20px;
                    border:1px solid #555;
                    box-shadow: 2px 2px 5px #333;
                    border-radius:20px;
                    margin: 0 20px 0 20px;
                    top: calc(50% - 66px);
                    left: calc(50% - 66px);
                    position:absolute;
                    opacity: 0.8;
                }
            </style>
            <div class="pimcore_editable_video_progress" id="' . $uid . '">
                <img src="' . $thumbnail . '" style="width: ' . $this->getWidthWithUnit() . '; height: ' . $this->getHeightWithUnit() . ';">
                <div class="pimcore_editable_video_progress_status"></div>
            </div>
        </div>';

        return $code;
    }

    private function getEmptyCode(): string
    {
        $uid = 'video_' . uniqid();

        return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video"><div class="pimcore_editable_video_empty" id="' . $uid . '" style="width: ' . $this->getWidthWithUnit() . '; height: ' . $this->getHeightWithUnit() . ';"></div></div>';
    }

    private function updateAllowedTypesFromConfig(array $config): void
    {
        $this->allowedTypes = self::ALLOWED_TYPES;

        if (
            isset($config['allowedTypes']) === true
            && empty($config['allowedTypes']) === false
            && empty(array_diff($config['allowedTypes'], self::ALLOWED_TYPES))
        ) {
            $this->allowedTypes = $config['allowedTypes'];
        }
    }

    public function getUniqId(): string
    {
        return 'video_' . uniqid();
    }

    public function isEmpty(): bool
    {
        if ($this->id) {
            return false;
        }

        return true;
    }

    public function getVideoType(): string
    {
        if (empty($this->type) === true) {
            $this->type = $this->getAllowedTypes()[0];
        }

        return $this->type;
    }

    public function getVideoAsset(): ?Asset\Video
    {
        if ($this->id && $this->getVideoType() === self::TYPE_ASSET) {
            return Asset\Video::getById((int)$this->id);
        }

        return null;
    }

    public function getPosterAsset(): ?Asset\Image
    {
        if ($this->poster) {
            return Asset\Image::getById($this->poster);
        }

        return null;
    }

    /**
     * TODO Pimcore 12: Change empty string return to null
     */
    public function getImageThumbnail(string|Asset\Video\Thumbnail\Config $config): Asset\Video\ImageThumbnailInterface|Asset\Image\ThumbnailInterface|string
    {
        if ($this->poster && ($poster = Asset\Image::getById($this->poster))) {
            return $poster->getThumbnail($config);
        }

        if ($this->getVideoAsset()) {
            return $this->getVideoAsset()->getImageThumbnail($config);
        }

        return '';
    }

    public function getThumbnail(string|Asset\Video\Thumbnail\Config $config): array
    {
        if ($this->getVideoAsset()) {
            return $this->getVideoAsset()->getThumbnail($config);
        }

        return [];
    }

    public function rewriteIds(array $idMapping): void
    {
        if (
            $this->type == self::TYPE_ASSET &&
            array_key_exists(self::TYPE_ASSET, $idMapping) &&
            array_key_exists($this->getId(), $idMapping[self::TYPE_ASSET])) {
            $this->setId($idMapping[self::TYPE_ASSET][$this->getId()]);
        }
    }
}
