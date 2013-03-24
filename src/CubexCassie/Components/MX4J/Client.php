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
    $this->_server = '108.178.0.234'; //$server;
    $this->_port   = $port;
  }

  public function loadMBean($objectName, array $parameters = null)
  {
    $path = 'mbean?objectname=' . $objectName;
    if($parameters !== null)
    {
      $path .= ':' . http_build_query($parameters);
    }
    $path .= '&template=identity';
    $rawResponse = $this->_directCall($path);
    return new MBeanResponse($rawResponse);
  }

  protected function _directCall($path)
  {
    $uri = sprintf("http://%s:%d/", $this->_server, $this->_port) . $path;
    return Curl::request($uri, 5);
  }
}
