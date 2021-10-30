<?php

use Homeleon\DB\DB;
use Homeleon\Auth\Auth;
use Homeleon\Session\Session;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function testCheck()
    {
        $db = $this->getMockBuilder(DB::class)->getMock();

        $storage = $this->getMockBuilder(Session::class)
                        ->disableOriginalConstructor()
                        // ->onlyMethods(['get'])
                        ->getMock();
        $storage->expects($this->once())
                ->method('get')
                ->with($this->equalTo('id'))
                ->willReturn(true);

        $auth = new Auth($db, $storage);

        $this->assertTrue($auth->check());
    }

    public function testAttempt()
    {
        $loginData = [
            'email' => 'test@mail.com',
            'password' => '123321',
        ];

         $hashForDataPassword = '$2y$10$ww9RdZonqThiReJv6tH1m.oGZpUg.QW5vwk/CoJr5s9/MVOM4H7eC';
        // $hashForDataPassword = password_hash($loginData['password'],  PASSWORD_BCRYPT);

        $db = $this->getMockBuilder(DB::class)
                   ->onlyMethods(['getRow'])
                   ->getMock();

        $db->expects($this->once())
           ->method('getRow')
           ->willReturn((object)['id' => 1, 'password' => $hashForDataPassword]);

        $storage = $this->getMockBuilder(Session::class)
                        ->disableOriginalConstructor()
                        // ->onlyMethods(['set'])
                        ->getMock();

        $storage->expects($this->once())
                ->method('set');

        $auth = new Auth($db, $storage);

        $this->assertTrue($auth->attempt($loginData));
    }
}
