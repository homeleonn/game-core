<?php

use Core\DB\DB;
use Core\Auth\Auth;
use Core\Session\Storage;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function testAttempt()
    {
        $data = [
            'email' => 'test@mail.com',
            'password' => '123321',
        ];

        $db = $this->getMockBuilder(DB::class)
                   ->setMethods(['getRow'])
                   ->getMock();

        $db->expects($this->once())
           ->method('getRow')
           ->with($this->equalTo('SELECT id, password FROM users WHERE email = ?s', $data['email']));


        $storage = $this->getMockBuilder(Storage::class)
                   ->setMethods(['set'])
                   ->getMock();

        $storage->expects($this->once())
           ->method('set')
           ->with($this->equalTo(null));

        $auth = $this->getMockBuilder(Auth::class)
                         ->setConstructorArgs([$db, $storage])
                         ->getMock();
        $auth->method('passwordVerify')
             ->willReturn(true);
        // var_dump(get_class_methods($auth));exit;

        $this->assertTrue($auth->attempt($data));
    }
}
