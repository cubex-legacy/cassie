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
    $data               = json_decode(
      json_encode(new \SimpleXMLElement($rawXml)),
      true
    );
    $this->_classname   = $data['@attributes']['classname'];
    $this->_description = $data['@attributes']['description'];
    $this->_objectName  = $data['@attributes']['objectname'];

    foreach($data['Attribute'] as $attr)
    {
      if($attr['classname'] === 'java.util.Map')
      {
        $value = $this->map($data['Attribute']['Map']);
      }
      else if($attr['@attributes']['value'])
      {
        $value = $attr['@attributes']['value'];
      }
      else
      {
        continue;
      }
      //TODO: Support  aggregation="array"
      //TODO: Support  aggregation="collection"
      //TODO: Support  aggregation="map"
      $this->_addAttribute(
        new Attribute(
          $attr['@attributes']['name'], false, null, $value
        )
      );
    }
  }

  public function map($map)
  {
    $result = [];
    foreach($map['Element'] as $item)
    {
      $itm                   = $item['@attributes'];
      $result[$itm['index']] = [$itm['key'] => $itm['element']];
    }
    return $result;
  }
}
