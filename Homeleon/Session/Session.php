<?php

namespace Homeleon\Session;

use Homeleon\Contracts\Session\Session as SessionContract;
use SessionHandlerInterface;
use Homeleon\Support\Crypter;
use Homeleon\Support\Str;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\Request;
use RuntimeException;

class Session implements SessionContract
{
    const DEFAULT_LIFETIME = 120;

    private $config;
    private SessionHandlerInterface $handler;
    private string $sessionId;
    private string $sessionFilename;
    private string $sessionName = 'fw_session';
    private string $separator = '___';
    private int $lifetime;
    private array $data;
    private $started = false;

    public function __construct(SessionHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->config = Config::get('session');
        [$this->sessionFilename, $this->sessionId] = $this->getSessionId();
        $this->lifetime = $this->config['lifetime'] ?? self::DEFAULT_LIFETIME;
    }

    public function start()
    {
        $this->started = true;
        $this->sendSessionCookieIdToUser();
        $this->handler->open($this->config['path'], '');
        $this->handler->gc($this->lifetime);
        $this->read();

    }

    private function sendSessionCookieIdToUser()
    {
        setCookie($this->sessionName, $this->sessionId, [
            'expires' => time() + $this->lifetime * 60,
            'httponly' => true,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
    }

    private function read()
    {
        $data = $this->handler->read($this->sessionFilename);
        if ($data) {
            $this->data = unserialize($data);
        } else {
            $this->data = [];
        }
    }

    private function write()
    {
        if (isset($this->data)) {
            $this->handler->write($this->sessionFilename, serialize($this->data));
        }
    }

    public function get(?string $key = null)
    {
        return is_null($key) ? $this->data : ($this->data[$key] ?? null);
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function flash($key, $value)
    {
        $this->data['_flash'][$key] = $value;
    }

    public function del(string $key): void
    {
        unset($this->data[$key]);
    }

    public function all(): mixed
    {
         return $this->data;
    }

    private function generateCsrfToken()
    {
        return Str::random(32);
    }

    private function getSessionId()
    {
        $key = Config::get('app_key');
        $crypter = new Crypter($key);
        $isNeedToGenerateToken = true;

        if (isset($_COOKIE[$this->sessionName])) {
            try {
                $sessionValue = $crypter->decrypt($_COOKIE[$this->sessionName]);
            } catch (RuntimeException $e) {
                exit('wrong session');
            }

            if ($sessionValue !== false) {
                $isNeedToGenerateToken = false;
            }

            [$token, $sessionFilename] = explode($this->separator, $sessionValue);
            Config::set('_token', $token);
        }

        if ($isNeedToGenerateToken) {
            $sessionFilename = Str::random(32);
        }

        $csrfToken = $this->generateCsrfToken();
        Config::set('csrf_token', $csrfToken);

        return [$sessionFilename, $crypter->encrypt($csrfToken . $this->separator . $sessionFilename)];
    }

    public function __destruct()
    {
        if ($this->started) {
            $this->write();
        }
    }
}
