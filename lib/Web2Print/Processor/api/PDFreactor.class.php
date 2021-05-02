<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace com\realobjects\pdfreactor\webservice\client;

/**
 * @internal
 */
class PDFreactor
{
    public $url;

    public function __construct($url = 'http://localhost:9423/service/rest')
    {
        $this->url = $url;
        if ($url == null) {
            $this->url = 'http://localhost:9423/service/rest';
        }
        if (substr($this->url, -1) == '/') {
            $this->url = substr($this->url, 0, -1);
        }
        $this->apiKey = null;
    }

    public function convert($config, & $connectionSettings = null)
    {
        $url = $this->url .'/convert.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        if (!is_null($config)) {
            $config['clientName'] = 'PHP';
            $config['clientVersion'] = PDFreactor::VERSION;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'POST',
                'content' => json_encode($config),
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 400) {
            throw $this->_createServerException($errorId, 'Invalid client data. '.$result->error, $result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 413) {
            throw $this->_createServerException($errorId, 'The configuration is too large to process.', $result);
        } elseif ($status == 500) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }

        return $result;
    }

    public function convertAsBinary($config, & $wh = null, & $connectionSettings = null)
    {
        $url = $this->url .'/convert.bin';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        if (!is_null($config)) {
            $config['clientName'] = 'PHP';
            $config['clientVersion'] = PDFreactor::VERSION;
        }
        $useStream = true;
        if (is_array($wh)) {
            $connectionSettings = $wh;
            $useStream = false;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'POST',
                'content' => json_encode($config),
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode && ($wh == null || !$useStream)) {
            $result = stream_get_contents($rh);
            fclose($rh);
        }
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result);
        } elseif ($status == 400) {
            throw $this->_createServerException($errorId, 'Invalid client data. '.$result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result);
        } elseif ($status == 413) {
            throw $this->_createServerException($errorId, 'The configuration is too large to process.');
        } elseif ($status == 500) {
            throw $this->_createServerException($errorId, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.');
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').');
        }
        if (!$errorMode && $wh != null && $useStream) {
            while (!feof($rh)) {
                if (fwrite($wh, fread($rh, 1024)) === false) {
                    return null;
                }
            }
            fclose($rh);
            fclose($wh);
        }

        return $result;
    }

    public function convertAsync($config, & $connectionSettings = null)
    {
        $documentId = null;
        $url = $this->url .'/convert/async.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        if (!is_null($config)) {
            $config['clientName'] = 'PHP';
            $config['clientVersion'] = PDFreactor::VERSION;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'POST',
                'content' => json_encode($config),
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 400) {
            throw $this->_createServerException($errorId, 'Invalid client data. '.$result->error, $result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 413) {
            throw $this->_createServerException($errorId, 'The configuration is too large to process.', $result);
        } elseif ($status == 500) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'Asynchronous conversions are unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }
        foreach ($http_response_header as $header) {
            $t = explode(':', $header, 2);
            if (isset($t[1])) {
                $headerName = trim($t[0]);
                if ($headerName == 'Location') {
                    $documentId = trim(substr($t[1], strrpos($t[1], '/') + 1));
                }
            }
            if (preg_match('/^Set-Cookie:\s*([^;]+)/', $header, $matches)) {
                parse_str($matches[1], $tmp);
                $keepDocument = false;
                if (isset($config->{'keepDocument'})) {
                    $keepDocument = $config->{'keepDocument'};
                }
                if (isset($connectionSettings)) {
                    if (empty($connectionSettings['cookies'])) {
                        $connectionSettings['cookies'] = [];
                    }
                    foreach ($tmp as $name => $value) {
                        $connectionSettings['cookies'][$name] = $value;
                    }
                }
            }
        }

        return $documentId;
    }

    public function getProgress($documentId, & $connectionSettings = null)
    {
        if (is_null($documentId)) {
            throw new ClientException('No conversion was triggered.');
        }
        $url = $this->url .'/progress/' . $documentId . '.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 404) {
            throw $this->_createServerException($errorId, 'Document with the given ID was not found. '.$result->error, $result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }

        return $result;
    }

    public function getDocument($documentId, & $connectionSettings = null)
    {
        if (is_null($documentId)) {
            throw new ClientException('No conversion was triggered.');
        }
        $url = $this->url .'/document/' . $documentId . '.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 404) {
            throw $this->_createServerException($errorId, 'Document with the given ID was not found. '.$result->error, $result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }

        return $result;
    }

    public function getDocumentAsBinary($documentId, & $wh = null, & $connectionSettings = null)
    {
        if (is_null($documentId)) {
            throw new ClientException('No conversion was triggered.');
        }
        $url = $this->url .'/document/' . $documentId . '.bin';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $useStream = true;
        if (is_array($wh)) {
            $connectionSettings = $wh;
            $useStream = false;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode && ($wh == null || !$useStream)) {
            $result = stream_get_contents($rh);
            fclose($rh);
        }
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result);
        } elseif ($status == 404) {
            throw $this->_createServerException($errorId, 'Document with the given ID was not found. '.$result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.');
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').');
        }
        if (!$errorMode && $wh != null && $useStream) {
            while (!feof($rh)) {
                if (fwrite($wh, fread($rh, 1024)) === false) {
                    return null;
                }
            }
            fclose($rh);
            fclose($wh);
        }

        return $result;
    }

    public function getDocumentMetadata($documentId, & $connectionSettings = null)
    {
        if (is_null($documentId)) {
            throw new ClientException('No conversion was triggered.');
        }
        $url = $this->url .'/document/metadata/' . $documentId . '.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 422) {
            throw $this->_createServerException($errorId, $result->error, $result);
        } elseif ($status == 404) {
            throw $this->_createServerException($errorId, 'Document with the given ID was not found. '.$result->error, $result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }

        return $result;
    }

    public function deleteDocument($documentId, & $connectionSettings = null)
    {
        if (is_null($documentId)) {
            throw new ClientException('No conversion was triggered.');
        }
        $url = $this->url .'/document/' . $documentId . '.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'DELETE',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 404) {
            throw $this->_createServerException($errorId, 'Document with the given ID was not found. '.$result->error, $result);
        } elseif ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }
    }

    public function getVersion(& $connectionSettings = null)
    {
        $url = $this->url .'/version.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }

        return $result;
    }

    public function getStatus(& $connectionSettings = null)
    {
        $url = $this->url .'/status.json';
        if (!is_null($this->apiKey)) {
            $url .= '?apiKey=' . $this->apiKey;
        }
        $headerStr = '';
        $cookieStr = '';
        if (!empty($connectionSettings) && !empty($connectionSettings['headers'])) {
            foreach ($connectionSettings['headers'] as $name => $value) {
                $lcName = strtolower($name);
                if ($lcName !== 'user-agent' && $lcName !== 'content-type' && $lcName !== 'range') {
                    $headerStr .= $name . ': ' . $value . "\r\n";
                }
            }
        }
        if (!empty($connectionSettings) && !empty($connectionSettings['cookies'])) {
            foreach ($connectionSettings['cookies'] as $name => $value) {
                $cookieStr .= $name . '=' . $value . '; ';
            }
        }
        $headerStr .= "Content-Type: application/json\r\n";
        $headerStr .= "User-Agent: PDFreactor PHP API v8\r\n";
        $headerStr .= "X-RO-User-Agent: PDFreactor PHP API v8\r\n";
        if (!empty($connectionSettings) || !empty($cookieStr)) {
            $headerStr .= 'Cookie: ' . substr($cookieStr, 0, -2);
        }
        $options = [
            'http' => [
                'header' => $headerStr,
                'follow_location' => false,
                'max_redirects' => 0,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $result = null;
        $errorMode = true;
        $rh = fopen($url, false, false, $context);
        if (!isset($http_response_header)) {
            $lastError = error_get_last();
            throw new UnreachableServiceException('Error connecting to PDFreactor Web Service at ' . $this->url . '. Please make sure the PDFreactor Web Service is installed and running (Error: ' . $lastError['message'] . ')');
        }
        $status = intval(substr($http_response_header[0], 9, 3));
        if ($status >= 200 && $status <= 204) {
            $errorMode = false;
        }
        if (!$errorMode) {
            $result = json_decode(stream_get_contents($rh));
        }
        fclose($rh);
        $errorId = null;
        if ($errorMode) {
            foreach ($http_response_header as $header) {
                $t = explode(':', $header, 2);
                if (isset($t[1])) {
                    $headerName = trim($t[0]);
                    if ($headerName == 'X-RO-Error-ID') {
                        $errorId = trim($t[1]);
                    }
                }
            }
        }
        if ($status == 401) {
            throw $this->_createServerException($errorId, 'Unauthorized. '.$result->error, $result);
        } elseif ($status == 503) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service is unavailable.', $result);
        } elseif ($status > 400) {
            throw $this->_createServerException($errorId, 'PDFreactor Web Service error (status: ' . $status . ').', $result);
        }
    }

    public function getDocumentUrl($documentId)
    {
        if (!is_null($documentId)) {
            return $this->url . '/document/' . $documentId;
        }

        return null;
    }

    public function getProgressUrl($documentId)
    {
        if (!is_null($documentId)) {
            return $this->url . '/progress/' . $documentId;
        }

        return null;
    }

    const VERSION = 8;

    public function __get($name)
    {
        if ($name == 'apiKey') {
            return isset($this->$name) ? $this->$name : null;
        }
    }

    public function _createServerException($errorId = null, $message = null, $result = null)
    {
        switch ($errorId) {
        case 'asyncUnavailable':
            return new AsyncUnavailableException($errorId, $message, $result);
        case 'badRequest':
            return new BadRequestException($errorId, $message, $result);
        case 'commandRejected':
            return new CommandRejectedException($errorId, $message, $result);
        case 'conversionAborted':
            return new ConversionAbortedException($errorId, $message, $result);
        case 'conversionFailure':
            return new ConversionFailureException($errorId, $message, $result);
        case 'documentNotFound':
            return new DocumentNotFoundException($errorId, $message, $result);
        case 'resourceNotFound':
            return new ResourceNotFoundException($errorId, $message, $result);
        case 'invalidClient':
            return new InvalidClientException($errorId, $message, $result);
        case 'invalidConfiguration':
            return new InvalidConfigurationException($errorId, $message, $result);
        case 'noConfiguration':
            return new NoConfigurationException($errorId, $message, $result);
        case 'noInputDocument':
            return new NoInputDocumentException($errorId, $message, $result);
        case 'requestRejected':
            return new RequestRejectedException($errorId, $message, $result);
        case 'serviceUnavailable':
            return new ServiceUnavailableException($errorId, $message, $result);
        case 'unauthorized':
            return new UnauthorizedException($errorId, $message, $result);
        case 'unprocessableConfiguration':
            return new UnprocessableConfigurationException($errorId, $message, $result);
        case 'unprocessableInput':
            return new UnprocessableInputException($errorId, $message, $result);
        case 'notAcceptable':
            return new NotAcceptableException($errorId, $message, $result);
        default:
            return new ServerException($errorId, $message, $result);
    }
    }
}
class PDFreactorWebserviceException extends \Exception
{
    public $result;

