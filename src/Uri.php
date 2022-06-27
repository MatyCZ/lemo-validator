<?php

namespace LemoBase\Validator;

use Laminas\Validator\AbstractValidator;

class Uri extends AbstractValidator
{
    public const INVALID          = 'invalid';
    public const INVALID_FRAGMENT = 'invalidFragment';
    public const INVALID_HOST     = 'invalidHost';
    public const INVALID_PORT     = 'invalidPort';
    public const INVALID_PATH     = 'invalidPath';
    public const INVALID_QUERY    = 'invalidQuery';
    public const INVALID_SCHEME   = 'invalidScheme';
    public const INVALID_USERINFO = 'invalidUserInfo';

    protected array $messageTemplates = [
        self::INVALID          => "The value is not a valid uri. Use the basic format scheme://host/",
        self::INVALID_FRAGMENT => "'%fragment%' is not a valid fragment",
        self::INVALID_HOST     => "'%host%' is not a valid host",
        self::INVALID_PORT     => "'%port%' is not a valid port",
        self::INVALID_PATH     => "'%path%' is not a valid path",
        self::INVALID_QUERY    => "'%query%' is not a valid query",
        self::INVALID_SCHEME   => "'%scheme%' is not a valid scheme",
        self::INVALID_USERINFO => "'%userinfo%' is not a valid useinfo",
    ];

    protected array $messageVariables = [
        'fragment' => 'fragment',
        'host'     => 'host',
        'port'     => 'port',
        'path'     => 'path',
        'query'    => 'query',
        'scheme'   => 'scheme',
        'userinfo' => 'userinfo',
    ];

    protected ?string $fragment = null;
    protected ?string $host = null;
    protected ?int $port = null;
    protected ?string $path = null;
    protected ?string $query = null;
    protected ?string $scheme = null;
    protected ?string $userinfo = null;

    /**
     * Returns true if $value is valid URI
     *
     * @param  string $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);

        $uri = new \Laminas\Uri\Uri($value);

        // Basic validation
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->scheme = $uri->getScheme();
        $this->userinfo = $uri->getUserInfo();
        $this->host = $uri->getHost();
        $this->port = $uri->getPort();
        $this->path = $uri->getPath();
        $this->query = $uri->getQuery();
        $this->fragment= $uri->getFragment();

        // Validate scheme
        if (null !== $uri->getScheme() && false === $uri->validateScheme($uri->getScheme())) {
            $this->error(self::INVALID_SCHEME);
            return false;
        }

        // Validate userinfo
        if (null !== $uri->getUserInfo() && false === $uri->validateUserInfo($uri->getUserInfo())) {
            $this->error(self::INVALID_USERINFO);
            return false;
        }

        // Validate host
        if (null !== $uri->getHost() && false === $uri->validateHost($uri->getHost())) {
            $this->error(self::INVALID_HOST);
            return false;
        }

        // Validate port
        if (null !== $uri->getPort() && false === $uri->validatePort($uri->getPort())) {
            $this->error(self::INVALID_PORT);
            return false;
        }

        // Validate path
        if (null !== $uri->getPath() && false === $uri->validatePath($uri->getPath())) {
            $this->error(self::INVALID_PATH);
            return false;
        }

        // Validate query
        if (null !== $uri->getQuery() && false === $uri->validateQueryFragment($uri->getQuery())) {
            $this->error(self::INVALID_QUERY);
            return false;
        }

        // Validate fragment
        if (null !== $uri->getFragment() && false === $uri->validateQueryFragment($uri->getFragment())) {
            $this->error(self::INVALID_FRAGMENT);
            return false;
        }

        return true;
    }
}
