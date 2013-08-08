<?php

namespace GeoIp2\Record;

abstract class AbstractRecord
{
    private $record;

    /**
     * @ignore
     */
    public function __construct($record)
    {
        $this->record = $record;
    }

    /**
     * @ignore
     */
    public function __get($attr)
    {
        $valid = in_array($attr, $this->validAttributes);
        // XXX - kind of ugly but greatly reduces boilerplate code
        $key = strtolower(preg_replace('/([A-Z])/', '_\1', $attr));

        if ($valid && isset($this->record[$key])) {
            return $this->record[$key];
        } elseif ($valid) {
            return null;
        } else {
            throw new \RuntimeException("Unknown attribute: $attr");
        }
    }
}
