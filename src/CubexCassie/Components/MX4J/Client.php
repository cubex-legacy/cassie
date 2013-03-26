<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Components\MX4J;

use Cubex\Helpers\Curl;

class Client
{
  protected $_server;
  protected $_port;

  public function __construct($server = 'localhost', $port = 8081)
  {
    $this->_server = $server;
    $this->_port   = $port;
  }

  public function loadMBean($objectName, array $parameters = null)
  {
    $path = 'mbean?template=identity&objectname=' . $objectName;
    if($parameters !== null)
    {
      $path .= ':' . http_build_query($parameters, null, ',');
    }
    $rawResponse = $this->_directCall($path);
    return new MBeanResponse($rawResponse);
  }

  public function loadAttribute(
    $objectName, $format, $attribute, array $parameters = null
  )
  {
    $path = 'getattribute?template=identity';
    $path .= '&attribute=' . $attribute;
    $path .= '&format=' . $format;
    $path .= '&objectname=' . $objectName;
    if($parameters !== null)
    {
      $path .= ':' . http_build_query($parameters, null, ',');
    }
    $rawResponse = $this->_directCall($path);

    return new MBeanResponse($rawResponse);
  }


  protected function _directCall($path)
  {
    $uri = sprintf("http://%s:%d/", $this->_server, $this->_port) . $path;
    return Curl::request($uri, 5);
  }
}
