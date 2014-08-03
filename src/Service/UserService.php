<?php

namespace Fusani\Auth\Service;

class UserService
{
    protected $adapter;

    public function __construct(\Fusani\Auth\Adapter\Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * This function authenticates a user. It needs the email and password
     *
     * @param $email : the email address to authenticate with
     * @param $password : the password to authenticate with
     * @return response from request
     */
    public function authenticate($email, $password)
    {
        // send the request to the api
        return $this->adapter->post('/v1/user/authenticate/'.$email, array('password' => $password));
    }

    /**
     * This function changes a user's password. If no password is sent, a random one is
     * sent.
     *
     * @param $email : the email address
     * @param $password : the new password
     * @return response from the request
     */
    public function changePassword($email, $password = '')
    {
        if (empty($password)) {
            $password = $this->generatePassword();
        }

        $response = $this->adapter->post('/v1/user/password/'.$email, array('password' => $password));
        $response['user']['password'] = $password;
        return $response;
    }

    /**
     * This function creates a random password for the user.
     *
     * @param $length : an optional length for the password
     * @return a random password for the user
     */
    protected function generatePassword($length = 20)
    {
        $letters = 'qwrtypsdfghjklzxcvbnm0123456789';

        // generate a password
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $letters[rand(0, strlen($letters) - 1)];
        }

        return $password;
    }

    /**
     * This function registers a new user. It needs the email, firstname,
     * lastname, and optionally a password.
     *
     * @param $email : the email address to register
     * @param $firstname : the first name of the user
     * @param $lastname : the last name of the user
     * @param $password : optional password
     * @return response from request
     */
    public function register($email, $firstname, $lastname, $password = '')
    {
        // if no password, make one up
        if (empty($password)) {
            $password = $this->generatePassword();
        }

        // build up the params
        $params = array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'password' => $password,
        );

        // send the request to the api
        $response = $this->adapter->post('/v1/user', $params);
        $response['password'] = $password;
        return $response;
    }

    /**
     * This function updates the user data.
     *
     * @param $uuid : the uuid of the user
     * @param $email : the email address to register
     * @param $firstname : the first name of the user
     * @param $lastname : the last name of the user
     * @return response from request
     */
    public function updateUser($uuid, $email, $firstname, $lastname)
    {
        $params = array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
        );

        return $this->adapter->put('/v1/user/'.$uuid, $params);
    }
}
