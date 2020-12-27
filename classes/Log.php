<?php


namespace Classes;


class Log
{
    function __construct($level, $type, $target, $value, $description, $timestamp) {
        $this->level        = $level;
        $this->type         = $type;
        $this->target       = $target;
        $this->value        = $value;
        $this->description  = $description;
        $this->timestamp    = $timestamp;
    }
    private $level; // notice, warning, error
    private $type;  // affiliate, product, system
    private $target;// id
    private $value;
    private $description;
    private $timestamp;

    public function getArray(){
        return [
            'level' => $this->level,
            'type'  => $this->type,
            'target'=> $this->target,
            'value' => $this->value,
            'description' => $this->description,
            'timestamp' => $this->timestamp
        ];
    }
}