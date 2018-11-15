<?php

namespace Vivait\DocBuild\Model;

class Options
{

    /**
     * @var bool
     */
    private $tokenRefresh;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var string
     */
    private $url;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->tokenRefresh = $options['token_refresh'];
        $this->cacheKey = $options['cache_key'];
        $this->url = $options['url'];
    }

    /**
     * @return bool
     */
    public function shouldTokenRefresh(): bool
    {
        return $this->tokenRefresh;
    }

    /**
     * @return string
     */
    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
