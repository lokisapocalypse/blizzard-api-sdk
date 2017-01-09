<?php

namespace Fusani\Fusani\Service;

use Fusani\Fusani\SimpleTestCase;
use Fusani\Fusani\Adapter;

/**
 * @covers Fusani\Fusani\Service\UserService
 */
class UserServiceTest extends SimpleTestCase
{
    public function testAcceptInvitation()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->acceptInvitation('Avengers', 'peter-parker'));
    }

    public function testAddUser()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->addUser('Peter', 'Parker', 'peter.parker@dailybugle.com'));
    }

    public function testChangePassword()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->changePassword('peter.parker@dailybugle.com', 'spider-man'));
    }

    public function testCreateGroup()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->createGroup('New Avengers', 'Avengers'));
    }

    public function testInviteToGroup()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->inviteToGroup('peter.parker@dailybugle.com', 'Avengers', 'Iron Man'));
    }

    public function testJoinGroup()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->joinGroup('New Avengers', 'Peter Parker'));
    }

    public function testLogin()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->login('peter.parker@dailybugle.com', 'spider-man'));
    }

    public function testRejectInvitation()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->rejectInvitation('Avengers', 'peter-parker'));
    }

    public function testResetPassword()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $service = new UserService($adapter);

        $this->assertTrue($service->resetPassword('peter.parker@dailybugle.com'));
    }
}
