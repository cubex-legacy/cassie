<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Components\Thrift;

use Cubex\KvStore\Cassandra\Connection;

class Ring
{
  protected $_conn;

  public function __construct(Connection $connection)
  {
    $this->_conn = $connection;
  }

  public function analyseKeyspace($keyspace)
  {
    $ring  = $this->_conn->ring($keyspace);
    $hosts = [];
    $epd   = 'endpoint_details';

    foreach($ring as $tokenRange)
    {
      $details = head($tokenRange->$epd);
      $dc      = $details->datacenter;
      $rack    = $details->rack;
      $host    = $details->host;

      if(!isset($hosts[$dc]))
      {
        $hosts[$dc] = [];
      }
      if(!isset($hosts[$dc][$rack]))
      {
        $hosts[$dc][$rack] = [];
      }
      if(!isset($hosts[$dc][$rack][$host]))
      {
        $hosts[$dc][$rack][$host] = ['tokens' => []];
      }

      $hosts[$dc][$rack][$host]['tokens'][] = [
        'start'         => $tokenRange->{'start_token'},
        'end'           => $tokenRange->{'end_token'},
        'endpoints'     => $tokenRange->{'endpoints'},
        'rpc_endpoints' => $tokenRange->{'rpc_endpoints'},
      ];
    }

    return $hosts;
  }
}
