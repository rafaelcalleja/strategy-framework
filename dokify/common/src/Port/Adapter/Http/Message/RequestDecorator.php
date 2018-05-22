<?php

namespace Dokify\Port\Adapter\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestDecorator implements RequestInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        return new self($this->request->withProtocolVersion($version));
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->request->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        return $this->request->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        return $this->request->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        return $this->request->getHeaderLine($name);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        return new self($this->request->withHeader($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        return new self($this->request->withAddedHeader($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        return new self($this->request->withoutHeader($name));
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->request->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        return new self($this->request->withBody($body));
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        return $this->request->getRequestTarget();
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        return new self($this->request->withRequestTarget($requestTarget));
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        return new self($this->request->withMethod($method));
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->request->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return new self($this->request->withUri($uri, $preserveHost));
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->request->getServerParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->request->getCookieParams();
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        return new self($this->request->withCookieParams($cookies));
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->request->getQueryParams();
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        return new self($this->request->withQueryParams($query));
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return  $this->request->getUploadedFiles();
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        return new self($this->request->withUploadedFiles($uploadedFiles));
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->request->getParsedBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        return new self($this->request->withParsedBody($data));
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->request->getAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value)
    {
        return new self($this->request->withAttribute($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name)
    {
        return new self($this->request->withoutAttribute($name));
    }
}
