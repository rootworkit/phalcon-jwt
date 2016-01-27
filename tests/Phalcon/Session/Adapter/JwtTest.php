<?php

namespace Rootwork\Test\Phalcon\Session\Adapter {

    use PHPUnit_Framework_TestCase as TestCase;
    use Rootwork\Phalcon\Session\Adapter\Jwt;
    use Firebase\JWT\JWT as JwtUtil;

    /**
     * Test case for Phalcon JWT adapter.
     *
     * @copyright   Copyright (c) 2015-2016 Rootwork InfoTech LLC (www.rootwork.it)
     * @license     BSD-3-clause
     * @author      Mike Soule <mike@rootwork.it>
     * @package     Rootwork\Test\Phalcon\Session\Adapter\Jwt
     */
    class JwtTest extends TestCase
    {

        /** @var string JWT hash key */
        public $jwtKey;

        /** @var array Cookies generated during tests */
        public static $cookies = [];

        /**
         * Set up the test.
         */
        public function setUp()
        {
            $this->jwtKey = bin2hex(openssl_random_pseudo_bytes(64));
        }

        /**
         * Test reading and writing values to the JWT key.
         */
        public function testReadAndWriteSession()
        {
            $session = new Jwt(['key' => $this->jwtKey]);
            $session->write();

            $cookie = current(self::$cookies);

            $this->assertEquals(1, count(self::$cookies));
            $this->assertEquals('X-Token', $cookie['name']);
            $this->assertNotFalse(base64_decode($session->jti));
            $this->assertEquals(44, strlen($session->jti));
        }

        /**
         * Test starting a session.
         *
         * @param   bool    $expected
         * @param   string  $arrName
         *
         * @dataProvider provideStart
         */
        public function testStart($expected, $arrName = null)
        {
            $tmp = new Jwt(['key' => $this->jwtKey]);
            $tmp->write();

            $cookie = current(self::$cookies);
            $jwt    = $cookie['value'];
            unset($tmp);

            switch ($arrName) {
                case '_COOKIE':
                    $_COOKIE['X-Token'] = $jwt;
                    break;
                case '_REQUEST':
                    $_REQUEST['X-Token'] = $jwt;
                    break;
                case '_SERVER':
                    $_SERVER['X-Token'] = $jwt;
                    break;
            }

            $session    = new Jwt(['key' => $this->jwtKey]);
            $actual     = $session->start();

            if (!$expected) {
                $actual = $session->start();
            }

            $this->assertEquals($expected, $actual);
        }

        /**
         * Provides data for testing start().
         *
         * @return array
         */
        public function provideStart()
        {
            return [
                [true, '_COOKIE'],
                [true, '_REQUEST'],
                [true, '_SERVER'],
                [false, null],
            ];
        }
    }
}

namespace Rootwork\Phalcon\Session\Adapter {

    /**
     * Override for built-in setcookie() function.
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     * @return bool
     */
    function setcookie(
        $name,
        $value = "",
        $expire = 0,
        $path = "",
        $domain = "",
        $secure = false,
        $httpOnly = false
    ) {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        \Rootwork\Test\Phalcon\Session\Adapter\JwtTest::$cookies[$name] = [
            'name'      => $name,
            'value'     => $value,
            'expire'    => $expire,
            'path'      => $path,
            'domain'    => $domain,
            'secure'    => $secure,
            'httpOnly'  => $httpOnly
        ];

        return true;
    }

    /**
     * Override for built-in headers_sent() function.
     *
     * @return bool
     */
    function headers_sent()
    {
        return false;
    }
}
