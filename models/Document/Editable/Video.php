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
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Video extends Model\Document\Editable
{
    /**
     * contains depending on the type of the video the unique identifier eg. "http://www.youtube.com", "789", ...
     *
     * @var int|string|null
     */
    public $id;

    /**
     * one of asset, youtube, vimeo, dailymotion
     *
     * @var string|null
     */
    public $type = 'asset';

    /**
     * asset ID of poster image
     *
     * @var int|null
     */
    public $poster;

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $description = '';

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
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'video';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        $path = $this->id;
        if ($this->type == 'asset' && ($video = Asset::getById($this->id))) {
            $path = $video->getFullPath();
        }

        $poster = Asset::getById($this->poster);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'path' => $path,
            'poster' => $poster ? $poster->getFullPath() : '',
        ];
    }

    /**
     * @return array
     */
    public function getDataForResource()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'poster' => $this->poster,
        ];
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $inAdmin = false;
        $args = func_get_args();
        if (array_key_exists(0, $args)) {
            $inAdmin = $args[0];
        }

        if (!$this->id || !$this->type) {
            return $this->getEmptyCode();
        } elseif ($this->type == 'asset') {
            return $this->getAssetCode($inAdmin);
        } elseif ($this->type == 'youtube') {
            return $this->getYoutubeCode();
        } elseif ($this->type == 'vimeo') {
            return $this->getVimeoCode();
        } elseif ($this->type == 'dailymotion') {
            return $this->getDailymotionCode();
        } elseif ($this->type == 'url') {
            return $this->getUrlCode();
        }

        return $this->getEmptyCode();
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [];

        if ($this->type == 'asset') {
            $asset = Asset::getById($this->id);
            if ($asset instanceof Asset) {
                $key = 'asset_' . $asset->getId();
                $dependencies[$key] = [
                    'id' => $asset->getId(),
                    'type' => 'asset',
                ];
            }
        }

        if ($poster = Asset::getById($this->poster)) {
            $key = 'asset_' . $poster->getId();
            $dependencies[$key] = [
                'id' => $poster->getId(),
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
        if ($this->type == 'asset' && !empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent asset with id [' . $this->id . ']');
                $this->id = null;
                $this->type = null;
            }
        }

        if (!($poster = Asset::getById($this->poster))) {
            $sane = false;
            Logger::notice('Detected insane relation, removing reference to non existent asset with id [' . $this->id . ']');
            $this->poster = null;
        }

        return $sane;
    }

    /**
     * @see EditableInterface::admin
     *
     * @return string
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
        $this->type = $data['type'];
        $this->poster = $data['poster'];
        $this->title = $data['title'];
        $this->description = $data['description'];

        return $this;
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
        if ($data['type']) {
            $this->type = $data['type'];
        }

        if ($data['title']) {
            $this->title = $data['title'];
        }

        if ($data['description']) {
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
    public function getAssetCode($inAdmin = false)
    {
        $asset = Asset::getById($this->id);
        $config = $this->getConfig();
        $thumbnailConfig = $config['thumbnail'] ?? null;

        // compatibility mode when FFMPEG is not present or no thumbnail config is given
        if (!\Pimcore\Video::isAvailable() || !$thumbnailConfig) {
            if ($asset instanceof Asset && preg_match("/\.(f4v|flv|mp4)/", $asset->getFullPath())) {
                $image = $this->getPosterThumbnailImage($asset);

                return $this->getHtml5Code(['mp4' => (string) $asset], $image);
            }

            return $this->getErrorCode('Asset is not a video, or missing thumbnail configuration');
        }

        if ($asset instanceof Asset\Video && $thumbnailConfig) {
            $thumbnail = $asset->getThumbnail($thumbnailConfig);
            if ($thumbnail) {
                $image = $this->getPosterThumbnailImage($asset);

                if ($inAdmin && isset($config['editmodeImagePreview']) && $config['editmodeImagePreview']) {
                    $code = '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video '.$config['class'].'">';
                    $code .= '<img width="' . $this->getWidth() . '" src="' . $image . '" />';
                    $code .= '</div>';

                    return $code;
                }

                if ($thumbnail['status'] === 'finished') {
                    return $this->getHtml5Code($thumbnail['formats'], $image);
                }

                if ($thumbnail['status'] === 'inprogress') {
                    // disable the output-cache if enabled
                    $cacheService = \Pimcore::getContainer()->get('pimcore.event_listener.frontend.full_page_cache');
                    $cacheService->disable('Video rendering in progress');

                    return $this->getProgressCode($image);
                }

                return $this->getErrorCode('The video conversion failed, please see the log files in /var/logs for more details.');
            }

            return $this->getErrorCode("The given thumbnail doesn't exist: '" . $thumbnailConfig . "'");
        }

        return $this->getEmptyCode();
    }

    /**
     * @param Asset\Video $asset
     *
     * @return Asset\Image\Thumbnail|null
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

        $image = null;
        if ($this->poster && ($poster = Asset\Image::getById($this->poster))) {
            $image = $poster->getThumbnail($imageThumbnailConf);
        } else {
            if ($asset->getCustomSetting('image_thumbnail_asset')
                && ($customPreviewAsset = Asset\Image::getById($asset->getCustomSetting('image_thumbnail_asset')))) {
                $image = $customPreviewAsset->getThumbnail($imageThumbnailConf);
            } else {
                $image = $asset->getImageThumbnail($imageThumbnailConf);
            }
        }

        return $image;
    }

    /**
     * @return string
     */
    public function getUrlCode()
    {
        return $this->getHtml5Code(['mp4' => (string) $this->id]);
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public function getErrorCode($message = '')
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
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video">
            <div class="pimcore_tag_video_error pimcore_editable_video_error" style="text-align:center; width: ' . $width . '; height: ' . ($this->getHeight() - 1) . 'px; border:1px solid #000; background: url(/bundles/pimcoreadmin/img/filetype-not-supported.svg) no-repeat center center #fff;">
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
        if ($this->type == 'youtube') {
            if ($youtubeId = $this->id) {
                if (strpos($youtubeId, '//') !== false) {
                    $parts = parse_url($this->id);
                    parse_str($parts['query'], $vars);

                    if ($vars['v']) {
                        $youtubeId = $vars['v'];
                    }

                    //get youtube id if form urls like  http://www.youtube.com/embed/youtubeId
                    if (strpos($this->id, 'embed') !== false) {
                        $explodedPath = explode('/', $parts['path']);
                        $youtubeId = $explodedPath[array_search('embed', $explodedPath) + 1];
                    }

                    if ($parts['host'] == 'youtu.be') {
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
    public function getYoutubeUrlEmbedded()
    {
        if ($this->type == 'youtube') {
            if ($youtubeId = $this->parseYoutubeId()) {
                if (strpos($youtubeId, 'PL') === 0) {
                    $youtubeId .= sprintf('videoseries?list=%s', $youtubeId);
                }

                return 'https://www.youtube-nocookie.com/embed/'.$youtubeId;
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getYoutubeCode()
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
        if (array_key_exists('youtube', $config) && is_array($config['youtube'])) {
            $configurations = array_merge($clipConfig, $config['youtube']);
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

        $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video '.$config['class'].'">
            <iframe width="' . $width . '" height="' . $height . '" src="https://www.youtube-nocookie.com/embed/' . $seriesPrefix . $youtubeId . $wmode . $additional_params .'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
        </div>';

        return $code;
    }

    /**
     * @return string
     */
    public function getVimeoCode()
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $config = $this->getConfig();
        $code = '';
        $uid = 'video_' . uniqid();

        // get vimeo id
        if (preg_match("@vimeo.*/([\d]+)@i", $this->id, $matches)) {
            $vimeoId = intval($matches[1]);
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
            if (is_array($config['config']['clip'])) {
                $clipConfig = $config['config']['clip'];
            }

            // this is to be backward compatible to <= v 1.4.7
            $configurations = $clipConfig;
            if (is_array($config['vimeo'])) {
                $configurations = array_merge($clipConfig, $config['vimeo']);
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

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video '.$config['class'].'">
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
    public function getDailymotionCode()
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

            $clipConfig = is_array($config['config']['clip']) ? $config['config']['clip'] : [];

            // this is to be backward compatible to <= v 1.4.7
            $configurations = $clipConfig;
            if (is_array($config['dailymotion'])) {
                $configurations = array_merge($clipConfig, $config['dailymotion']);
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

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video '.$config['class'].'">
                <iframe src="https://www.dailymotion.com/embed/video/' . $dailymotionId . '?' . $additional_params .'" width="' . $width . '" height="' . $height . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
            </div>';

            return $code;
        }

        // default => return the empty code
        return $this->getEmptyCode();
    }

    /**
     * @param array $urls
     * @param string|null $thumbnail
     *
     * @return string
     */
    public function getHtml5Code($urls = [], $thumbnail = null)
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

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video">' . "\n";

            $uploadDate = new \DateTime();
            $uploadDate->setTimestamp($video->getCreationDate());

            $jsonLd = [
                '@context' => 'http://schema.org',
                '@type' => 'VideoObject',
                'name' => $this->getTitle(),
                'description' => $this->getDescription(),
                'uploadDate' => $uploadDate->format(\DateTime::ISO8601),
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

            $jsonLd['thumbnailUrl'] = (string)$thumbnail;
            if (!preg_match('@https?://@', (string)$thumbnail)) {
                $jsonLd['thumbnailUrl'] = Tool::getHostUrl() . $thumbnail;
            }

            $code .= "\n\n<script type=\"application/ld+json\">\n" . json_encode($jsonLd) . "\n</script>\n\n";

            // default attributes
            $attributesString = '';
            $attributes = [
                'width' => $this->getWidth(),
                'height' => $this->getHeight(),
                'poster' => $thumbnail,
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
                $code .= '<source type="video/' . $type . '" src="' . $url . '" />' . "\n";
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
    public function getProgressCode($thumbnail = null)
    {
        $uid = 'video_' . uniqid();
        $code = '
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video">
            <style type="text/css">
                #' . $uid . ' .pimcore_tag_video_progress_status {
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
            <div class="pimcore_tag_video_progress pimcore_editable_video_progress" id="' . $uid . '">
                <img src="' . $thumbnail . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;">
                <div class="pimcore_tag_video_progress_status pimcore_editable_video_progress_status"></div>
            </div>
        </div>';

        return $code;
    }

    /**
     * @return string
     */
    public function getEmptyCode()
    {
        $uid = 'video_' . uniqid();

        return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video pimcore_editable_video"><div class="pimcore_tag_video_empty" id="' . $uid . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;"></div></div>';
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
            if ($data->type == 'asset') {
                $this->id = $data->id;
                $asset = Asset::getById($data->id);
                if (!$asset) {
                    throw new \Exception('Referencing unknown asset with id [ '.$data->id.' ] in webservice import field [ '.$data->name.' ]');
                }
                $this->type = $data->type;
            } elseif (in_array($data->type, ['dailymotion', 'vimeo', 'youtube', 'url'])) {
                $this->id = $data->id;
                $this->type = $data->type;
            } else {
                throw new \Exception('cannot get values from web service import - type must be asset,youtube,url, vimeo or dailymotion');
            }
        }
    }

    /**
     * @return string
     */
    public function getVideoType()
    {
        return $this->type;
    }

    /**
     * @return Asset\Video|null
     */
    public function getVideoAsset()
    {
        if ($this->getVideoType() == 'asset') {
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
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param array $idMapping
     */
    public function rewriteIds($idMapping)
    {
        if ($this->type == 'asset' && array_key_exists('asset', $idMapping) and array_key_exists($this->getId(), $idMapping['asset'])) {
            $this->setId($idMapping['asset'][$this->getId()]);
        }
    }
}

class_alias(Video::class, 'Pimcore\Model\Document\Tag\Video');
