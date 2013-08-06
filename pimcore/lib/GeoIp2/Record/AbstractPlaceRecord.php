<?php

namespace GeoIp2\Record;

abstract class AbstractPlaceRecord extends AbstractRecord
{
    private $languages;

    /**
     * @ignore
     */
    public function __construct($record, $languages)
    {
        $this->languages = $languages;
        parent::__construct($record);
    }

    /**
     * @ignore
     */
    public function __get($attr)
    {
        if ($attr == 'name') {
            return $this->name();
        } else {
            return parent::__get($attr);
        }
    }

    private function name()
    {
        foreach ($this->languages as $language) {
            if (isset($this->names[$language])) {
                return $this->names[$language];
            }
        }
    }
}
