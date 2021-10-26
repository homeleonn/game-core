<?php

namespace Core\Session;

use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    protected $prefix;
    protected $savePath;

    public function __construct($prefix = 'sess_')
    {
        $this->prefix = $prefix;
    }

    public function path($id)
    {
        return $this->savePath . '/' . $this->prefix . str_replace(['/', '\\'], '', $id);
    }

    public function open($savePath, $sessionName)
    {
        $this->savePath = $savePath;

        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $path = $this->path($id);
        if (!file_exists($path)) return '';

        return (string)file_get_contents($path);
    }

    public function write($id, $data)
    {
        return file_put_contents($this->path($id), $data) === false ? false : true;
    }

    public function destroy($id)
    {
        $file = $this->path($id);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function gc($maxLifetime)
    {
        // prevent many attempts scanning session folder to 2% chance
        if (mt_rand(1, 100) > 2) return;

        foreach (glob($this->path('*')) as $file) {
            if (file_exists($file) && filemtime($file) + $maxlifetime < time()) {
                unlink($file);
            }
        }

        return true;
    }
}
