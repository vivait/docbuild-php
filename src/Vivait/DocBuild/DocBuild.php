<?php

namespace Vivait\DocBuild;

use Vivait\DocBuild\Http\GuzzleAdapter;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuild
{
    const URL = "http://doc.build/api";
    /**
     * @var HttpAdapter
     */
    private $http;

    /**
     * @param $key
     * @param HttpAdapter $http
     */
    public function __construct($key, HttpAdapter $http = null)
    {
        if(!$http){
            $this->http = new GuzzleAdapter();
        } else {
            $this->http = $http;
        }

        $this->http->setUrl(self::URL);
        $this->http->setKey($key);
    }

    public function getDocuments()
    {
        return $this->http->get('documents');
    }

    public function getDocument($id)
    {
        return $this->http->get('documents/' . $id);
    }

    public function downloadDocument($id)
    {
        return $this->http->get('documents/' . $id . '/payload', [], [],  false);
    }

    public function getResponseHeaders()
    {
        return $this->http->getResponseHeaders();
    }
}
