<?php

namespace GeoIp2\Database;

use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use GeoIp2\Model\CityIspOrg;
use GeoIp2\Model\Country;
use GeoIp2\Model\Omni;
use MaxMind\Db\Reader as DbReader;

/**
 * Instances of this class provide a reader for the GeoIP2 database format.
 * IP addresses can be looked up using the <code>country</code>
 * and <code>city</code> methods. We also provide <code>cityIspOrg</code>
 * and <code>omni</code> methods to ease compatibility with the web service
 * client, although we may offer the ability to specify additional databases
 * to replicate these web services in the future (e.g., the ISP/Org database).
 *
  * **Usage**
 *
 * The basic API for this class is the same for every database. First, you
 * create a reader object, specifying a file name. You then call the method
 * corresponding to the specific database, passing it the IP address you want
 * to look up.
 *
 * If the request succeeds, the method call will return a model class for
 * the method you called. This model in turn contains multiple record classes,
 * each of which represents part of the data returned by the database. If
 * the database does not contain the requested information, the attributes
 * on the record class will have a <code>null</code> value.
 *
 * If the address is not in the database, an
 * {@link \GeoIp2\Exception\AddressNotFoundException} exception will be
 * thrown. If an invalid IP address is passed to one of the methods, a
 * SPL {@link \InvalidArgumentException} will be thrown. If the database is
 * corrupt or invalid, a {@link \MaxMind\Db\Reader\InvalidDatabaseException}
 * will be thrown.
 *
 */
class Reader
{
    private $dbReader;
    private $languages;

    /**
     * Constructor.
     *
     * @param string $filename The path to the GeoIP2 database file.
     * @param array  $languages  List of language codes to use in name property
     * from most preferred to least preferred.
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
     *          is corrupt or invalid
     */
    public function __construct(
        $filename,
        $languages = array('en')
    ) {
        $this->dbReader = new DbReader($filename);
        $this->languages = $languages;
    }

    /**
     * This method returns a GeoIP2 City model.
     *
     * @param string $ipAddress IPv4 or IPv6 address as a string.
     *
     * @return \GeoIp2\Model\City
     *
     * @throws \GeoIp2\Exception\AddressNotFoundException if the address is
     *         not in the database.
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
     *         is corrupt or invalid
     */
    public function city($ipAddress)
    {
        return $this->modelFor('City', $ipAddress);
    }

    /**
     * This method returns a GeoIP2 Country model.
     *
     * @param string $ipAddress IPv4 or IPv6 address as a string.
     *
     * @return \GeoIp2\Model\Country
     *
     * @throws \GeoIp2\Exception\AddressNotFoundException if the address is
     *         not in the database.
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
     *         is corrupt or invalid
     */
    public function country($ipAddress)
    {
        return $this->modelFor('Country', $ipAddress);
    }

    /**
     * This method returns a GeoIP2 City/ISP/Org model.
     *
     * @param string $ipAddress IPv4 or IPv6 address as a string.
     *
     * @return \GeoIp2\Model\CityIspOrg
     *
     * @throws \GeoIp2\Exception\AddressNotFoundException if the address is
     *         not in the database.
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
     *         is corrupt or invalid
     */
    public function cityIspOrg($ipAddress)
    {
        return $this->modelFor('CityIspOrg', $ipAddress);
    }

    /**
     * This method returns a GeoIP2 Omni model.
     *
     * @param string $ipAddress IPv4 or IPv6 address as a string.
     *
     * @return \GeoIp2\Model\Omni
     *
     * @throws \GeoIp2\Exception\AddressNotFoundException if the address is
     *         not in the database.
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
     *         is corrupt or invalid
     */
    public function omni($ipAddress)
    {
        return $this->modelFor('Omni', $ipAddress);
    }

    private function modelFor($class, $ipAddress)
    {
        $record = $this->dbReader->get($ipAddress);
        if ($record === null) {
            throw new AddressNotFoundException(
                "The address $ipAddress is not in the database."
            );
        }
        $record['traits']['ip_address'] = $ipAddress;
        $class = "GeoIp2\\Model\\" . $class;

        return new $class($record, $this->languages);
    }

    /**
     * Closes the GeoIP2 database and returns the resources to the system.
     */
    public function close()
    {
        $this->dbReader->close();
    }
}
