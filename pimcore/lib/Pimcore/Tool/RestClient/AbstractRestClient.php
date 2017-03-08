<?php

namespace Pimcore\Tool\RestClient;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice;
use Pimcore\Tool;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractRestClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    protected $disableMappingExceptions = false;

    /**
     * @var bool
     */
    protected $enableProfiling = false;

    /**
     * @var mixed
     */
    protected $profilingInfo;

    /**
     * @var bool
     */
    protected $condense = false;

    /**
     * @var string
     */
    protected $basePath = '/webservice/rest';

    /**
     * @var string
     */
    protected $scheme = 'http';

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var array
     */
    protected $defaultParameters = [];

    /**
     * @var array
     */
    protected $defaultHeaders = [];

    /**
     * @var RequestInterface
     */
    protected $lastRequest;

    /**
     * @var ResponseInterface
     */
    protected $lastResponse;

    /**
     * @param array $options
     * @param array $parameters
     * @param array $headers
     */
    public function __construct(array $parameters = [], array $headers = [], array $options = [])
    {
        $this->defaultParameters = $parameters;
        $this->defaultHeaders    = $headers;

        $this->setValues($options);
    }

    /**
     * @return array
     */
    public function getDefaultParameters()
    {
        return $this->defaultParameters;
    }

    /**
     * @param array $defaultParameters
     */
    public function setDefaultParameters(array $defaultParameters)
    {
        $this->defaultParameters = $defaultParameters;
    }

    /**
     * @return array
     */
    public function getDefaultHeaders()
    {
        return $this->defaultHeaders;
    }

    /**
     * @param array $defaultHeaders
     */
    public function setDefaultHeaders(array $defaultHeaders)
    {
        $this->defaultHeaders = $defaultHeaders;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setValues(array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->setValue($key, $value);
        }

        return $this;
    }

    /**
     * @param  $key
     * @param  $value
     *
     * @return $this
     */
    public function setValue($key, $value)
    {
        $method = "set" . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid option: %s', $key));
        }

        return $this;
    }

    /**
     * @param $disableMappingExceptions
     *
     * @return $this
     */
    public function setDisableMappingExceptions($disableMappingExceptions)
    {
        $this->disableMappingExceptions = $disableMappingExceptions;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDisableMappingExceptions()
    {
        return $this->disableMappingExceptions;
    }

    /**
     * @param $condense
     *
     * @return $this
     */
    public function setCondense($condense)
    {
        $this->condense = $condense;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCondense()
    {
        return $this->condense;
    }

    /**
     * @param $enableProfiling
     *
     * @return $this
     */
    public function setEnableProfiling($enableProfiling)
    {
        $this->enableProfiling = $enableProfiling;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnableProfiling()
    {
        return $this->enableProfiling;
    }

    /**
     * @return mixed
     */
    public function getProfilingInfo()
    {
        return $this->profilingInfo;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param $apikey
     *
     * @return $this
     */
    public function setApiKey($apikey)
    {
        $this->defaultParameters['apikey'] = $apikey;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApiKey()
    {
        return isset($this->defaultParameters['apikey']) ? $this->defaultParameters['apikey'] : null;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
    }

    /**
     * Get response
     *
     * @param string $method     The request method
     * @param string $uri        The URI to fetch
     * @param array  $parameters The Request parameters
     * @param array  $files      The files
     * @param array  $server     The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     * @param string $content    The raw body data
     *
     * @return ResponseInterface
     */
    abstract public function getResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null);

    /**
     * Get response as decoded JSON object
     *
     * @param string $method         The request method
     * @param string $uri            The URI to fetch
     * @param array  $parameters     The Request parameters
     * @param array  $files          The files
     * @param array  $server         The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     * @param string $content        The raw body data
     * @param int    $expectedStatus The expected status code
     *
     * @return object
     */
    public function getJsonResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $expectedStatus = 200)
    {
        $response = $this->getResponse($method, $uri, $parameters, $files, $server, $content);

        $json = $this->parseJsonResponse($this->lastRequest, $response);

        return $json;
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param int               $expectedStatus
     *
     * @return object
     * @throws Exception
     */
    protected function parseJsonResponse(RequestInterface $request, ResponseInterface $response, $expectedStatus = 200)
    {
        if ($response->getStatusCode() !== $expectedStatus) {
            throw Exception::create(
                sprintf('Response status %d does not match the expected status %d', $response->getStatusCode(), $expectedStatus),
                $request,
                $response
            );
        }

        $contentType = $response->getHeader('Content-Type');
        if (count($contentType) !== 1) {
            throw Exception::create(
                sprintf(
                    'Invalid content-type header (%s): %s',
                    $request->getUri(),
                    json_encode($contentType)
                ),
                $request,
                $response
            );
        }

        $contentType = $contentType[0];
        if ($contentType !== 'application/json') {
            throw Exception::create(
                sprintf(
                    'No JSON response header (%s): %d %s',
                    $contentType,
                    $response->getStatusCode(),
                    $request->getUri()
                ),
                $request,
                $response
            );
        }

        $json = null;
        $body = (string)$response->getBody();

        if (!empty($body)) {
            $json = json_decode($body);
        }

        if (null === $json) {
            throw Exception::create(
                sprintf('No valid JSON data: %s', $body),
                $request,
                $response
            );
        }

        return $json;
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function prepareParameters(array $parameters = [])
    {
        $parameters = array_replace($this->defaultParameters, $parameters);

        return $parameters;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    protected function prepareHeaders(array $headers = [])
    {
        $parameters = array_replace($this->defaultHeaders, $headers);

        return $parameters;
    }

    /**
     * Add REST parameters
     *
     * @param array       $parameters
     * @param string|null $condition
     * @param string|null $order
     * @param string|null $orderKey
     * @param int|null    $offset
     * @param int|null    $limit
     * @param string|null $groupBy
     * @param string|null $objectClass
     *
     * @return array
     */
    protected function buildRestParameters(array $parameters = [], $condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $objectClass = null)
    {
        if ($condition) {
            $parameters['condition'] = urlencode($condition);
        }

        if ($order) {
            $parameters['order'] = $order;
        }

        if ($orderKey) {
            $parameters['orderKey'] = $order;
        }

        if (null !== $offset) {
            $parameters['offset'] = (int)$offset;
        }

        if (null !== $limit) {
            $parameters['limit'] = (int)$limit;
        }

        if ($groupBy) {
            $parameters['groupBy'] = $groupBy;
        }

        if ($objectClass) {
            $parameters['objectClass'] = $objectClass;
        }

        return $parameters;
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     * @param bool $decode
     * @param null $objectClass
     *
     * @return Object[]
     * @throws Exception
     */
    public function getObjectList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true, $objectClass = null)
    {
        $params   = $this->buildRestParameters([], $condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);
        $response = $this->getJsonResponse('GET', '/object-list', $params);
        $response = $response->data ?: null;

        if (!is_array($response)) {
            throw new Exception('Response is empty');
        }

        $result = [];
        foreach ($response as $item) {
            $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Object\\Listing\\Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $object = new Object\AbstractObject();
                $wsDocument->reverseMap($object);
                $result[] = $object;
            }
        }

        return $result;
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     * @param bool $decode
     *
     * @return Asset[]
     * @throws Exception
     */
    public function getAssetList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true)
    {
        $params   = $this->buildRestParameters([], $condition, $order, $orderKey, $offset, $limit, $groupBy);
        $response = $this->getJsonResponse('GET', '/asset-list', $params);
        $response = $response->data ?: null;

        if (!is_array($response)) {
            throw new Exception('Response is empty');
        }

        $result = [];
        foreach ($response as $item) {
            $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Asset\\Listing\\Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $type  = $wsDocument->type;
                $type  = "\\Pimcore\\Model\\Asset\\" . ucfirst($type);
                $asset = new $type();
                $wsDocument->reverseMap($asset);
                $result[] = $asset;
            }
        }

        return $result;
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     * @param bool $decode
     *
     * @return Document[]
     * @throws Exception
     */
    public function getDocumentList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true)
    {
        $params = $this->buildRestParameters([], $condition, $order, $orderKey, $offset, $limit, $groupBy);
        $response = $this->getJsonResponse('GET', '/document-list', $params);
        $response = $response->data ?: null;

        if (!is_array($response)) {
            throw new Exception('Response is empty');
        }

        $result = [];
        foreach ($response as $item) {
            $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Document\\Listing\\Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $type = $wsDocument->type;
                $type = "\\Pimcore\\Model\\Document\\" . ucfirst($type);

                if (!Tool::classExists($type)) {
                    throw new Exception("Class " . $type . " does not exist");
                }

                $document = new $type();
                $wsDocument->reverseMap($document);
                $result[] = $document;
            }
        }

        return $result;
    }

    /**
     * @param      $id
     * @param bool $decode
     * @param null $idMapper
     *
     * @return mixed|Object\Folder
     * @throws Exception
     */
    public function getObjectById($id, $decode = true, $idMapper = null)
    {
        $params = [];

        if ($this->getEnableProfiling()) {
            $this->profilingInfo = null;
            $params['profiling'] = 1;
        }

        if ($this->getCondense()) {
            $params['condense'] = 1;
        }

        $response = $this->getJsonResponse('GET', sprintf('/object/id/%d', $id), $params);

        if ($this->getEnableProfiling()) {
            $this->profilingInfo = $response->profiling;
        }

        $response = $response->data;

        $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Object\\Concrete\\In", $response);

        if (!$decode) {
            return $wsDocument;
        }

        if ($wsDocument->type == "folder") {
            $object = new Object\Folder();
            $wsDocument->reverseMap($object);

            return $object;
        } elseif ($wsDocument->type == "object" || $wsDocument->type == "variant") {
            $classname = "Pimcore\\Model\\Object\\" . ucfirst($wsDocument->className);

            $object = \Pimcore::getDiContainer()->make($classname);

            if ($object instanceof Object\Concrete) {
                $curTime = microtime(true);
                $wsDocument->reverseMap($object, $this->getDisableMappingExceptions(), $idMapper);
                $timeConsumed = round(microtime(true) - $curTime, 3) * 1000;

                if ($this->profilingInfo) {
                    $this->profilingInfo->reverse = $timeConsumed;
                }

                return $object;
            } else {
                throw new Exception("Unable to decode object, could not instantiate Object with given class name [ $classname ]");
            }
        }
    }

    /**
     * @param      $id
     * @param bool $decode
     * @param null $idMapper
     *
     * @return mixed
     * @throws Exception
     */
    public function getDocumentById($id, $decode = true, $idMapper = null)
    {
        $response = $this->getJsonResponse('GET', sprintf('/document/id/%d', $id));
        $response = $response->data;

        if ($response->type === "folder") {
            $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Document\\Folder\\In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $doc = new Document\Folder();
            $wsDocument->reverseMap($doc, $this->getDisableMappingExceptions(), $idMapper);

            return $doc;
        } else {
            $type  = ucfirst($response->type);
            $class = "\\Pimcore\\Model\\Webservice\\Data\\Document\\" . $type . "\\In";

            $wsDocument = $this->fillWebserviceData($class, $response);
            if (!$decode) {
                return $wsDocument;
            }

            if (!empty($type)) {
                $type     = "\\Pimcore\\Model\\Document\\" . ucfirst($wsDocument->type);
                $document = new $type();
                $wsDocument->reverseMap($document, $this->getDisableMappingExceptions(), $idMapper);

                return $document;
            }
        }
    }

    /**
     * TODO
     *
     * @param        $id
     * @param bool   $decode
     * @param null   $idMapper
     * @param bool   $light
     * @param null   $thumbnail
     * @param bool   $tolerant
     * @param string $protocol
     *
     * @return mixed|Asset\Folder
     * @throws Exception
     */
    public function getAssetById($id, $decode = true, $idMapper = null, $light = false, $thumbnail = null, $tolerant = false, $protocol = "http://")
    {
        $params = [];
        if ($light) {
            $params['light'] = 1;
        }

        $response = $this->getJsonResponse('GET', sprintf('/asset/id/%d', $id), $params);
        $response = $response->data;

        if ($response->type === "folder") {
            $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Asset\\Folder\\In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $asset = new Asset\Folder();
            $wsDocument->reverseMap($asset, $this->getDisableMappingExceptions(), $idMapper);

            return $asset;
        } else {
            $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Asset\\File\\In", $response);
            if (!$decode) {
                return $wsDocument;
            }

            $type = $wsDocument->type;
            if (!empty($type)) {
                $type = "\\Pimcore\\Model\\Asset\\" . ucfirst($type);
                if (!Tool::classExists($type)) {
                    throw new Exception("Asset class " . $type . " does not exist");
                }

                /** @var Asset $asset */
                $asset = new $type();
                $wsDocument->reverseMap($asset, $this->getDisableMappingExceptions(), $idMapper);

                if ($light) {
                    $client = Tool::getHttpClient();
                    $client->setMethod("GET");

                    $assetType = $asset->getType();
                    $data      = null;

                    if ($assetType == "image" && strlen($thumbnail) > 0) {
                        // try to retrieve thumbnail first
                        // http://example.com/var/tmp/thumb_9__fancybox_thumb
                        $tmpPath = preg_replace("@^" . preg_quote(PIMCORE_WEB_ROOT, "@") . "@", "", PIMCORE_TEMPORARY_DIRECTORY);
                        $uri     = $protocol . $this->getHost() . $tmpPath . "/thumb_" . $asset->getId() . "__" . $thumbnail;
                        $client->setUri($uri);

                        if ($this->getLoggingEnabled()) {
                            print("    =>" . $uri . "\n");
                        }

                        $result = $client->request();
                        if ($result->getStatus() == 200) {
                            $data = $result->getBody();
                        }
                        $mimeType = $result->getHeader("Content-Type");

                        $filename = $asset->getFilename();

                        switch ($mimeType) {
                            case "image/tiff":
                                $filename = $this->changeExtension($filename, "tiff");
                                break;
                            case "image/jpeg":
                                $filename = $this->changeExtension($filename, "jpg");
                                break;
                            case "image/gif":
                                $filename = $this->changeExtension($filename, "gif");
                                break;
                            case "image/png":
                                $filename = $this->changeExtension($filename, "png");
                                break;

                        }

                        Logger::debug("mimeType: " . $mimeType);
                        $asset->setFilename($filename);
                    }

                    if (!$data) {
                        $path     = $wsDocument->path;
                        $filename = $wsDocument->filename;
                        $uri      = $protocol . $this->getHost() . "/var/assets" . $path . $filename;
                        $client->setUri($uri);
                        $result = $client->request();
                        if ($result->getStatus() != 200 && !$tolerant) {
                            throw new Exception("Could not retrieve asset");
                        }
                        $data = $result->getBody();
                    }
                    $asset->setData($data);
                }

                return $asset;
            }
        }
    }

    /**
     * Creates a new document.
     *
     * @param Document $document
     *
     * @return mixed json encoded success value and id
     */
    public function createDocument(Document $document)
    {
        $type      = $document->getType();
        $typeUpper = ucfirst($type);
        $className = "\\Pimcore\\Model\\Webservice\\Data\\Document\\" . $typeUpper . "\\In";

        $wsDocument  = Webservice\Data\Mapper::map($document, $className, "out");
        $encodedData = json_encode($wsDocument);

        $response = $this->getJsonResponse('POST', '/document', [], [], [], $encodedData);

        return $response;
    }

    /**
     * Creates a new object.
     *
     * @param Object\AbstractObject $object
     *
     * @return mixed json encoded success value and id
     */
    public function createObjectConcrete(Object\AbstractObject $object)
    {
        if ($object->getType() === "folder") {
            $documentType = "\\Pimcore\\Model\\Webservice\\Data\\Object\\Folder\\Out";
        } else {
            $documentType = "\\Pimcore\\Model\\Webservice\\Data\\Object\\Concrete\\Out";
        }

        $wsDocument  = Webservice\Data\Mapper::map($object, $documentType, "out");
        $encodedData = json_encode($wsDocument);

        $response = $this->getJsonResponse('POST', '/object', [], [], [], $encodedData);

        return $response;
    }

    /**
     * @param Asset $asset
     *
     * @return mixed|null|string
     * @throws Exception
     * @throws \Exception
     */
    public function createAsset(Asset $asset)
    {
        if ($asset->getType() === "folder") {
            $documentType = "\\Pimcore\\Model\\Webservice\\Data\\Asset\\Folder\\Out";
        } else {
            $documentType = "\\Pimcore\\Model\\Webservice\\Data\\Asset\\File\\Out";
        }

        $wsDocument  = Webservice\Data\Mapper::map($asset, $documentType, "out");
        $encodedData = json_encode($wsDocument);

        $response = $this->getJsonResponse('POST', '/asset', [], [], [], $encodedData);

        return $response;
    }

    /**
     * Deletes an object.
     *
     * @param $objectId
     *
     * @return mixed json encoded success value and id
     */
    public function deleteObject($objectId)
    {
        $response = $this->getJsonResponse('DELETE', sprintf('/object/id/%s', $objectId));

        return $response;
    }

    /**
     * Deletes an asset.
     *
     * @param $assetId
     *
     * @return mixed json encoded success value and id
     */
    public function deleteAsset($assetId)
    {
        $response = $this->getJsonResponse('DELETE', sprintf('/asset/id/%s', $assetId));

        return $response;
    }

    /**
     * Deletes a document.
     *
     * @param $documentId
     *
     * @return mixed json encoded success value and id
     */
    public function deleteDocument($documentId)
    {
        $response = $this->getJsonResponse('DELETE', sprintf('/document/id/%s', $documentId));

        return $response;
    }

    /**
     * Creates a new object folder.
     *
     * @param Object\Folder $objectFolder object folder.
     *
     * @return mixed
     */
    public function createObjectFolder(Object\Folder $objectFolder)
    {
        return $this->createObjectConcrete($objectFolder);
    }

    /**
     * Creates a new document folder.
     *
     * @param Document\Folder $documentFolder document folder.
     *
     * @return mixed
     */
    public function createDocumentFolder(Document\Folder $documentFolder)
    {
        return $this->createDocument($documentFolder);
    }

    /**
     * Creates a new asset folder.
     *
     * @param Asset\Folder $assetFolder document folder.
     *
     * @return mixed
     */
    public function createAssetFolder(Asset\Folder $assetFolder)
    {
        return $this->createAsset($assetFolder);
    }

    /**
     * @param      $id
     * @param bool $decode
     *
     * @return mixed|null|Object\ClassDefinition|string
     * @throws Exception
     */
    public function getClassById($id, $decode = true)
    {
        $response     = $this->getJsonResponse('GET', sprintf('/class/id/%d', $id));
        $responseData = $response->data;

        if (!$decode) {
            return $response;
        }

        $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\In", $responseData);

        $class = new Object\ClassDefinition();
        $wsDocument->reverseMap($class);

        return $class;
    }

    /**
     * @param      $id
     * @param bool $decode
     *
     * @return mixed|Object\ClassDefinition
     * @throws Exception
     */
    public function getObjectMetaById($id, $decode = true)
    {
        $response = $this->getJsonResponse('GET', sprintf('/object-meta/id/%d', $id));
        $response = $response->data;

        $wsDocument = $this->fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\In", $response);

        if (!$decode) {
            return $wsDocument;
        }

        $class = new Object\ClassDefinition();
        $wsDocument->reverseMap($class);

        return $class;
    }

    /**
     * @param null $condition
     * @param null $groupBy
     *
     * @return mixed
     * @throws Exception
     */
    public function getAssetCount($condition = null, $groupBy = null)
    {
        $params = $this->buildRestParameters([], $condition, null, null, null, null, $groupBy, null);

        $response = $this->getJsonResponse('GET', '/asset-count', $params);
        $response = (array)$response;

        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve asset count");
        }

        return $response["data"]->totalCount;
    }

    /**
     * @param null $condition
     * @param null $groupBy
     *
     * @return mixed
     * @throws Exception
     */
    public function getDocumentCount($condition = null, $groupBy = null)
    {
        $params = $this->buildRestParameters([], $condition, null, null, null, null, $groupBy, null);

        $response = $this->getJsonResponse('GET', '/document-count', $params);
        $response = (array)$response;

        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve document count");
        }

        return $response["data"]->totalCount;
    }

    /**
     * @param null $condition
     * @param null $groupBy
     * @param null $objectClass
     *
     * @return mixed
     * @throws Exception
     */
    public function getObjectCount($condition = null, $groupBy = null, $objectClass = null)
    {
        $params = $this->buildRestParameters([], $condition, null, null, null, null, $groupBy, $objectClass);

        $response = $this->getJsonResponse('GET', '/object-count', $params);
        $response = (array)$response;

        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve object count");
        }

        return $response["data"]->totalCount;
    }

    /**
     * Returns the current user
     *
     * @return mixed
     */
    public function getUser()
    {
        $response = $this->getJsonResponse('GET', '/user');
        $response = ["success" => true, "data" => $response->data];

        return $response;
    }

    /**
     * @return mixed|null|string
     * @throws Exception
     */
    public function getFieldCollections()
    {
        $response = $this->getJsonResponse('GET', '/field-collections');

        return $response;
    }

    /**
     * @param $id
     *
     * @return mixed|null|string
     * @throws Exception
     */
    public function getFieldCollection($id)
    {
        $response = $this->getJsonResponse('GET', sprintf('/field-collection/id/%d', $id));

        return $response;
    }

    /**
     * Returns a list of defined classes
     *
     * @return mixed
     */
    public function getClasses()
    {
        $response = $this->getJsonResponse('GET', '/classes');

        return $response;
    }

    /**
     * Returns a list of defined object bricks
     *
     * @return mixed
     */
    public function getObjectBricks()
    {
        $response = $this->getJsonResponse('GET', '/object-bricks');

        return $response;
    }

    /**
     * Returns the given object brick definition
     *
     * @param $id
     *
     * @return mixed
     */
    public function getObjectBrick($id)
    {
        $response = $this->getJsonResponse('GET', sprintf('/object-brick/id/%d', $id));

        return $response;
    }

    /**
     * Returns the current server time
     *
     * @return mixed
     */
    public function getCurrentTime()
    {
        $response = $this->getJsonResponse('GET', '/system-clock');

        return $response;
    }

    /**
     * Returns a list of image thumbnail configurations.
     *
     * @return mixed
     */
    public function getImageThumbnails()
    {
        $response = $this->getJsonResponse('GET', '/image-thumbnails');

        return $response;
    }

    /**
     * Returns the image thumbnail configuration with the given ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function getImageThumbnail($id)
    {
        $params = [
            'id' => $id
        ];

        $response = $this->getJsonResponse('GET', '/image-thumbnail', $params);

        return $response;
    }

    /**
     * Returns: server-info including pimcore version, current time and extension data.
     *
     * @return mixed
     */
    public function getServerInfo()
    {
        $response = $this->getJsonResponse('GET', '/server-info');

        return $response;
    }

    /**
     * @param $wsData
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    private function map($wsData, $data)
    {
        if (!($data instanceof \stdClass)) {
            throw new Exception("Ws data format error");
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $tmp = [];

                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $object = new \stdClass();
                        $tmp[]  = $this->map($object, $subvalue);
                    } else {
                        $tmp[$subkey] = $subvalue;
                    }
                }
                $value = $tmp;
            }

            $wsData->$key = $value;
        }

        return $wsData;
    }

    /**
     * @param $class
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    private function fillWebserviceData($class, $data)
    {
        $class = "\\" . ltrim($class, "\\"); // add global namespace

        if (!Tool::classExists($class)) {
            throw new Exception("cannot fill web service data " . $class);
        }

        $wsData = new $class();

        return $this->map($wsData, $data);
    }

    /**
     * @param $filename
     * @param $extension
     *
     * @return string
     */
    private function changeExtension($filename, $extension)
    {
        $idx = strrpos($filename, ".");
        if ($idx >= 0) {
            $filename = substr($filename, 0, $idx) . "." . $extension;
        }

        return $filename;
    }
}
