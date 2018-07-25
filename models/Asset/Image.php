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
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Image extends Model\Asset
{
    /**
     * @var string
     */
    public $type = 'image';

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {

        // only do this if the file exists and contains data
        if ($this->getDataChanged() || !$this->getCustomSetting('imageDimensionsCalculated')) {
            try {
                // save the current data into a tmp file to calculate the dimensions, otherwise updates wouldn't be updated
                // because the file is written in parent::update();
                $tmpFile = $this->getTemporaryFile();
                $dimensions = $this->getDimensions($tmpFile, true);
                unlink($tmpFile);

                if ($dimensions && $dimensions['width']) {
                    $this->setCustomSetting('imageWidth', $dimensions['width']);
                    $this->setCustomSetting('imageHeight', $dimensions['height']);
                }
            } catch (\Exception $e) {
                Logger::error('Problem getting the dimensions of the image with ID ' . $this->getId());
            }

            // this is to be downward compatible so that the controller can check if the dimensions are already calculated
            // and also to just do the calculation once, because the calculation can fail, an then the controller tries to
            // calculate the dimensions on every request an also will create a version, ...
            $this->setCustomSetting('imageDimensionsCalculated', true);
        }

        $this->clearThumbnails();

        parent::update($params);

        // now directly create "system" thumbnails (eg. for the tree, ...)
        if ($this->getDataChanged()) {
            try {
                $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath();

                // set the modification time of the thumbnail to the same time from the asset
                // so that the thumbnail check doesn't fail in Asset\Image\Thumbnail\Processor::process();
                // we need the @ in front of touch because of some stream wrapper (eg. s3) which don't support touch()
                @touch($path, $this->getModificationDate());

                $this->generateLowQualityPreview();
            } catch (\Exception $e) {
                Logger::error('Problem while creating system-thumbnails for image ' . $this->getRealFullPath());
                Logger::error($e);
            }
        }
    }

    protected function postPersistData()
    {
        $this->detectFocalPoint();
    }

    public function detectFocalPoint()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['focal_point_detection'];

        if (!$config['enabled']) {
            return false;
        }

        $facedetectBin = \Pimcore\Tool\Console::getExecutable('facedetect');
        if ($facedetectBin) {
            $faceCoordinates = [];
            $thumbnail = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig());
            $image = $thumbnail->getFileSystemPath();
            $imageWidth = $thumbnail->getWidth();
            $imageHeight = $thumbnail->getHeight();

            $result = \Pimcore\Tool\Console::exec($facedetectBin . ' ' . escapeshellarg($image));
            if (strpos($result, "\n")) {
                $faces = explode("\n", trim($result));
                $xPoints = [];
                $yPoints = [];

                foreach ($faces as $coordinates) {
                    list($x, $y, $width, $height) = explode(' ', $coordinates);

                    // percentages
                    $Px = $x / $imageWidth * 100;
                    $Py = $y / $imageHeight * 100;
                    $Pw = $width / $imageWidth * 100;
                    $Ph = $height / $imageHeight * 100;

                    $faceCoordinates[] = [
                        'x' => $Px,
                        'y' => $Py,
                        'width' => $Pw,
                        'height' => $Ph
                    ];

                    // focal point calculation
                    $xPoints[] = ($Px + $Px + $Pw) / 2;
                    $yPoints[] = ($Py + + $Py + $Ph) / 2;
                }

                $this->setCustomSetting('faceCoordinates', $faceCoordinates);

                if (!$this->getCustomSetting('focalPointX')) {
                    $focalPointX = array_sum($xPoints) / count($xPoints);
                    $focalPointY = array_sum($yPoints) / count($yPoints);

                    $this->setCustomSetting('focalPointX', $focalPointX);
                    $this->setCustomSetting('focalPointY', $focalPointY);
                }
            }
        }
    }

    /**
     * @param null|string $generator
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function generateLowQualityPreview($generator = null)
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['low_quality_image_preview'];

        if (!$config['enabled']) {
            return false;
        }

        if (!$generator) {
            $generator = $config['generator'];
        }

        if (!$generator) {
            $sqipBin = \Pimcore\Tool\Console::getExecutable('sqip');
            if ($sqipBin) {
                $generator = 'qip';
            }
        }

        if ($generator == 'sqip') {
            // SQIP is preferred, produced smaller files & mostly better quality
            // primitive isn't able to process PJPEG so we have to generate a PNG
            $sqipConfig = Image\Thumbnail\Config::getPreviewConfig();
            $sqipConfig->setFormat('png');
            $pngPath = $this->getThumbnail($sqipConfig)->getFileSystemPath();
            $svgPath = $this->getLowQualityPreviewFileSystemPath();
            \Pimcore\Tool\Console::exec($sqipBin . ' -o ' . escapeshellarg($svgPath) . ' '. escapeshellarg($pngPath));
            unlink($pngPath);

            if (file_exists($svgPath)) {
                $svgData = file_get_contents($svgPath);
                $svgData = str_replace('<svg', '<svg preserveAspectRatio="xMidYMid slice"', $svgData);
                File::put($svgPath, $svgData);

                return $svgPath;
            }
        }

        // fallback
        if (class_exists('Imagick')) {
            // Imagick fallback
            $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath();
            $imagick = new \Imagick($path);
            $imagick->setImageFormat('jpg');
            $imagick->setOption('jpeg:extent', '1kb');
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();

            // we can't use getImageBlob() here, because of a bug in combination with jpeg:extent
            // http://www.imagemagick.org/discourse-server/viewtopic.php?f=3&t=24366
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/image-optimize-' . uniqid() . '.jpg';
            $imagick->writeImage($tmpFile);
            $imageBase64 = base64_encode(file_get_contents($tmpFile));
            $imagick->destroy();
            unlink($tmpFile);

            $svg = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$width" height="$height" viewBox="0 0 $width $height" preserveAspectRatio="xMidYMid slice">
	<filter id="blur" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
    <feGaussianBlur stdDeviation="20 20" edgeMode="duplicate" />
    <feComponentTransfer>
      <feFuncA type="discrete" tableValues="1 1" />
    </feComponentTransfer>
  </filter>
    <image filter="url(#blur)" x="0" y="0" height="100%" width="100%" xlink:href="data:image/jpg;base64,$imageBase64" />
</svg>
EOT;

            File::put($this->getLowQualityPreviewFileSystemPath(), $svg);

            return $this->getLowQualityPreviewFileSystemPath();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getLowQualityPreviewPath()
    {
        $fsPath = $this->getLowQualityPreviewFileSystemPath();
        $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails', '', $fsPath);
        $path = urlencode_ignore_slash($path);

        $event = new GenericEvent($this, [
            'filesystemPath' => $fsPath,
            'frontendPath' => $path
        ]);
        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_IMAGE_THUMBNAIL, $event);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @return string
     */
    public function getLowQualityPreviewFileSystemPath()
    {
        $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath();
        $svgPath = preg_replace("/\.p?jpe?g$/", '-low-quality-preview.svg', $path);

        return $svgPath;
    }

    public function delete()
    {
        parent::delete();
        $this->clearThumbnails(true);
    }

    /**
     * @param bool $force
     */
    public function clearThumbnails($force = false)
    {
        if ($this->getDataChanged() || $force) {
            $files = glob($this->getImageThumbnailSavePath() . '/image-thumb__' . $this->getId() . '__*');
            foreach ($files as $file) {
                recursiveDelete($file);
            }
        }
    }

    /**
     * @param $name
     */
    public function clearThumbnail($name)
    {
        $dir = $this->getImageThumbnailSavePath() . '/thumb__' . $name;
        if (is_dir($dir)) {
            recursiveDelete($dir);
        }
    }

    /**
     * Legacy method for backwards compatibility. Use getThumbnail($config)->getConfig() instead.
     *
     * @param mixed $config
     *
     * @return Image\Thumbnail|bool
     */
    public function getThumbnailConfig($config)
    {
        $thumbnail = $this->getThumbnail($config);

        return $thumbnail->getConfig();
    }

    /**
     * Returns a path to a given thumbnail or an thumbnail configuration.
     *
     * @param null $config
     * @param bool $deferred
     *
     * @return Image\Thumbnail
     */
    public function getThumbnail($config = null, $deferred = true)
    {
        return new Image\Thumbnail($this, $config, $deferred);
    }

    /**
     * @static
     *
     * @throws \Exception
     *
     * @return null|\Pimcore\Image\Adapter
     */
    public static function getImageTransformInstance()
    {
        try {
            $image = \Pimcore\Image::getInstance();
        } catch (\Exception $e) {
            $image = null;
        }

        if (!$image instanceof \Pimcore\Image\Adapter) {
            throw new \Exception("Couldn't get instance of image tranform processor.");
        }

        return $image;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        if ($this->getWidth() > $this->getHeight()) {
            return 'landscape';
        } elseif ($this->getWidth() == $this->getHeight()) {
            return 'square';
        } elseif ($this->getHeight() > $this->getWidth()) {
            return 'portrait';
        }

        return 'unknown';
    }

    /**
     * @return string
     */
    public function getRelativeFileSystemPath()
    {
        return str_replace(PIMCORE_WEB_ROOT, '', $this->getFileSystemPath());
    }

    /**
     * @param null $path
     * @param bool $force
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getDimensions($path = null, $force = false)
    {
        if (!$force) {
            $width = $this->getCustomSetting('imageWidth');
            $height = $this->getCustomSetting('imageHeight');

            if ($width && $height) {
                return [
                    'width' => $width,
                    'height' => $height
                ];
            }
        }

        if (!$path) {
            $path = $this->getFileSystemPath();
        }

        $dimensions = null;

        //try to get the dimensions with getimagesize because it is much faster than e.g. the Imagick-Adapter
        if (is_readable($path)) {
            $imageSize = getimagesize($path);
            if ($imageSize[0] && $imageSize[1]) {
                $dimensions = [
                    'width' => $imageSize[0],
                    'height' => $imageSize[1]
                ];
            }
        }

        if (!$dimensions) {
            $image = self::getImageTransformInstance();

            $status = $image->load($path, ['preserveColor' => true, 'asset' => $this]);
            if ($status === false) {
                return;
            }

            $dimensions = [
                'width' => $image->getWidth(),
                'height' => $image->getHeight()
            ];
        }

        // EXIF orientation
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            if (is_array($exif)) {
                if (array_key_exists('Orientation', $exif)) {
                    $orientation = intval($exif['Orientation']);
                    if (in_array($orientation, [5, 6, 7, 8])) {
                        // flip height & width
                        $dimensions = [
                            'width' => $dimensions['height'],
                            'height' => $dimensions['width']
                        ];
                    }
                }
            }
        }

        return $dimensions;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $dimensions = $this->getDimensions();

        return $dimensions['width'];
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $dimensions = $this->getDimensions();

        return $dimensions['height'];
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return Model\Asset
     */
    public function setCustomSetting($key, $value)
    {
        if (in_array($key, ['focalPointX', 'focalPointY'])) {
            // if the focal point changes we need to clean all thumbnails on save
            if ($this->getCustomSetting($key) != $value) {
                $this->setDataChanged();
            }
        }

        return parent::setCustomSetting($key, $value);
    }

    /**
     * @return bool
     */
    public function isVectorGraphic()
    {
        // we use a simple file-extension check, for performance reasons
        if (preg_match("@\.(svgz?|eps|pdf|ps|ai|indd)$@", $this->getFilename())) {
            return true;
        }

        return false;
    }

    /**
     * Checks if this file represents an animated image (png or gif)
     *
     * @return bool
     */
    public function isAnimated()
    {
        $isAnimated = false;

        switch ($this->getMimetype()) {
            case 'image/gif':
                $isAnimated = $this->isAnimatedGif();
                break;
            case 'image/png':
                $isAnimated = $this->isAnimatedPng();
                break;
            default:
                break;
        }

        return $isAnimated;
    }

    /**
     * Checks if this object represents an animated gif file
     *
     * @return bool
     */
    private function isAnimatedGif()
    {
        $isAnimated = false;

        if ($this->getMimetype() == 'image/gif') {
            $fileContent = $this->getData();

            /**
             * An animated gif contains multiple "frames", with each frame having a header made up of:
             *  - a static 4-byte sequence (\x00\x21\xF9\x04)
             *  - 4 variable bytes
             *  - a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)
             *
             * @see http://it.php.net/manual/en/function.imagecreatefromgif.php#104473
             */
            $numberOfFrames = preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $fileContent, $matches);

            $isAnimated = $numberOfFrames > 1;
        }

        return $isAnimated;
    }

    /**
     * Checks if this object represents an animated png file
     *
     * @return bool
     */
    private function isAnimatedPng()
    {
        $isAnimated = false;

        if ($this->getMimetype() == 'image/png') {
            $fileContent = $this->getData();

            /**
             * Valid APNGs have an "acTL" chunk somewhere before their first "IDAT" chunk.
             *
             * @see http://foone.org/apng/
             */
            $isAnimated = strpos(substr($fileContent, 0, strpos($fileContent, 'IDAT')), 'acTL') !== false;
        }

        return $isAnimated;
    }

    /**
     * @return array
     */
    public function getEXIFData()
    {
        $data = [];

        if (function_exists('exif_read_data') && is_file($this->getFileSystemPath())) {
            $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM];

            if (in_array(@exif_imagetype($this->getFileSystemPath()), $supportedTypes)) {
                $exif = @exif_read_data($this->getFileSystemPath());
                if (is_array($exif)) {
                    foreach ($exif as $name => $value) {
                        if ((is_string($value) && strlen($value) < 50) || is_numeric($value)) {
                            $data[$name] = \ForceUTF8\Encoding::toUTF8($value);
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function getXMPData()
    {
        $data = [];

        if (in_array(File::getFileExtension($this->getFilename()), ['jpg', 'jpeg', 'jp2', 'png', 'gif', 'webp', 'j2k', 'jpf', 'jpx', 'jpm'])) {
            $chunkSize = 1024;
            if (!is_int($chunkSize)) {
                throw new \RuntimeException('Expected integer value for argument #2 (chunkSize)');
            }

            if ($chunkSize < 12) {
                throw new \RuntimeException('Chunk size cannot be less than 12 argument #2 (chunkSize)');
            }

            if (($file_pointer = fopen($this->getFileSystemPath(), 'rb')) === false) {
                throw new \RuntimeException('Could not open file for reading');
            }

            $tag = '<x:xmpmeta';
            $tagLength = strlen($tag);
            $buffer = false;

            // find open tag
            while ($buffer === false && ($chunk = fread($file_pointer, $chunkSize)) !== false) {
                if (strlen($chunk) <= $tagLength) {
                    break;
                }
                if (($position = strpos($chunk, $tag)) === false) {
                    // if open tag not found, back up just in case the open tag is on the split.
                    fseek($file_pointer, $tagLength * -1, SEEK_CUR);
                } else {
                    $buffer = substr($chunk, $position);
                }
            }

            if ($buffer !== false) {
                $tag = '</x:xmpmeta>';
                $tagLength = strlen($tag);
                $offset = 0;
                while (($position = strpos($buffer, $tag, $offset)) === false && ($chunk = fread($file_pointer,
                        $chunkSize)) !== false && !empty($chunk)) {
                    $offset = strlen($buffer) - $tagLength; // subtract the tag size just in case it's split between chunks.
                    $buffer .= $chunk;
                }

                if ($position === false) {
                    // this would mean the open tag was found, but the close tag was not.  Maybe file corruption?
                    throw new \RuntimeException('No close tag found.  Possibly corrupted file.');
                } else {
                    $buffer = substr($buffer, 0, $position + $tagLength);
                }

                $buffer = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $buffer);
                $buffer = preg_replace('@<(/)?([a-zA-Z]+):([a-zA-Z]+)@', '<$1$2____$3', $buffer);

                $xml = @simplexml_load_string($buffer);
                if ($xml) {
                    if ($xml->rdf____RDF->rdf____Description) {
                        foreach ($xml->rdf____RDF->rdf____Description as $description) {
                            $data = array_merge($data, object2array($description));
                        }
                    }
                }

                if (isset($data['@attributes'])) {
                    unset($data['@attributes']);
                }
            }

            fclose($file_pointer);
        }

        // remove namespace prefixes if possible
        $resultData = [];
        array_walk($data, function ($value, $key) use (&$resultData) {
            $parts = explode('____', $key);
            $length = count($parts);
            if ($length > 1) {
                $name = $parts[$length - 1];
                if (!isset($resultData[$name])) {
                    $key = $name;
                }
            }

            $resultData[$key] = $value;
        });

        return $resultData;
    }

    /**
     * @return array
     */
    public function getIPTCData()
    {
        $data = [];

        if (is_file($this->getFileSystemPath())) {
            $result = getimagesize($this->getFileSystemPath(), $info);
            if ($result) {
                $mapping = [
                    '1#000' => 'EnvelopeRecordVersion',
                    '1#005' => 'Destination',
                    '1#020' => 'FileFormat',
                    '1#022' => 'FileVersion',
                    '1#030' => 'ServiceIdentifier',
                    '1#040' => 'EnvelopeNumber',
                    '1#050' => 'ProductID',
                    '1#060' => 'EnvelopePriority',
                    '1#070' => 'DateSent',
                    '1#080' => 'TimeSent',
                    '1#090' => 'CodedCharacterSet',
                    '1#100' => 'UniqueObjectName',
                    '1#120' => 'ARMIdentifier',
                    '1#122' => 'ARMVersion',
                    '2#000' => 'ApplicationRecordVersion',
                    '2#003' => 'ObjectTypeReference',
                    '2#004' => 'ObjectAttributeReference',
                    '2#005' => 'ObjectName',
                    '2#007' => 'EditStatus',
                    '2#008' => 'EditorialUpdate',
                    '2#010' => 'Urgency',
                    '2#012' => 'SubjectReference',
                    '2#015' => 'Category',
                    '2#020' => 'SupplementalCategories',
                    '2#022' => 'FixtureIdentifier',
                    '2#025' => 'Keywords',
                    '2#026' => 'ContentLocationCode',
                    '2#027' => 'ContentLocationName',
                    '2#030' => 'ReleaseDate',
                    '2#035' => 'ReleaseTime',
                    '2#037' => 'ExpirationDate',
                    '2#038' => 'ExpirationTime',
                    '2#040' => 'SpecialInstructions',
                    '2#042' => 'ActionAdvised',
                    '2#045' => 'ReferenceService',
                    '2#047' => 'ReferenceDate',
                    '2#050' => 'ReferenceNumber',
                    '2#055' => 'DateCreated',
                    '2#060' => 'TimeCreated',
                    '2#062' => 'DigitalCreationDate',
                    '2#063' => 'DigitalCreationTime',
                    '2#065' => 'OriginatingProgram',
                    '2#070' => 'ProgramVersion',
                    '2#075' => 'ObjectCycle',
                    '2#080' => 'By-line',
                    '2#085' => 'By-lineTitle',
                    '2#090' => 'City',
                    '2#092' => 'Sub-location',
                    '2#095' => 'Province-State',
                    '2#100' => 'Country-PrimaryLocationCode',
                    '2#101' => 'Country-PrimaryLocationName',
                    '2#103' => 'OriginalTransmissionReference',
                    '2#105' => 'Headline',
                    '2#110' => 'Credit',
                    '2#115' => 'Source',
                    '2#116' => 'CopyrightNotice',
                    '2#118' => 'Contact',
                    '2#120' => 'Caption-Abstract',
                    '2#121' => 'LocalCaption',
                    '2#122' => 'Writer-Editor',
                    '2#125' => 'RasterizedCaption',
                    '2#130' => 'ImageType',
                    '2#131' => 'ImageOrientation',
                    '2#135' => 'LanguageIdentifier',
                    '2#150' => 'AudioType',
                    '2#151' => 'AudioSamplingRate',
                    '2#152' => 'AudioSamplingResolution',
                    '2#153' => 'AudioDuration',
                    '2#154' => 'AudioOutcue',
                    '2#184' => 'JobID',
                    '2#185' => 'MasterDocumentID',
                    '2#186' => 'ShortDocumentID',
                    '2#187' => 'UniqueDocumentID',
                    '2#188' => 'OwnerID',
                    '2#200' => 'ObjectPreviewFileFormat',
                    '2#201' => 'ObjectPreviewFileVersion',
                    '2#202' => 'ObjectPreviewData',
                    '2#221' => 'Prefs',
                    '2#225' => 'ClassifyState',
                    '2#228' => 'SimilarityIndex',
                    '2#230' => 'DocumentNotes',
                    '2#231' => 'DocumentHistory',
                    '2#232' => 'ExifCameraInfo',
                    '2#255' => 'CatalogSets',
                    '3#000' => 'NewsPhotoVersion',
                    '3#010' => 'IPTCPictureNumber',
                    '3#020' => 'IPTCImageWidth',
                    '3#030' => 'IPTCImageHeight',
                    '3#040' => 'IPTCPixelWidth',
                    '3#050' => 'IPTCPixelHeight',
                    '3#055' => 'SupplementalType',
                    '3#060' => 'ColorRepresentation',
                    '3#064' => 'InterchangeColorSpace',
                    '3#065' => 'ColorSequence',
                    '3#066' => 'ICC_Profile',
                    '3#070' => 'ColorCalibrationMatrix',
                    '3#080' => 'LookupTable',
                    '3#084' => 'NumIndexEntries',
                    '3#085' => 'ColorPalette',
                    '3#086' => 'IPTCBitsPerSample',
                    '3#090' => 'SampleStructure',
                    '3#100' => 'ScanningDirection',
                    '3#102' => 'IPTCImageRotation',
                    '3#110' => 'DataCompressionMethod',
                    '3#120' => 'QuantizationMethod',
                    '3#125' => 'EndPoints',
                    '3#130' => 'ExcursionTolerance',
                    '3#135' => 'BitsPerComponent',
                    '3#140' => 'MaximumDensityRange',
                    '3#145' => 'GammaCompensatedValue',
                    '7#010' => 'SizeMode',
                    '7#020' => 'MaxSubfileSize',
                    '7#090' => 'ObjectSizeAnnounced',
                    '7#095' => 'MaximumObjectSize',
                    '8#010' => 'SubFile',
                    '9#010' => 'ConfirmedObjectSize',
                ];

                if ($info && isset($info['APP13'])) {
                    $iptcRaw = iptcparse($info['APP13']);
                    if (is_array($iptcRaw)) {
                        foreach ($iptcRaw as $key => $value) {
                            if (is_array($value) && count($value) === 1) {
                                $value = $value[0];
                            }

                            if (isset($mapping[$key])) {
                                $data[$mapping[$key]] = \ForceUTF8\Encoding::toUTF8($value);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
