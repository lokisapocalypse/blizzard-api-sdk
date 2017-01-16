<?php

namespace Fusani\Fusani\Service;

use Fusani\Fusani\Adapter;

class UserService
{
    protected $adapter;
    protected $params;

    public function __construct(Adapter\Adapter $adapter, $apikey)
    {
        $this->adapter = $adapter;
        $this->params = ['apikey' => $apikey];
    }

    public function acceptInvitation($groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/invitation/accept',
            array_merge($this->params, compact('groupIdentity', 'userIdentity'))
        );
    }

    public function addUser($firstname, $lastname, $email)
    {
        return $this->adapter->post(
            'user/add',
            array_merge($this->params, compact('firstname', 'lastname', 'email'))
        );
    }

    public function changePassword($email, $password)
    {
        return $this->adapter->put(
            'user/password/change',
            array_merge($this->params, compact('email', 'password'))
        );
    }

    public function createGroup($name, $email)
    {
        return $this->adapter->post(
            'user/group/add',
            array_merge($this->params, compact('name', 'email'))
        );
    }

    public function inviteToGroup($email, $groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/invite',
            array_merge($this->params, compact('email', 'groupIdentity', 'userIdentity'))
        );
    }

    public function joinGroup($groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/join',
            array_merge($this->params, compact('groupIdentity', 'userIdentity'))
        );
    }

    public function login($email, $password)
    {
        return $this->adapter->post(
            'user/login',
            array_merge($this->params, compact('email', 'password'))
        );
    }

    public function rejectInvitation($groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/invitation/reject',
            array_merge($this->params, compact('groupIdentity', 'userIdentity'))
        );
    }

    public function resetPassword($email)
    {
        return $this->adapter->put(
            'user/password/reset',
            array_merge($this->params, compact('email'))
        );
    }
}
