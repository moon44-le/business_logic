<?php


namespace Classes;


class DB extends \PDO
{
    function __construct() {
        parent::__construct(
            'mysql:host=localhost;dbname=tischde;charset=utf8mb4',
            'testuser',
            'a72D#9vWS*2M@_m',
            array(
                parent::ATTR_ERRMODE => parent::ERRMODE_EXCEPTION,
                parent::ATTR_PERSISTENT => false
            )
        );
    }
}