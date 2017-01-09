<?php

namespace Fusani\Fusani\Service;

use Fusani\Fusani\Adapter;

class UserService
{
    protected $rpcAdapter;

    public function __construct(Adapter\Adapter $rpcAdapter)
    {
        $this->rpcAdapter = $rpcAdapter;
    }

    public function acceptInvitation($groupIdentity, $userIdentity)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'acceptInvitation',
            compact('groupIdentity', 'userIdentity')
        );
    }

    public function addUser($firstname, $lastname, $email)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'addUser',
            compact('firstname', 'lastname', 'email')
        );
    }

    public function changePassword($email, $password)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'changePassword',
            compact('email', 'password')
        );
    }

    public function createGroup($name, $email = '')
    {
        return $this->rpcAdapter->call(
            'UserService',
            'createGroup',
            compact('name', 'email')
        );
    }

    public function inviteToGroup($email, $groupIdentity, $userIdentity)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'inviteToGroup',
            compact('email', 'groupIdentity', 'userIdentity')
        );
    }

    public function joinGroup($groupIdentity, $userIdentity)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'joinGroup',
            compact('groupIdentity', 'userIdentity')
        );
    }

    public function login($email, $password)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'login',
            compact('email', 'password')
        );
    }

    public function rejectInvitation($groupIdentity, $userIdentity)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'rejectInvitation',
            compact('groupIdentity', 'userIdentity')
        );
    }

    public function resetPassword($email)
    {
        return $this->rpcAdapter->call(
            'UserService',
            'resetPassword',
            compact('email')
        );
    }
}
