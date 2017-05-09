<?php

namespace Srmklive\Dropbox\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use GuzzleHttp\Psr7\StreamWrapper;
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
     * Check whether the current API request has any upload content.
     *
     * @var bool
     */
    protected $apiHasContent;

    protected $content;

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
     * Copy a file or folder to a different location in the user's Dropbox.
     *
     * If the source path is a folder all its contents will be copied.
     *
     * @param string $fromPath
     * @param string $toPath
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-copy
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
     * Create a folder at a given path.
     *
     * @param string $path
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-create_folder
     */
    public function createFolder($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/create_folder';

        $response = $this->doDropboxApiRequest();
        $response['.tag'] = 'folder';

        return $response;
    }

    /**
     * Delete the file or folder at a given path.
     *
     * If the path is a folder, all its contents will be deleted too.
     * A successful response indicates that the file or folder was deleted.
     *
     * @param string $path
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-delete
     */
    public function delete($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/delete';

        return $this->doDropboxApiRequest();
    }

    /**
     * Download a file from a user's Dropbox.
     *
     * @param string $path
     *
     * @return resource
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-download
     */
    public function download($path)
    {
        $this->setupRequest([
            'path' => $this->normalizePath($path),
        ]);

        $this->apiEndpoint = 'files/download';
        $this->apiHasContent = true;

        $response = $this->doDropboxApiRequest();
        $this->apiHasContent = false;

        return StreamWrapper::getResource($response->getBody());
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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Exception
     */
    protected function doDropboxApiRequest()
    {
        if ($this->apiHasContent) {
            $headers['Dropbox-API-Arg'] = json_encode(
                $this->request->toArray()
            );
            $headers['Content-Type'] = 'application/octet-stream';

            $post = [
                'headers' => $headers,
                'body' => $this->content,
            ];

            $postUrl = "{$this->apiContentUrl}.{$this->apiEndpoint}";
        } else {
            $post = [
                'json' => $this->request->toArray()
            ];

            $postUrl = "{$this->apiUrl}.{$this->apiEndpoint}";
        }

        try {
            $response = $this->client->post($postUrl, $post);
        } catch (HttpClientException $exception) {
            throw $this->determineException($exception);
        }

        return ($this->apiHasContent) ? $response  : \GuzzleHttp\json_decode($response->getBody(), true);
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