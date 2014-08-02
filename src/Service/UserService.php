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
            $letters = 'qwrtypsdfghjklzxcvbnm0123456789';

            // generate a password
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $letters[rand(0, strlen($letters) - 1)];
            }
        }

        // build up the params
        $params = array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'password' => $password,
        );

        // send the request to the api
        $response = $this->adapter->post('/user', $params);
        $response['password'] = $password;
        return $response;
    }
}
