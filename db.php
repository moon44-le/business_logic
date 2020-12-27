<?php



$link = new PDO(
    'mysql:host=localhost;dbname=tischde;charset=utf8mb4',
    'testuser',
    'a72D#9vWS*2M@_m',
    array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false
    )
);

