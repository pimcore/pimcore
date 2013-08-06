<?php

namespace MaxMind\Db;

use MaxMind\Db\Reader\Decoder;
use MaxMind\Db\Reader\Logger;
use MaxMind\Db\Reader\Metadata;

/**
 * Instances of this class provide a reader for the MaxMind DB format. IP
 * addresses can be looked up using the <code>get</code> method.
 */
class Reader
{
    private $DATA_SECTION_SEPARATOR_SIZE = 16;
    private $METADATA_START_MARKER = "\xAB\xCD\xEFMaxMind.com";

    private $debug;
    private $decoder;
    private $fileHandle;
    private $metadata;

    /**
     * Constructs a Reader for the MaxMind DB format. The file passed to it must
     * be a valid MaxMind DB file such as a GeoIp2 database file.
     *
     * @param string $database
     *            the MaxMind DB file to use.
     * @param string $fileMode
     *            the mode to open the file with.
     * @throws InvalidDatabaseException
     *             if the database is invalid or there is an error reading
     *             from it.
     */
    public function __construct($database)
    {
        $this->debug = getenv('MAXMIND_DB_READER_DEBUG');
        $this->fileHandle = fopen($database, 'r');
        $start = $this->findMetadataStart($database);


        $metadataDecoder = new Decoder($this->fileHandle, 0);
        list($metadataArray) = $metadataDecoder->decode($start);
        $this->metadata = new Metadata($metadataArray);
        $this->decoder = new Decoder(
            $this->fileHandle,
            $this->metadata->searchTreeSize + $this->DATA_SECTION_SEPARATOR_SIZE
        );

        if ($this->debug) {
            Logger::log(serialize($this->metadata));
        }
    }

    /**
     * Looks up the <code>address</code> in the MaxMind DB.
     *
     * @param string $ipAddress
     *            the IP address to look up.
     * @return the record for the IP address.
     * @throws InvalidDatabaseException
     *             if the database is invalid or there is an error reading
     *             from it.
     */
    public function get($ipAddress)
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException(
                "$ipAddress is not a valid IP address"
            );
        }
        $pointer = $this->findAddressInTree($ipAddress);
        if ($pointer == 0) {
            // FIXME - I think PHP might expect an exception, and the GeoIP2
            // reader will behave that way anyway
            return null;
        }
        return $this->resolveDataPointer($pointer);
    }

    private function findAddressInTree($ipAddress)
    {
        // XXX - could simplify. Done as a byte array to ease porting
        $rawAddress = array_merge(unpack('C*', inet_pton($ipAddress)));

        if ($this->debug) {
            Logger::log();
            Logger::log("IP address", $ipAddress);
            Logger::log("IP address", implode(',', $rawAddress));
        }

        $isIp4AddressInIp6Db = count($rawAddress) == 4
                && $this->metadata->ipVersion == 6;
        $ipStartBit = $isIp4AddressInIp6Db ? 96 : 0;

        // The first node of the tree is always node 0, at the beginning of the
        // value
        $nodeNum = 0;

        for ($i = 0; $i < count($rawAddress) * 8 + $ipStartBit; $i++) {
            $bit = 0;
            if ($i >= $ipStartBit) {
                $tempBit = 0xFF & $rawAddress[($i - $ipStartBit) / 8];
                $bit = 1 & ($tempBit >> 7 - ($i % 8));
            }
            $record = $this->readNode($nodeNum, $bit);

            if ($this->debug) {
                Logger::log("Bit #", $i);
                Logger::log("Bit value", $bit);
                Logger::log("Record", $bit == 1 ? "right" : "left");
                Logger::log("Record value", $record);
            }

            if ($record == $this->metadata->nodeCount) {
                if ($this->debug) {
                    Logger::log("Record is empty");
                }
                return 0;
            } elseif ($record > $this->metadata->nodeCount) {
                if ($this->debug) {
                    Logger::log("Record is a data pointer");
                }
                return $record;
            }

            if ($this->debug) {
                Logger::log("Record is a node number");
            }

            $nodeNum = $record;
        }
        throw new InvalidDatabaseException("Something bad happened");
    }

    private function readNode($nodeNumber, $index)
    {
        $baseOffset = $nodeNumber * $this->metadata->nodeByteSize;

        // XXX - probably could condense this.
        switch ($this->metadata->recordSize) {
            case 24:
                fseek($this->fileHandle, $baseOffset + $index * 3);
                $bytes = fread($this->fileHandle, 3);
                list(, $node) = unpack('N', "\x00" . $bytes);
                return $node;
            case 28:
                fseek($this->fileHandle, $baseOffset + 3);
                list(, $middle) = unpack('C', fgetc($this->fileHandle));
                if ($index == 0) {
                    $middle = (0xF0 & $middle) >> 4;
                } else {
                    $middle = 0x0F & $middle;
                }

                fseek($this->fileHandle, $baseOffset + $index * 4);
                $bytes = fread($this->fileHandle, 3);
                list(, $node) = unpack('N', chr($middle) . $bytes);
                return $node;
            case 32:
                fseek($this->fileHandle, $baseOffset + $index * 4);
                $bytes = fread($this->fileHandle, 4);
                list(, $node) = unpack('N', $bytes);
                return $node;
            default:
                throw new InvalidDatabaseException(
                    'Unknown record size: '
                    + $this->metadata->recordSize
                );
        }
    }

    private function resolveDataPointer($pointer)
    {
        $resolved = $pointer - $this->metadata->nodeCount
                + $this->metadata->searchTreeSize;

        if ($this->debug) {
            $treeSize = $this->metadata->searchTreeSize;
            Logger::log(
                'Resolved data pointer',
                '( ' . $pointer . " - "
                . $this->metadata->nodeCount . " ) + " . $treeSize . " = "
                . $resolved
            );
        }

        // We only want the data from the decoder, not the offset where it was
        // found.
        list($data) = $this->decoder->decode($resolved);
        return $data;
    }

    /*
     * This is an extremely naive but reasonably readable implementation. There
     * are much faster algorithms (e.g., Boyer-Moore) for this if speed is ever
     * an issue, but I suspect it won't be.
     */
    private function findMetadataStart($filename)
    {
        $handle = $this->fileHandle;
        $fstat = fstat($handle);
        $fileSize = $fstat['size'];
        $marker = $this->METADATA_START_MARKER;
        $markerLength = strlen($marker);

        for ($i = 0; $i < $fileSize - $markerLength + 1; $i++) {
            for ($j = 0; $j < $markerLength; $j++) {
                fseek($handle, $fileSize - $i - $j - 1);
                $matchBit = fgetc($handle);
                if ($matchBit != $marker[$markerLength - $j - 1]) {
                    continue 2;
                }
            }
            return $fileSize - $i;
        }
        throw new InvalidDatabaseException(
            'Could not find a MaxMind DB metadata marker in this file ('
            . $filename . "). Is this a valid MaxMind DB file?"
        );
    }

    /**
     * @return Metadata object for the database.
     */
    public function metadata()
    {
        return $this->metadata;
    }

    /**
     * Closes the MaxMind DB and returns resources to the system.
     *
     * @throws Exception
     *             if an I/O error occurs.
     */
    public function close()
    {
        fclose($this->fileHandle);
    }
}
