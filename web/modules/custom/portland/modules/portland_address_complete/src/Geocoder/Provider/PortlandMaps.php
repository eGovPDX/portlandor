<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Drupal\portland_address_complete\Geocoder\Provider;

use Geocoder\Exception\NoResult;
use Geocoder\Exception\UnsupportedOperation;
use Ivory\HttpAdapter\HttpAdapterInterface;
use Geocoder\Provider\AbstractHttpProvider;
use Geocoder\Provider\Provider;

/**
 * @author ALKOUM Dorian <baikunz@gmail.com>
 */
class PortlandMaps extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const ENDPOINT_URL = 'https://www.portlandmaps.com/api/suggest/?centerline=1&alt_coords=1&api_key=%s&count=1&query=%s';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = '%s://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?location=%F,%F';

    /**
     * @var string
     */
    private $sourceCountry;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @param HttpAdapterInterface $adapter       An HTTP adapter
     * @param string               $sourceCountry Country biasing (optional)
     * @param bool                 $useSsl        Whether to use an SSL connection (optional)
     */
    public function __construct(HttpAdapterInterface $adapter, $sourceCountry = null, $useSsl = false)
    {
        parent::__construct($adapter);

        $this->sourceCountry = $sourceCountry;
        $this->protocol      = $useSsl ? 'https' : 'http';
    }

    /**
     * {@inheritDoc}
     */
    public function geocode($address)
    {
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The PortlandMaps provider does not support IP addresses, only street addresses.');
        }

        $address = explode(',', $address)[0];

        // Save a request if no valid address entered
        if (empty($address)) {
            throw new NoResult('Invalid address.');
        }

        $portlandmaps_api_key = \Drupal::service('key.repository')->getKey('portlandmaps_api_server_side')->getKeyValue();
        $query = sprintf(self::ENDPOINT_URL, $portlandmaps_api_key, urlencode($address));
        $json  = $this->executeQuery($query);

        // no result
        if (empty($json->candidates)) {
            throw new NoResult(sprintf('No results found for query "%s".', $query));
        }

        $results = [];
        foreach ($json->candidates as $location) {
            $data = $location->attributes;

            $coordinates  = [
                'x' => $data->lat,
                'y' => $data->lon,
            ];
            $city         = !empty($data->jurisdiction) ? $data->jurisdiction : null;
            $zipcode      = !empty($data->zip_code) ? $data->zip_code : null;

            // $adminLevels = [];
            // foreach (['Region', 'Subregion'] as $i => $property) {
            //     if (! empty($data->{$property})) {
            //         $adminLevels[] = ['name' => $data->{$property}, 'level' => $i + 1];
            //     }
            // }

            $results[] = array_merge($this->getDefaults(), [
                'latitude'     => $coordinates['y'],
                'longitude'    => $coordinates['x'],
                // 'streetNumber' => $streetNumber,
                // 'streetName'   => $streetName,
                'locality'     => $city,
                'postalCode'   => $zipcode,
                // 'adminLevels'  => $adminLevels,
                // 'countryCode'  => $countryCode,
            ]);
        }

        return $this->returnResults($results);
    }

    /**
     * {@inheritDoc}
     */
    public function reverse($latitude, $longitude)
    {
        throw new NoResult('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'portlandmaps';
    }

    /**
     * @param string $query
     */
    private function buildQuery($query)
    {
        if (null !== $this->sourceCountry) {
            $query = sprintf('%s&sourceCountry=%s', $query, $this->sourceCountry);
        }

        return sprintf('%s&maxLocations=%d&f=%s&outFields=*', $query, $this->getLimit(), 'json');
    }

    /**
     * @param string $query
     */
    private function executeQuery($query)
    {
        $query   = $this->buildQuery($query);
        $content = (string) $this->getAdapter()->get($query)->getBody();

        if (empty($content)) {
            throw new NoResult(sprintf('Could not execute query "%s".', $query));
        }

        $json = json_decode($content);

        // API error
        if (!isset($json)) {
            throw new NoResult(sprintf('Could not execute query "%s".', $query));
        }

        return $json;
    }
}
