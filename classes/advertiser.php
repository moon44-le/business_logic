<?php


namespace Classes;


class Advertiser
{
    function __construct($arg) {
        $this->id               = $arg->id;
        $this->title            = $arg->title;
        $this->lastUpdate       = strtotime($arg->lastUpdate);
        $this->dataUrl    = $arg->dataUrl;
        $this->dataProtocol     = $arg->dataProtocol;
        $this->dataType         = $arg->dataType;
        $this->matchCode        = preg_replace("/[^a-z0-9\_\-\.]/i",'',$this->title);
        $this->delimiter        = $arg->delimiter;
        switch ($arg->isActive){
            case 1:     $this->isActive = true; break;
            default:    $this->isActive = false; break;
        }
    }

    private $id;
    private $title;
    private $matchCode;
    private $lastUpdate;
    private $isActive;
    private $dataUrl;
    private $dataProtocol;
    private $dataType;
    private $products = [];
    private $delimiter;
    private $handle;

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getDataProtocol()
    {
        return $this->dataProtocol;
    }
    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }
    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    /**
     * @return string
     */
    public function getMatchCode()
    {
        return $this->matchCode;
    }
    /**
     * @return string
     */
    public function getDataUrl()
    {
        return $this->dataUrl;
    }

    /**
     * @return array
     */
    public function getProducts(){
        return $this->products;
    }

    /**
     * @return array
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param $products
     */
    public function setProducts($products){
        switch ($this->matchCode){
            case 'kickbyte':
                foreach($products as $product) {
                    if (
                        isset($product['id']) &&
                        isset($product['titel'])
                    ) {
                            $mappedProduct = [];
                            $mappedProduct['id']            = "A".$this->id."--".$product['id'];
                            $mappedProduct['sku']           = $product['id'];
                            $mappedProduct['title']         = $product['titel'];
                            $mappedProduct['active']        = 1;
                            $this->products[$mappedProduct['id']] = new Product($mappedProduct);

                    }else{
                        $GLOBALS[logs][] = new Log('error', 'system', 'Feed', $this->matchCode, 'required attributes failed', $GLOBALS[timestamp] );
                        break;
                    }
                }
                break;
            case 'affiliateNameTest':
                foreach($products as $product) {
                    if (
                        isset($product['SKU']) &&
                        isset($product['Categories']) &&
                        isset($product['Name']) &&
                        isset($product['Published'])
                    ) {
                        if ( strpos($product['Categories'], 'Clothing') !== false) {
                            $mappedProduct = [];
                            $mappedProduct['id']            = "A".$this->id."--".$product['SKU'];
                            $mappedProduct['sku']           = $product['SKU'];
                            $mappedProduct['title']         = $product['Name'];
                            $mappedProduct['active']        = $product['Published'];
                            $this->products[$mappedProduct['id']] = new Product($mappedProduct);
                        }
                    }else{
                        $GLOBALS[logs][] = new Log('error', 'system', 'Feed', $this->matchCode, 'required attributes failed', $GLOBALS[timestamp] );
                        break;
                    }
                }
                break;
            default:
                $GLOBALS[logs][] = new Log('error', 'system', 'Feed', $this->matchCode, 'matchrules failed', $GLOBALS[timestamp] );
        }
    }
}
