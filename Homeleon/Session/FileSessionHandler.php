<?php

namespace Homeleon\Session;

use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    protected $prefix;
    protected $savePath;

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }

    public function path($filename)
    {
        return $this->savePath . '/' . $this->prefix . str_replace(['/', '\\'], '', $filename);
    }

    public function open($savePath, $sessionName): bool
    {
        $this->savePath = $savePath;

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($filename): string|false
    {
        $path = $this->path($filename);
        if (!file_exists($path)) return '';

        return (string)file_get_contents($path);
    }

    public function write($filename, $data): bool
    {
        return file_put_contents($this->path($filename), $data) === false ? false : true;
    }

    public function destroy($filename): bool
    {
        $file = $this->path($filename);

        if (file_exists($file)) {
            unlink($file);
            return true;
        }

        return false;
    }

    public function gc($maxLifetime): int|false
    {
        // prevent many attempts scanning session folder to 2% chance
        if (mt_rand(1, 100) > 2) return false;

        $countOfDeletedFiles = 0;
        foreach (glob($this->path('*')) as $file) {
            if (file_exists($file) && filemtime($file) + $maxLifetime < time()) {
                unlink($file);
                $countOfDeletedFiles++;
            }
        }

        return $countOfDeletedFiles;
    }
}
