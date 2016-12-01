<?php

namespace syar\example\service;

use PDO;

/**
 * Class Test
 * @package syar\example\service
 */
class Db {
    protected static $pdo;

    static function getDb(){
        if(isset(self::$pdo)){
            return self::$pdo;
        }

        // local environment
        $host = '192.168.0.234';
        $user = $password = 'root';
        $db = 'test';
        $dsn = "mysql:dbname={$db};host={$host}";

        //self::$pdo = new PDO($dsn, $user, $password);
        self::$pdo = new PDO($dsn, $user, $password, [PDO::ATTR_PERSISTENT => true]);
        return self::$pdo;
    }

    public function getInfo($id){
        $sql = "select * from tmp_1 where id=" . intval($id);
        $set = self::getDb()->query($sql);
        $info = $set->fetch(PDO::FETCH_ASSOC);
        return $info;
    }

    public function getList($start = 0, $limit = 10){
        $start = intval($start);
        $limit = intval($limit);
        $sql = "select * from tmp_1 where 1 limit {$limit} offset {$start}";
        $set = self::getDb()->query($sql);
        $list = $set->fetchAll(PDO::FETCH_ASSOC);
        return $list;
    }
}