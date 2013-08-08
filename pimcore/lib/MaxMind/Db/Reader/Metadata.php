<?php

namespace MaxMind\Db\Reader;

use MaxMind\Db\Reader\Decoder;

/**
 * This class provides the metadata for the database. This is primarily
 * meant for internal use.
 */
class Metadata
{
    private $binaryFormatMajorVersion;
    private $binaryFormatMinorVersion;
    private $buildEpoch;
    private $databaseType;
    private $description;
    private $ipVersion;
    private $languages;
    private $nodeByteSize;
    private $nodeCount;
    private $recordSize;
    private $searchTreeSize;

    public function __construct($metadata)
    {
        $this->binaryFormatMajorVersion =
        $metadata['binary_format_major_version'];
        $this->binaryFormatMinorVersion =
            $metadata['binary_format_minor_version'];
        $this->buildEpoch = $metadata['build_epoch'];
        $this->databaseType = $metadata['database_type'];
        $this->languages = $metadata['languages'];
        $this->description = $metadata['description'];
        $this->ipVersion = $metadata['ip_version'];
        $this->nodeCount = $metadata['node_count'];
        $this->recordSize = $metadata['record_size'];
        $this->nodeByteSize = $this->recordSize / 4;
        $this->searchTreeSize = $this->nodeCount * $this->nodeByteSize;
    }

    public function __get($var)
    {
        return $this->$var;
    }
}
