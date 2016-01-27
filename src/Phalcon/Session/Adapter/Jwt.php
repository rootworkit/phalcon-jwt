<?php

namespace Rootwork\Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;
use Phalcon\Session\AdapterInterface;
use Phalcon\Session\Exception;
use Firebase\JWT\JWT as JwtUtil;

/**
 * Jwt Session adapter
 *
 * @copyright   Copyright (c) 2015-2016 Rootwork InfoTech LLC (www.rootwork.it)
 * @license     BSD-3-clause
 * @author      Mike Soule <mike@rootwork.it>
 * @package     Rootwork\Phalcon\Session\Adapter
 *
 * @property    string $iss
 * @property    string $sub
 * @property    string $aud
 * @property    string $exp
 * @property    string $nbf
 * @property    string $iat
 * @property    string $jti
 * @property    string $typ
 */
class Jwt extends Adapter implements AdapterInterface
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'algorithm' => 'HS256',
        'lifetime'  => 900,
        'tokenName' => 'X-Token',
    ];

    /**
     * JWT payload
     *
     * @var array
     */
    protected $payload = [
        'iss'       => null,        // Issuer: server name
        'sub'       => null,        // Subject: Usually a user ID
        'aud'       => null,        // Audience: Who the claim is meant for (rarely used)
        'exp'       => null,        // Expire: time token expires
        'nbf'       => null,        // Not before: time token becomes valid
        'iat'       => null,        // Issued at: time when the token was generated
        'jti'       => null,        // Json Token Id: an unique identifier for the token
        'typ'       => null,        // Type: Mirrors the typ header (rarely used)
    ];

    /**
     * @var bool
     */
    protected $authenticated = false;

    /**
     * Class constructor.
     *
     * @param  array     $options
     * @throws Exception
     */
    public function __construct($options = null)
    {
        if (!isset($options['key'])) {
            throw new Exception('An encryption key is required');
        }

        parent::__construct($options);
    }

    /**
     * Start the session
     *
     * @return bool
     */
    public function start()
    {
        if (!headers_sent() && $this->status() !== self::SESSION_ACTIVE) {
            $this->decode();
            $this->_started = true;

            return true;
        }

        return false;
    }

    /**
     * Set a session value
     *
     * @param string $index
     * @param mixed  $value
     */
    public function set($index, $value)
    {
        $this->payload[$index] = $value;
    }

    /**
     * Get a session value
     *
     * @param   string  $index
     * @param   mixed   $default
     * @param   bool    $remove
     *
     * @return mixed|null
     */
    public function get($index, $default = null, $remove = false)
    {
        if (array_key_exists($index, $this->payload)) {
            $value = $this->payload[$index];

            if ($remove) {
                unset($this->payload[$index]);
            }

            return $value;
        }

        return $default;
    }

    /**
     * @param string $index
     * @return bool
     */
    public function has($index)
    {
        return isset($this->payload[$index]);
    }

    /**
     * Remove a value from the token
     *
     * @param string $index
     */
    public function remove($index)
    {
        unset($this->payload[$index]);
    }

    /**
     * Set the token ID
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->payload['jti'] = $id;
    }

    /**
     * Get the token ID
     *
     * @return mixed
     */
    public function getId()
    {
        if (empty($this->payload['jti'])) {
            $this->regenerateId();
        }

        return $this->payload['jti'];
    }

    /**
     * Get the payload
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Write the token out to the client
     *
     * @return bool
     */
    public function write()
    {
        $now = time();

        $this->jti  = $this->getId();
        $this->iat  = $now;
        $this->nbf  = $now;
        $this->exp  = $now + $this->_options['lifetime'];
        $this->iss  = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;

        $payload = array_filter($this->payload);

        $encoded = JwtUtil::encode(
            $payload,
            $this->_options['key'],
            $this->_options['algorithm']
        );

        return setcookie($this->getName(), $encoded, $this->exp, '/', null, null, true);
    }

    /**
     * Destroy the session
     *
     * @param bool $ignored
     * @return bool
     */
    public function destroy($ignored = false)
    {
        $this->payload = array_fill_keys(array_keys($this->payload), null);
        $this->authenticated = false;

        return setcookie($this->getName(), null, 1, '/');
    }

    /**
     * Check if the session is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * Check the session status
     *
     * @return int
     */
    public function status()
    {
        if ($this->_started) {
            return self::SESSION_ACTIVE;
        }

        return self::SESSION_NONE;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $options = array_merge($this->defaultOptions, $options);

        parent::setOptions($options);
    }

    /**
     * Decode the request token
     *
     * @return bool
     */
    protected function decode()
    {
        try {
            $payload = JwtUtil::decode(
                $this->getJwt(),
                $this->_options['key'],
                [$this->_options['algorithm']]
            );

            foreach ($payload as $key => $val) {
                $this->set($key, $val);
            }

            $this->authenticated = true;

            return true;
        } catch (\Exception $e) {
            // Handle exception
        }

        return false;
    }

    /**
     * Get the JWT for this request
     *
     * @return string|null
     */
    protected function getJwt()
    {
        $name = $this->getName();

        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }

        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        return null;
    }

    /**
     * Set the name to use for the session token (used as cookie or header name)
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->_options['tokenName'] = $name;
    }

    /**
     * Get the token name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_options['tokenName'];
    }

    /**
     * Regenerate the token ID
     *
     * @param bool|true $ignored
     * @return $this
     */
    public function regenerateId($ignored = true)
    {
        $this->set('jti', base64_encode(mcrypt_create_iv(32)));
        return $this;
    }

    /**
     * Override the parent destructor
     */
    public function __destruct()
    {
        $this->_started = false;
    }
}
