<?php


namespace Classes;


class Product
{
    function __construct($arg) {
        switch ($arg) {
            case is_array($arg):
                $this->id           = $arg['id'];
                $this->sku          = $arg['sku'];
                $this->active       = $arg['active'];
                $this->title        = $arg['title'];
                break;
            case is_object($arg):
                $this->id           = $arg->id;
                $this->sku          = $arg->sku;
                $this->active       = $arg->active;
                $this->title        = $arg->title;
                break;
            default: break;
        }
    }
    public $id;
    public $sku;
    public $active;
    public $title;
    public $description;


    /**
     * @return mixed
     */
    public function getSku()
    {
        return $this->sku;
    }
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }







}


