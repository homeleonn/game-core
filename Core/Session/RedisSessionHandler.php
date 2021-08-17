<?php

namespace Core\Session;

use Redis;

class RedisSessionHandler implements \SessionHandlerInterface
{
    public $ttl = 1800; // 30 minutes default
    protected $db;
    protected $prefix;

    public function __construct(Redis $db, $prefix = 'SID:') {
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function open($savePath, $sessionName) {
        // No action necessary because connection is injected
        // in constructor and arguments are not applicable.

        return true;
    }

    public function close() {
        $this->db = null;
        unset($this->db);
        return true;
    }

    public function read($id) {
        $id = $this->prefix . $id;
        $sessData = $this->db->get($id);
        $this->db->expire($id, $this->ttl);
        // if ($sessData) {
        //      $this->db->expire($id, $this->ttl);
        // }
        return $sessData ? $sessData : '';
    }

    public function write($id, $data) {
        // if (!$data) return true;
        $id = $this->prefix . $id;
        $this->db->set($id, $data, $this->ttl);
        return true;
    }

    public function destroy($id) {
        $this->db->del($this->prefix . $id);
        return false;
    }

    public function gc($maxLifetime) {
        // no action necessary because using EXPIRE
        return true;
    }
}