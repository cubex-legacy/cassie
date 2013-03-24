<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Components\MX4J;

use Cubex\Data\Attribute;
use Cubex\Mapper\DataMapper;

class MBeanResponse extends DataMapper
{
  protected $_classname;
  protected $_description;
  protected $_objectName;

  public function __construct($rawXml)
  {
    //Convert to some pretty arrays
    $data = json_decode(
      json_encode(new \SimpleXMLElement($rawXml)),
      true
    );

    $this->_classname   = $data['@attributes']['classname'];
    $this->_description = $data['@attributes']['description'];
    $this->_objectName  = $data['@attributes']['objectname'];
    foreach($data['Attribute'] as $attr)
    {
      $this->_addAttribute(
        new Attribute(
          $attr['@attributes']['name'], false, null, $attr['@attributes']['value']
        )
      );
    }
  }
}
