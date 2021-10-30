<?php

namespace Homeleon\Contracts\Database;

interface Database
{
    public function query();
    public function affectedRows();
    public function insertId();
    public function numRows($result);
    public function getOne();
    public function getRow();
    public function getCol();
    public function getAll();
    public function getInd();
    public function getIndCol();
    public function lastQuery();
    public function escapeString($value);
    public function getStats();
    public function table(string $tableName, $model = null);
}
