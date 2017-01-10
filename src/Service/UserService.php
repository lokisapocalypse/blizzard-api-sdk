<?php

namespace Fusani\Fusani\Service;

use Fusani\Fusani\Adapter;

class UserService
{
    protected $adapter;

    public function __construct(Adapter\Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function acceptInvitation($groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/invitation/accept',
            compact('groupIdentity', 'userIdentity')
        );
    }

    public function addUser($firstname, $lastname, $email)
    {
        return $this->adapter->post(
            'user/add',
            compact('firstname', 'lastname', 'email')
        );
    }

    public function changePassword($email, $password)
    {
        return $this->adapter->put(
            'user/password/change',
            compact('email', 'password')
        );
    }

    public function createGroup($name, $email = '')
    {
        return $this->adapter->post(
            'user/group/add',
            compact('name', 'email')
        );
    }

    public function inviteToGroup($email, $groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/invite',
            compact('email', 'groupIdentity', 'userIdentity')
        );
    }

    public function joinGroup($groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/join',
            compact('groupIdentity', 'userIdentity')
        );
    }

    public function login($email, $password)
    {
        return $this->adapter->post(
            'user/login',
            compact('email', 'password')
        );
    }

    public function rejectInvitation($groupIdentity, $userIdentity)
    {
        return $this->adapter->post(
            'user/group/invitation/reject',
            compact('groupIdentity', 'userIdentity')
        );
    }

    public function resetPassword($email)
    {
        return $this->adapter->put(
            'user/password/reset',
            compact('email')
        );
    }
}
