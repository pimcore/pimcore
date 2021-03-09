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
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\MetaData;

use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Tool;

trait EmbeddedMetaDataTrait
{
    use TemporaryFileHelperTrait;

    /**
     * @param bool $force
     * @param bool $useExifTool
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getEmbeddedMetaData(bool $force, bool $useExifTool = true)
    {
        if ($force) {
            $this->handleEmbeddedMetaData($useExifTool);
        }

        return $this->getCustomSetting('embeddedMetaData') ?: [];
    }

    /**
     * @param bool $useExifTool
     * @param string|null $filePath
     *
     * @throws \Exception
     */
    protected function handleEmbeddedMetaData(bool $useExifTool = true, ?string $filePath = null)
    {
        if (!$this->getCustomSetting('embeddedMetaDataExtracted') || $this->getDataChanged()) {
            $this->readEmbeddedMetaData($useExifTool, $filePath);
        }
    }

    /**
     * @param bool $useExifTool
     * @param string|null $filePath
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function readEmbeddedMetaData(bool $useExifTool = true, ?string $filePath = null): array
    {
        $exiftool = \Pimcore\Tool\Console::getExecutable('exiftool');

        if (!$filePath) {
            $filePath = $this->getFileSystemPath();
        }

        $filePath = $this->getLocalFile($filePath);

        if ($exiftool && $useExifTool) {
            $path = escapeshellarg($filePath);
            $output = Tool\Console::exec($exiftool . ' -j ' . $path);
            $embeddedMetaData = $this->flattenArray((array) json_decode($output)[0]);

            foreach (['Directory', 'FileName', 'SourceFile', 'ExifToolVersion'] as $removeKey) {
                if (isset($embeddedMetaData[$removeKey])) {
                    unset($embeddedMetaData[$removeKey]);
                }
            }
        } else {
            $xmp = $this->flattenArray($this->getXMPData($filePath));
            $iptc = $this->flattenArray($this->getIPTCData($filePath));
            $exif = $this->flattenArray($this->getEXIFData($filePath));
            $embeddedMetaData = array_merge(array_merge($xmp, $exif), $iptc);
        }

        $this->setCustomSetting('embeddedMetaData', $embeddedMetaData);
        $this->setCustomSetting('embeddedMetaDataExtracted', true);

        return $embeddedMetaData;
    }

    /**
     * @param array $tempArray
     *
     * @return array
     */
    private function flattenArray(array $tempArray)
    {
        array_walk($tempArray, function (&$value) {
            if (is_array($value)) {
                $value = implode_recursive($value, ' | ');
            }
        });

        return $tempArray;
    }

    /**
     * @param string|null $filePath
     *
     * @return array
     */
    public function getEXIFData(?string $filePath = null)
    {
        if (!$filePath) {
            $filePath = $this->getFileSystemPath();
        }

        $data = [];

        if (function_exists('exif_read_data') && is_file($filePath)) {
            $exif = @exif_read_data($filePath);
            if (is_array($exif)) {
                foreach ($exif as $name => $value) {
                    if ((is_string($value) && strlen($value) < 50) || is_numeric($value)) {
                        $data[$name] = \ForceUTF8\Encoding::toUTF8($value);
                    }
                }
            }
        }

        return $data;
    }

    public function getXMPData(?string $filePath = null)
    {
        if (!$filePath) {
            $filePath = $this->getFileSystemPath();
        }

        $data = [];

        if (is_file($filePath)) {
            $chunkSize = 1024;
            if (!is_int($chunkSize)) {
                throw new \RuntimeException('Expected integer value for argument #2 (chunkSize)');
            }

            if ($chunkSize < 12) {
                throw new \RuntimeException('Chunk size cannot be less than 12 argument #2 (chunkSize)');
            }

            if (($file_pointer = fopen($filePath, 'rb')) === false) {
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
    public function getIPTCData(?string $filePath = null)
    {
        if (!$filePath) {
            $filePath = $this->getFileSystemPath();
        }

        $data = [];

        if (is_file($filePath)) {
            $result = getimagesize($filePath, $info);
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
