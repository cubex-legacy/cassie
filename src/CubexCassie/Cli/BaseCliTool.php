<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Cli;

use Cubex\Cli\CliCommand;
use Cubex\KvStore\Cassandra\Connection;
use CubexCassie\Components\MX4J\Client;

abstract class BaseCliTool extends CliCommand
{
  /**
   * @required
   * @valuerequired
   */
  public $host = 'localhost';
  /**
   * @valuerequired
   */
  public $port = 9160;
  /**
   * @valuerequired
   */
  public $jmxPort = 8081;

  protected $_connection;
  protected $_mx4jClient;

  protected function _getConnection()
  {
    if($this->_connection === null)
    {
      $this->_connection = new Connection([$this->host], $this->port);
    }
    return $this->_connection;
  }

  protected function _getMx4jClient()
  {
    if($this->_mx4jClient === null)
    {
      $this->_mx4jClient = new Client($this->host, $this->jmxPort);
    }
    return $this->_mx4jClient;
  }
}