    public function __construct($message)
    {
        parent::__construct($message == null ? 'Unknown PDFreactor Web Service error' : $message);
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}
class ServerException extends PDFreactorWebserviceException
{
    public $result;

    public function __construct($errorId = null, $message = null, $result = null)
    {
        $this->result = $result;
        $this->errorId = $errorId;
        parent::__construct($message == null ? 'Unknown PDFreactor Web Service error' : $message);
    }

    public function getResult()
    {
        return $this->result;
    }
}
class ClientException extends PDFreactorWebserviceException
{
    public $result;

    public function __construct($message)
    {
        parent::__construct($message);
    }
}
class AsyncUnavailableException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class BadRequestException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class CommandRejectedException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class ConversionAbortedException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class ConversionFailureException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class DocumentNotFoundException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class ResourceNotFoundException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class InvalidClientException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class InvalidConfigurationException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class NoConfigurationException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class NoInputDocumentException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class RequestRejectedException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class ServiceUnavailableException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class UnauthorizedException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class UnprocessableConfigurationException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class UnprocessableInputException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class NotAcceptableException extends ServerException
{
    public function __construct($errorId, $message, $result)
    {
        parent::__construct($errorId, $message, $result);
    }
}
class UnreachableServiceException extends ClientException
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
class InvalidServiceException extends ClientException
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
abstract class CallbackType
{
    const FINISH = 'FINISH';
    const PROGRESS = 'PROGRESS';
    const START = 'START';
}
abstract class Cleanup
{
    const CYBERNEKO = 'CYBERNEKO';
    const JTIDY = 'JTIDY';
    const NONE = 'NONE';
    const TAGSOUP = 'TAGSOUP';
}
abstract class ColorSpace
{
    const CMYK = 'CMYK';
    const RGB = 'RGB';
}
abstract class Conformance
{
    const PDF = 'PDF';
    const PDFA1A = 'PDFA1A';
    const PDFA1A_PDFUA1 = 'PDFA1A_PDFUA1';
    const PDFA1B = 'PDFA1B';
    const PDFA2A = 'PDFA2A';
    const PDFA2A_PDFUA1 = 'PDFA2A_PDFUA1';
    const PDFA2B = 'PDFA2B';
    const PDFA2U = 'PDFA2U';
    const PDFA3A = 'PDFA3A';
    const PDFA3A_PDFUA1 = 'PDFA3A_PDFUA1';
    const PDFA3B = 'PDFA3B';
    const PDFA3U = 'PDFA3U';
    const PDFUA1 = 'PDFUA1';
    const PDFX1A_2001 = 'PDFX1A_2001';
    const PDFX1A_2003 = 'PDFX1A_2003';
    const PDFX3_2002 = 'PDFX3_2002';
    const PDFX3_2003 = 'PDFX3_2003';
    const PDFX4 = 'PDFX4';
    const PDFX4P = 'PDFX4P';
}
abstract class ContentType
{
    const BINARY = 'BINARY';
    const BMP = 'BMP';
    const GIF = 'GIF';
    const HTML = 'HTML';
    const JPEG = 'JPEG';
    const JSON = 'JSON';
    const NONE = 'NONE';
    const PDF = 'PDF';
    const PNG = 'PNG';
    const TEXT = 'TEXT';
    const TIFF = 'TIFF';
    const XML = 'XML';
}
abstract class CssPropertySupport
{
    const ALL = 'ALL';
    const HTML = 'HTML';
    const HTML_THIRD_PARTY = 'HTML_THIRD_PARTY';
    const HTML_THIRD_PARTY_LENIENT = 'HTML_THIRD_PARTY_LENIENT';
}
abstract class Doctype
{
    const AUTODETECT = 'AUTODETECT';
    const HTML5 = 'HTML5';
    const XHTML = 'XHTML';
    const XML = 'XML';
}
abstract class Encryption
{
    const NONE = 'NONE';
    const TYPE_128 = 'TYPE_128';
    const TYPE_40 = 'TYPE_40';
}
abstract class ErrorPolicy
{
    const CONFORMANCE_VALIDATION_UNAVAILABLE = 'CONFORMANCE_VALIDATION_UNAVAILABLE';
    const LICENSE = 'LICENSE';
    const MISSING_RESOURCE = 'MISSING_RESOURCE';
    const UNCAUGHT_JAVASCRIPT_EXCEPTION = 'UNCAUGHT_JAVASCRIPT_EXCEPTION';
}
abstract class ExceedingContentAgainst
{
    const NONE = 'NONE';
    const PAGE_BORDERS = 'PAGE_BORDERS';
    const PAGE_CONTENT = 'PAGE_CONTENT';
    const PARENT = 'PARENT';
}
abstract class ExceedingContentAnalyze
{
    const CONTENT = 'CONTENT';
    const CONTENT_AND_BOXES = 'CONTENT_AND_BOXES';
    const CONTENT_AND_STATIC_BOXES = 'CONTENT_AND_STATIC_BOXES';
    const NONE = 'NONE';
}
abstract class HttpsMode
{
    const LENIENT = 'LENIENT';
    const STRICT = 'STRICT';
}
abstract class JavaScriptDebugMode
{
    const EXCEPTIONS = 'EXCEPTIONS';
    const FUNCTIONS = 'FUNCTIONS';
    const LINES = 'LINES';
    const NONE = 'NONE';
    const POSITIONS = 'POSITIONS';
}
abstract class JavaScriptMode
{
    const DISABLED = 'DISABLED';
    const ENABLED = 'ENABLED';
    const ENABLED_NO_LAYOUT = 'ENABLED_NO_LAYOUT';
    const ENABLED_REAL_TIME = 'ENABLED_REAL_TIME';
    const ENABLED_TIME_LAPSE = 'ENABLED_TIME_LAPSE';
}
abstract class KeystoreType
{
    const JKS = 'JKS';
    const PKCS12 = 'PKCS12';
}
abstract class LogLevel
{
    const DEBUG = 'DEBUG';
    const FATAL = 'FATAL';
    const INFO = 'INFO';
    const NONE = 'NONE';
    const PERFORMANCE = 'PERFORMANCE';
    const WARN = 'WARN';
}
abstract class MediaFeature
{
    const ASPECT_RATIO = 'ASPECT_RATIO';
    const COLOR = 'COLOR';
    const COLOR_INDEX = 'COLOR_INDEX';
    const DEVICE_ASPECT_RATIO = 'DEVICE_ASPECT_RATIO';
    const DEVICE_HEIGHT = 'DEVICE_HEIGHT';
    const DEVICE_WIDTH = 'DEVICE_WIDTH';
    const GRID = 'GRID';
    const HEIGHT = 'HEIGHT';
    const MONOCHROME = 'MONOCHROME';
    const ORIENTATION = 'ORIENTATION';
    const RESOLUTION = 'RESOLUTION';
    const WIDTH = 'WIDTH';
}
abstract class MergeMode
{
    const APPEND = 'APPEND';
    const ARRANGE = 'ARRANGE';
    const OVERLAY = 'OVERLAY';
    const OVERLAY_BELOW = 'OVERLAY_BELOW';
    const PREPEND = 'PREPEND';
}
abstract class OutputIntentDefaultProfile
{
    const FOGRA39 = 'Coated FOGRA39';
    const GRACOL = 'Coated GRACoL 2006';
    const IFRA = 'ISO News print 26% (IFRA)';
    const JAPAN = 'Japan Color 2001 Coated';
    const JAPAN_NEWSPAPER = 'Japan Color 2001 Newspaper';
    const JAPAN_UNCOATED = 'Japan Color 2001 Uncoated';
    const JAPAN_WEB = 'Japan Web Coated (Ad)';
    const SWOP = 'US Web Coated (SWOP) v2';
    const SWOP_3 = 'Web Coated SWOP 2006 Grade 3 Paper';
}
abstract class OutputType
{
    const BMP = 'BMP';
    const GIF = 'GIF';
    const GIF_DITHERED = 'GIF_DITHERED';
    const JPEG = 'JPEG';
    const PDF = 'PDF';
    const PNG = 'PNG';
    const PNG_AI = 'PNG_AI';
    const PNG_TRANSPARENT = 'PNG_TRANSPARENT';
    const PNG_TRANSPARENT_AI = 'PNG_TRANSPARENT_AI';
    const TIFF_CCITT_1D = 'TIFF_CCITT_1D';
    const TIFF_CCITT_1D_DITHERED = 'TIFF_CCITT_1D_DITHERED';
    const TIFF_CCITT_GROUP_3 = 'TIFF_CCITT_GROUP_3';
    const TIFF_CCITT_GROUP_3_DITHERED = 'TIFF_CCITT_GROUP_3_DITHERED';
    const TIFF_CCITT_GROUP_4 = 'TIFF_CCITT_GROUP_4';
    const TIFF_CCITT_GROUP_4_DITHERED = 'TIFF_CCITT_GROUP_4_DITHERED';
    const TIFF_LZW = 'TIFF_LZW';
    const TIFF_PACKBITS = 'TIFF_PACKBITS';
    const TIFF_UNCOMPRESSED = 'TIFF_UNCOMPRESSED';
}
abstract class OverlayRepeat
{
    const ALL_PAGES = 'ALL_PAGES';
    const LAST_PAGE = 'LAST_PAGE';
    const NONE = 'NONE';
    const TRIM = 'TRIM';
}
abstract class PageOrder
{
    const BOOKLET = 'BOOKLET';
    const BOOKLET_RTL = 'BOOKLET_RTL';
    const EVEN = 'EVEN';
    const ODD = 'ODD';
    const REVERSE = 'REVERSE';
}
abstract class PagesPerSheetDirection
{
    const DOWN_LEFT = 'DOWN_LEFT';
    const DOWN_RIGHT = 'DOWN_RIGHT';
    const LEFT_DOWN = 'LEFT_DOWN';
    const LEFT_UP = 'LEFT_UP';
    const RIGHT_DOWN = 'RIGHT_DOWN';
    const RIGHT_UP = 'RIGHT_UP';
    const UP_LEFT = 'UP_LEFT';
    const UP_RIGHT = 'UP_RIGHT';
}
abstract class PdfScriptTriggerEvent
{
    const AFTER_PRINT = 'AFTER_PRINT';
    const AFTER_SAVE = 'AFTER_SAVE';
    const BEFORE_PRINT = 'BEFORE_PRINT';
    const BEFORE_SAVE = 'BEFORE_SAVE';
    const CLOSE = 'CLOSE';
    const OPEN = 'OPEN';
}
abstract class ProcessingPreferences
{
    const SAVE_MEMORY_IMAGES = 'SAVE_MEMORY_IMAGES';
}
abstract class QuirksMode
{
    const DETECT = 'DETECT';
    const QUIRKS = 'QUIRKS';
    const STANDARDS = 'STANDARDS';
}
abstract class ResolutionUnit
{
    const DPCM = 'DPCM';
    const DPI = 'DPI';
    const DPPX = 'DPPX';
    const TDPCM = 'TDPCM';
    const TDPI = 'TDPI';
    const TDPPX = 'TDPPX';
}
abstract class ResourceType
{
    const ATTACHMENT = 'ATTACHMENT';
    const DOCUMENT = 'DOCUMENT';
    const FONT = 'FONT';
    const ICC_PROFILE = 'ICC_PROFILE';
    const IFRAME = 'IFRAME';
    const IMAGE = 'IMAGE';
    const LICENSEKEY = 'LICENSEKEY';
    const MERGE_DOCUMENT = 'MERGE_DOCUMENT';
    const OBJECT = 'OBJECT';
    const RUNNING_DOCUMENT = 'RUNNING_DOCUMENT';
    const SCRIPT = 'SCRIPT';
    const STYLESHEET = 'STYLESHEET';
    const UNKNOWN = 'UNKNOWN';
    const XHR = 'XHR';
}
abstract class SigningMode
{
    const SELF_SIGNED = 'SELF_SIGNED';
    const VERISIGN_SIGNED = 'VERISIGN_SIGNED';
    const WINCER_SIGNED = 'WINCER_SIGNED';
}
abstract class ViewerPreferences
{
    const CENTER_WINDOW = 'CENTER_WINDOW';
    const DIRECTION_L2R = 'DIRECTION_L2R';
    const DIRECTION_R2L = 'DIRECTION_R2L';
    const DISPLAY_DOC_TITLE = 'DISPLAY_DOC_TITLE';
    const DUPLEX_FLIP_LONG_EDGE = 'DUPLEX_FLIP_LONG_EDGE';
    const DUPLEX_FLIP_SHORT_EDGE = 'DUPLEX_FLIP_SHORT_EDGE';
    const DUPLEX_SIMPLEX = 'DUPLEX_SIMPLEX';
    const FIT_WINDOW = 'FIT_WINDOW';
    const HIDE_MENUBAR = 'HIDE_MENUBAR';
    const HIDE_TOOLBAR = 'HIDE_TOOLBAR';
    const HIDE_WINDOW_UI = 'HIDE_WINDOW_UI';
    const NON_FULLSCREEN_PAGE_MODE_USE_NONE = 'NON_FULLSCREEN_PAGE_MODE_USE_NONE';
    const NON_FULLSCREEN_PAGE_MODE_USE_OC = 'NON_FULLSCREEN_PAGE_MODE_USE_OC';
    const NON_FULLSCREEN_PAGE_MODE_USE_OUTLINES = 'NON_FULLSCREEN_PAGE_MODE_USE_OUTLINES';
    const NON_FULLSCREEN_PAGE_MODE_USE_THUMBS = 'NON_FULLSCREEN_PAGE_MODE_USE_THUMBS';
    const PAGE_LAYOUT_ONE_COLUMN = 'PAGE_LAYOUT_ONE_COLUMN';
    const PAGE_LAYOUT_SINGLE_PAGE = 'PAGE_LAYOUT_SINGLE_PAGE';
    const PAGE_LAYOUT_TWO_COLUMN_LEFT = 'PAGE_LAYOUT_TWO_COLUMN_LEFT';
    const PAGE_LAYOUT_TWO_COLUMN_RIGHT = 'PAGE_LAYOUT_TWO_COLUMN_RIGHT';
    const PAGE_LAYOUT_TWO_PAGE_LEFT = 'PAGE_LAYOUT_TWO_PAGE_LEFT';
    const PAGE_LAYOUT_TWO_PAGE_RIGHT = 'PAGE_LAYOUT_TWO_PAGE_RIGHT';
    const PAGE_MODE_FULLSCREEN = 'PAGE_MODE_FULLSCREEN';
    const PAGE_MODE_USE_ATTACHMENTS = 'PAGE_MODE_USE_ATTACHMENTS';
    const PAGE_MODE_USE_NONE = 'PAGE_MODE_USE_NONE';
    const PAGE_MODE_USE_OC = 'PAGE_MODE_USE_OC';
    const PAGE_MODE_USE_OUTLINES = 'PAGE_MODE_USE_OUTLINES';
    const PAGE_MODE_USE_THUMBS = 'PAGE_MODE_USE_THUMBS';
    const PICKTRAYBYPDFSIZE_FALSE = 'PICKTRAYBYPDFSIZE_FALSE';
    const PICKTRAYBYPDFSIZE_TRUE = 'PICKTRAYBYPDFSIZE_TRUE';
    const PRINTSCALING_APPDEFAULT = 'PRINTSCALING_APPDEFAULT';
    const PRINTSCALING_NONE = 'PRINTSCALING_NONE';
}
abstract class XmpPriority
{
    const HIGH = 'HIGH';
    const LOW = 'LOW';
    const NONE = 'NONE';
}
