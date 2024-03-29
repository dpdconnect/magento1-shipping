<?php

namespace DpdConnect\Sdk\Common;

/**
 * Class Authentication
 *
 * @package DpdConnect\Sdk\Common
 */
class Authentication
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $jwtToken;

    /**
     * @var callable
     */
    public $tokenUpdateCallback;

    /**
     * @param $username
     * @param $password
     *
     * @return Authentication
     */
    public static function fromPassword($username, $password)
    {
        $authentication = new static();
        $authentication->username = $username;
        $authentication->password = $password;

        return $authentication;
    }

    /**
     * @param $jwtToken
     *
     * @return Authentication
     */
    public static function fromJwtToken($jwtToken)
    {
        $authentication = new static();
        $authentication->jwtToken = $jwtToken;

        return $authentication;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getJwtToken()
    {
        return $this->jwtToken;
    }

    /**
     * @param $jwtToken
     *
     * @return $this
     */
    public function setJwtToken($jwtToken)
    {
        $this->jwtToken = $jwtToken;

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     */
    public function setTokenUpdateCallback(callable $callable)
    {
        $this->tokenUpdateCallback = $callable;

        return $this;
    }
}
