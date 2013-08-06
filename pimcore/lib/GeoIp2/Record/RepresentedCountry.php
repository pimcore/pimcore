<?php

namespace GeoIp2\Record;

/**
 * Contains data for the represented country associated with an IP address
 *
 * This class contains the country-level data associated with an IP address
 * for the IP's represented country. The represented country is the country
 * represented by something like a military base or embassy.
 *
 * This record is returned by all the end points.
 *
 * @property int $confidence A value from 0-100 indicating MaxMind's
 * confidence that the country is correct. This attribute is only available
 * from the Omni end point.
 *
 * @property int $geonameId The GeoName ID for the country. This attribute is
 * returned by all end points.
 *
 * @property string $isoCode The {@link http://en.wikipedia.org/wiki/ISO_3166-1
 * two-character ISO 3166-1 alpha code} for the country. This attribute is
 * returned by all end points.
 *
 * @property string $name The name of the country based on the languages list
 * passed to the constructor. This attribute is returned by all end points.
 *
 * @property array $names An array map where the keys are language codes and
 * the values are names. This attribute is returned by all end points.
 *
 * @property string $type A string indicating the type of entity that is
 * representing the country. Currently we only return <code>military</code>
 * but this could expand to include other types such as <code>embassy</code>
 * in the future. Returned by all endpoints.
 */
class RepresentedCountry extends Country
{
    protected $validAttributes = array(
        'confidence',
        'geonameId',
        'isoCode',
        'namespace',
        'type'
    );
}
