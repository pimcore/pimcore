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

namespace Pimcore\Model\Document\Editable;

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
     * @var int|string|null
     */
    protected $id;

    /**
     * one of self::ALLOWED_TYPES
     *
     * @internal
     *
     * @var string|null
     */
    protected $type;

    /**
     * asset ID of poster image
     *
     * @internal
     *
     * @var int|null
     */
    protected $poster;

    /**
     * @internal
     *
     * @var string
     */
    protected $title = '';

    /**
     * @internal
     *
     * @var string
     */
    protected $description = '';

    /**
     * @internal
     *
     * @var array
     */
    protected $allowedTypes;

    /**
     * @param int|string $id
     *
     * @return Video
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if (!$this->title && $this->getVideoAsset()) {
            // default title for microformats
            return $this->getVideoAsset()->getFilename();
        }

        return $this->title;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        if (!$this->description) {
            // default description for microformats
            return $this->getTitle();
        }

        return $this->description;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setPoster($id)
    {
        $this->poster = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPoster()
    {
        return $this->poster;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'video';
    }

    /**
     * @param array $allowedTypes
     *
     * @return $this
     */
    public function setAllowedTypes($allowedTypes)
    {
        $this->allowedTypes = $allowedTypes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $path = $this->id;
        if ($this->type === self::TYPE_ASSET && ($video = Asset::getById($this->id))) {
            $path = $video->getFullPath();
        }

        $this->updateAllowedTypesFromConfig($this->getConfig());

        if (
            empty($this->type) === true
            || in_array($this->type, $this->allowedTypes, true) === false
        ) {
            // Set the first type in array as default selection for dropdown
            $this->type = $this->allowedTypes[0];

            // Reset "id" and "path" to prevent invalid references
            $this->id   = '';
            $path       = '';
        }

        $poster = Asset::getById($this->poster);

        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'allowedTypes' => $this->allowedTypes,
            'title'        => $this->title,
            'description'  => $this->description,
            'path'         => $path,
            'poster'       => $poster ? $poster->getFullPath() : '',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForResource()
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'allowedTypes' => $this->allowedTypes,
            'title'        => $this->title,
            'description'  => $this->description,
            'poster'       => $this->poster,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        $inAdmin = false;
        $args    = func_get_args();
        if (array_key_exists(0, $args)) {
            $inAdmin = $args[0];
        }

        $this->updateAllowedTypesFromConfig($this->getConfig());

        if (
            empty($this->id) === true
            || empty($this->type) === true
            || in_array($this->type, $this->allowedTypes, true) === false
        ) {
            return $this->getEmptyCode();
        } elseif ($this->type === self::TYPE_ASSET) {
            return $this->getAssetCode($inAdmin);
        } elseif ($this->type === self::TYPE_YOUTUBE) {
            return $this->getYoutubeCode();
        } elseif ($this->type === self::TYPE_VIMEO) {
            return $this->getVimeoCode();
        } elseif ($this->type === self::TYPE_DAILYMOTION) {
            return $this->getDailymotionCode();
        } elseif ($this->type === 'url') {
            return $this->getUrlCode();
        }

        return $this->getEmptyCode();
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDependencies()
    {
        $dependencies = [];

        if ($this->type === self::TYPE_ASSET) {
            $asset = Asset::getById($this->id);
            if ($asset instanceof Asset) {
                $key = 'asset_' . $asset->getId();
                $dependencies[$key] = [
                    'id' => $asset->getId(),
                    'type' => self::TYPE_ASSET,
                ];
            }
        }

        if ($poster = Asset::getById($this->poster)) {
            $key = 'asset_' . $poster->getId();
            $dependencies[$key] = [
                'id' => $poster->getId(),
                'type' => self::TYPE_ASSET,
            ];
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity()
    {
        $valid = true;
        if ($this->type === self::TYPE_ASSET && !empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $valid = false;
                Logger::notice('Detected invalid relation, removing reference to non existent asset with id ['.$this->id.']');
                $this->id   = null;
                $this->type = null;
            }
        }

        if (!($poster = Asset::getById($this->poster))) {
            $valid = false;
            Logger::notice('Detected invalid relation, removing reference to non existent asset with id ['.$this->id.']');
            $this->poster = null;
        }

        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function admin()
    {
        $html = parent::admin();

        // get frontendcode for preview
        // put the video code inside the generic code
        $html = str_replace('</div>', $this->frontend(true) . '</div>', $html);

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

        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->poster = $data['poster'];
        $this->title = $data['title'];
        $this->description = $data['description'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
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

        $video = Asset::getByPath($data['path']);
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

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->getConfig()['width'] ?? '100%';
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->getConfig()['height'] ?? 300;
    }

    /**
     * @param bool $inAdmin
     *
     * @return string
     */
    private function getAssetCode($inAdmin = false)
    {
        $asset = Asset::getById($this->id);
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
                    $cacheService = \Pimcore::getContainer()->get(FullPageCacheListener::class);
                    $cacheService->disable('Video rendering in progress');

                    return $this->getProgressCode($image);
                }

                return $this->getErrorCode('The video conversion failed, please see the log files in /var/log for more details.');
            }

            return $this->getErrorCode("The given thumbnail doesn't exist: '" . $thumbnailConfig . "'");
        }

        return $this->getEmptyCode();
    }

    /**
     * @param Asset\Video $asset
     *
     * @return Asset\Image\Thumbnail|Asset\Video\ImageThumbnail
     */
    private function getPosterThumbnailImage(Asset\Video $asset)
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

    /**
     * @return string
     */
    private function getUrlCode()
    {
        return $this->getHtml5Code(['mp4' => (string) $this->id]);
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function getErrorCode($message = '')
    {
        $width = $this->getWidth();
        if (strpos($this->getWidth(), '%') === false) {
            $width = ((int)$this->getWidth() - 1) . 'px';
        }

        // only display error message in debug mode
        if (!\Pimcore::inDebugMode()) {
            $message = '';
        }

        $code = '
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video">
            <div class="pimcore_editable_video_error" style="text-align:center; width: ' . $width . '; height: ' . ($this->getHeight() - 1) . 'px; border:1px solid #000; background: url(/bundles/pimcoreadmin/img/filetype-not-supported.svg) no-repeat center center #fff;">
                ' . $message . '
            </div>
        </div>';

        return $code;
    }

    /**
     * @return mixed|string
     */
    private function parseYoutubeId()
    {
        $youtubeId = '';
        if ($this->type === self::TYPE_YOUTUBE) {
            if ($youtubeId = $this->id) {
                if (strpos($youtubeId, '//') !== false) {
                    $parts = parse_url($this->id);
                    if (array_key_exists('query', $parts)) {
                        parse_str($parts['query'], $vars);

                        if ($vars['v']) {
                            $youtubeId = $vars['v'];
                        }
                    }

                    //get youtube id if form urls like  http://www.youtube.com/embed/youtubeId
                    if (strpos($this->id, 'embed') !== false) {
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

    /**
     * @return string
     */
    private function getYoutubeCode()
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
        if (strpos($youtubeId, 'PL') === 0) {
            $wmode = '';
            $seriesPrefix = 'videoseries?list=';
        }

        $valid_youtube_prams = [ 'autohide',
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
        $additional_params = '';

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
                if (in_array($key, $valid_youtube_prams)) {
                    if (is_bool($value)) {
                        if ($value) {
                            $additional_params .= '&'.$key.'=1';
                        } else {
                            $additional_params .= '&'.$key.'=0';
                        }
                    } else {
                        $additional_params .= '&'.$key.'='.$value;
                    }
                }
            }
        }

        $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
            <iframe width="' . $width . '" height="' . $height . '" src="https://www.youtube-nocookie.com/embed/' . $seriesPrefix . $youtubeId . $wmode . $additional_params .'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen data-type="pimcore_video_editable"></iframe>
        </div>';

        return $code;
    }

    /**
     * @return string
     */
    private function getVimeoCode()
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $config = $this->getConfig();
        $code = '';
        $uid = 'video_' . uniqid();

        // get vimeo id
        if (preg_match("@vimeo.*/([\d]+)@i", $this->id, $matches)) {
            $vimeoId = (int)$matches[1];
        } else {
            // for object-videos
            $vimeoId = $this->id;
        }

        if (ctype_digit($vimeoId)) {
            $width = '100%';
            if (array_key_exists('width', $config)) {
                $width = $config['width'];
            }

            $height = '300';
            if (array_key_exists('height', $config)) {
                $height = $config['height'];
            }

            $valid_vimeo_prams = [
                'autoplay',
                'background',
                'loop',
                'muted',
                ];

            $additional_params = '';

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
                    if (in_array($key, $valid_vimeo_prams)) {
                        if (is_bool($value)) {
                            if ($value) {
                                $additional_params .= '&'.$key.'=1';
                            } else {
                                $additional_params .= '&'.$key.'=0';
                            }
                        } else {
                            $additional_params .= '&'.$key.'='.$value;
                        }
                    }
                }
            }

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                <iframe src="https://player.vimeo.com/video/' . $vimeoId . '?dnt=1&title=0&amp;byline=0&amp;portrait=0'. $additional_params .'" width="' . $width . '" height="' . $height . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
            </div>';

            return $code;
        }

        // default => return the empty code
        return $this->getEmptyCode();
    }

    /**
     * @return string
     */
    private function getDailymotionCode()
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $config = $this->getConfig();
        $code = '';
        $uid = 'video_' . uniqid();

        // get dailymotion id
        if (preg_match('@dailymotion.*/video/([^_]+)@i', $this->id, $matches)) {
            $dailymotionId = $matches[1];
        } else {
            // for object-videos
            $dailymotionId = $this->id;
        }

        if ($dailymotionId) {
            $width = $config['width'] ?? '100%';

            $height = $config['height'] ?? '300';

            $valid_dailymotion_prams = [
                'autoplay',
                'loop',
                'mute', ];

            $additional_params = '';

            $clipConfig = isset($config['config']['clip']) && is_array($config['config']['clip']) ? $config['config']['clip'] : [];

            // this is to be backward compatible to <= v 1.4.7
            $configurations = $clipConfig;
            if (isset($config[self::TYPE_DAILYMOTION]) && is_array($config[self::TYPE_DAILYMOTION])) {
                $configurations = array_merge($clipConfig, $config[self::TYPE_DAILYMOTION]);
            }

            if (!empty($configurations)) {
                foreach ($configurations as $key => $value) {
                    if (in_array($key, $valid_dailymotion_prams)) {
                        if (is_bool($value)) {
                            if ($value) {
                                $additional_params .= '&'.$key.'=1';
                            } else {
                                $additional_params .= '&'.$key.'=0';
                            }
                        } else {
                            $additional_params .= '&'.$key.'='.$value;
                        }
                    }
                }
            }

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video '. ($config['class'] ?? '') .'">
                <iframe src="https://www.dailymotion.com/embed/video/' . $dailymotionId . '?' . $additional_params .'" width="' . $width . '" height="' . $height . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
            </div>';

            return $code;
        }

        // default => return the empty code
        return $this->getEmptyCode();
    }

    /**
     * @param array $urls
     * @param Asset\Image\Thumbnail|Asset\Video\ImageThumbnail|null $thumbnail
     *
     * @return string
     */
    private function getHtml5Code($urls = [], $thumbnail = null)
    {
        $code = '';
        $video = $this->getVideoAsset();
        if ($video) {
            $duration = ceil($video->getDuration());

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
            $durationString = implode('', $durationParts);

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video">' . "\n";

            $uploadDate = new \DateTime();
            $uploadDate->setTimestamp($video->getCreationDate());

            $jsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $this->getTitle(),
                'description' => $this->getDescription(),
                'uploadDate' => $uploadDate->format('Y-m-d\TH:i:sO'),
                'duration' => $durationString,
                //'contentUrl' => Tool::getHostUrl() . $urls['mp4'],
                //"embedUrl" => "http://www.example.com/videoplayer.swf?video=123",
                //"interactionCount" => "1234",
            ];

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
                    if (strpos($value, '"')) {
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

    /**
     * @param string|null $thumbnail
     *
     * @return string
     */
    private function getProgressCode($thumbnail = null)
    {
        $uid = 'video_' . uniqid();
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
                <img src="' . $thumbnail . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;">
                <div class="pimcore_editable_video_progress_status"></div>
            </div>
        </div>';

        return $code;
    }

    /**
     * @return string
     */
    private function getEmptyCode()
    {
        $uid = 'video_' . uniqid();

        return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_editable_video"><div class="pimcore_editable_video_empty" id="' . $uid . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;"></div></div>';
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

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        if ($this->id) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getVideoType()
    {
        if (empty($this->type) === true) {
            $this->type = $this->allowedTypes[0];
        }

        return $this->type;
    }

    /**
     * @return Asset\Video|null
     */
    public function getVideoAsset()
    {
        if ($this->getVideoType() === self::TYPE_ASSET) {
            return Asset\Video::getById($this->id);
        }

        return null;
    }

    /**
     * @return Asset\Image
     */
    public function getPosterAsset()
    {
        return Asset\Image::getById($this->poster);
    }

    /**
     * @param string|Asset\Video\Thumbnail\Config $config
     *
     * @return string
     */
    public function getImageThumbnail($config)
    {
        if ($this->poster && ($poster = Asset\Image::getById($this->poster))) {
            return $poster->getThumbnail($config);
        }

        if ($this->getVideoAsset()) {
            return $this->getVideoAsset()->getImageThumbnail($config);
        }

        return '';
    }

    /**
     * @param string|Asset\Video\Thumbnail\Config $config
     *
     * @return array
     */
    public function getThumbnail($config)
    {
        if ($this->getVideoAsset()) {
            return $this->getVideoAsset()->getThumbnail($config);
        }

        return [];
    }

    /**
     * { @inheritdoc }
     */
    public function rewriteIds($idMapping) /** : void */
    {
        if ($this->type == self::TYPE_ASSET && array_key_exists(self::TYPE_ASSET, $idMapping) && array_key_exists($this->getId(), $idMapping[self::TYPE_ASSET])) {
            $this->setId($idMapping[self::TYPE_ASSET][$this->getId()]);
        }
    }
}
