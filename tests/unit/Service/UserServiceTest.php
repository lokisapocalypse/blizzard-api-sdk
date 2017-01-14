<?php

namespace Fusani\Fusani\Service;

use Fusani\Fusani\SimpleTestCase;
use Fusani\Fusani\Adapter;

/**
 * @covers Fusani\Fusani\Service\UserService
 */
class UserServiceTest extends SimpleTestCase
{
    protected $service;

    public function setup()
    {
        $adapter = new Adapter\AdapterStub('http://www.google.com');
        $adapter->addResponse(true);
        $this->service = new UserService($adapter, 'apikey');
    }

    public function testAcceptInvitation()
    {
        $this->assertTrue($this->service->acceptInvitation('Avengers', 'peter-parker'));
    }

    public function testAddUser()
    {
        $this->assertTrue($this->service->addUser('Peter', 'Parker', 'peter.parker@dailybugle.com'));
    }

    public function testChangePassword()
    {
        $this->assertTrue($this->service->changePassword('peter.parker@dailybugle.com', 'spider-man'));
    }

    public function testCreateGroup()
    {
        $this->assertTrue($this->service->createGroup('New Avengers', 'Avengers'));
    }

    public function testInviteToGroup()
    {
        $this->assertTrue($this->service->inviteToGroup('peter.parker@dailybugle.com', 'Avengers', 'Iron Man'));
    }

    public function testJoinGroup()
    {
        $this->assertTrue($this->service->joinGroup('New Avengers', 'Peter Parker'));
    }

    public function testLogin()
    {
        $this->assertTrue($this->service->login('peter.parker@dailybugle.com', 'spider-man'));
    }

    public function testRejectInvitation()
    {
        $this->assertTrue($this->service->rejectInvitation('Avengers', 'peter-parker'));
    }

    public function testResetPassword()
    {
        $this->assertTrue($this->service->resetPassword('peter.parker@dailybugle.com'));
    }
}
