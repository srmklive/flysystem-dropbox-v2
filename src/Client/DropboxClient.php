<?php

namespace Srmklive\Dropbox\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use Illuminate\Support\Collection;
use Srmklive\Dropbox\Exceptions\BadRequest;

class DropboxClient
{
    const THUMBNAIL_FORMAT_JPEG = 'jpeg';
    const THUMBNAIL_FORMAT_PNG = 'png';

    const THUMBNAIL_SIZE_XS = 'w32h32';
    const THUMBNAIL_SIZE_S = 'w64h64';
    const THUMBNAIL_SIZE_M = 'w128h128';
    const THUMBNAIL_SIZE_L = 'w640h480';
    const THUMBNAIL_SIZE_XL = 'w1024h768';

    /** @var \GuzzleHttp\Client */
    protected $client;

    /**
     * Dropbox OAuth access token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Dropbox API v2 Url.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Dropbox content API v2 url for uploading content.
     *
     * @var string
     */
    protected $apiContentUrl;

    /**
     * Dropbox API v2 endpoint.
     *
     * @var string
     */
    protected $apiEndpoint;

    /**
     * Collection containing Dropbox API request data.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $request;

    /**
     * DropboxClient constructor.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->setAccessToken($token);

        $this->client = new HttpClient([
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
            ]
        ]);

        $this->apiUrl = "https://api.dropboxapi.com/2/";
        $this->apiContentUrl = "https://content.dropboxapi.com/2/";
    }

    /**
     * Set Dropbox OAuth access token.
     *
     * @param string $token
     */
    protected function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * Copy content from one location to another.
     *
     * @param string $fromPath
     * @param string $toPath
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function copy($fromPath, $toPath)
    {
        $this->setupRequest([
            'from_path' => $this->normalizePath($fromPath),
            'to_path' => $this->normalizePath($toPath),
        ]);

        $this->apiEndpoint = 'files/copy';

        return $this->doDropboxApiRequest();
    }

    /**
     * Set Dropbox API request data.
     *
     * @param array $request
     */
    protected function setupRequest($request)
    {
        $this->request = new Collection($request);
    }

    /**
     * Perform Dropbox API request.
     *
     * @param bool $upload
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Exception
     */
    protected function doDropboxApiRequest($upload = false)
    {
        $headers['Dropbox-API-Arg'] = json_encode(
            $this->request->toArray()
        );

        if ($body !== '') {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        $postUrl = ($upload === true) ? "{$this->apiContentUrl}.{$this->apiEndpoint}" :
            "{$this->apiUrl}.{$this->apiEndpoint}";

        try {
            $response = $this->client->post($postUrl, [
                'headers' => $headers,
                'body' => '',
            ]);
        } catch (HttpClientException $exception) {
            throw $this->determineException($exception);
        }
        return $response;
    }

    /**
     * Normalize path.
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        return (trim($path, '/') === '') ? '' : '/'.$path;
    }

    /**
     * Catch Dropbox API request exception.
     *
     * @param HttpClientException $exception
     *
     * @return \Exception
     */
    protected function determineException(HttpClientException $exception)
    {
        if (in_array($exception->getResponse()->getStatusCode(), [400, 409])) {
            return new BadRequest($exception->getResponse());
        }
        return $exception;
    }
}