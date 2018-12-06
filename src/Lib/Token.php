<?php
namespace App\Lib;

class Token
{
    private $decoded;

    public function __construct()
    {
        $this->decoded = null;
    }

    public function populate($decoded)
    {
        $this->decoded = $decoded;
    }

    public function clear()
    {
        $this->decoded = null;
    }

    public function logged()
    {
        return !empty($this->decoded);
    }

    public function acl(array $auth)
    {
        if (empty($this->decoded)) return false;
        return !!count(array_intersect($auth, $this->decoded["acl"]));
    }

    public function data()
    {
        if (empty($this->decoded)) return null;
        return (array)$this->decoded["data"];
    }

    public function userId()
    {
        if (empty($this->decoded)) return null;
        return $this->decoded['sub'];
    }

    public function jti()
    {
        if (empty($this->decoded)) return null;
        return $this->decoded['jti'];
    }

    public function decoded()
    {
        return (array)$this->decoded;
    }
}
