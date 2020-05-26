<?php

class ChilexpressOficialOrderMeta extends ObjectModel
{
  public $id_chilexpress_oficial_ordermeta;
  public $id_order;
  public $transportOrderNumber;
  public $reference;
  public $productDescription;
  public $serviceDescription;
  public $genericString1;
  public $genericString2;
  public $deliveryTypeCode;
  public $destinationCoverageAreaName;
  public $additionalProductDescription;
  public $barcode;
  public $classificationData;
  public $printedDate;
  public $labelVersion;
  public $distributionDescription;
  public $companyName;
  public $recipient;
  public $address;
  public $groupReference;
  public $createdDate;
  public $labelData;


  /**
  * @see ObjectModel::$definition
  */
  public static $definition = array(
    'table' => 'chilexpress_oficial_ordermeta',
    'primary' => 'id_chilexpress_oficial_ordermeta',
    'multilang' => false,
    'fields' => array(
      'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
      'transportOrderNumber' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'productDescription' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'serviceDescription' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'genericString1' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'genericString2' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'deliveryTypeCode' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'destinationCoverageAreaName' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'additionalProductDescription' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'barcode' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'classificationData' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'printedDate' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'labelVersion' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'distributionDescription' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'companyName' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'recipient' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'address' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 128),
      'groupReference' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 32),
      'createdDate' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64),
      'labelData' => array('type' => self::TYPE_STRING)
    )
  );
}
