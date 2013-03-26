<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Applications\Www\Controllers;

use Cubex\Core\Controllers\WebpageController;
use Cubex\Database\ConnectionMode;
use Cubex\Form\Form;
use Cubex\KvStore\Cassandra\Connection;
use Cubex\View\HtmlElement;
use Cubex\View\Templates\Errors\Error404;
use CubexCassie\Components\MX4J\Client;
use CubexCassie\Components\Thrift\Ring;

class DefaultController extends WebpageController
{

  public function preProcess()
  {
    $this->requireCss(
      'http://twitter.github.com/bootstrap/assets/css/bootstrap.css'
    );
    $this->requireCss('/base');

    $this->requireJs('http://code.jquery.com/jquery-latest.js');
    $this->requireJs(
      'http://twitter.github.com/bootstrap/assets/js/bootstrap.min.js'
    );
  }

  public function renderIndex()
  {
    $server = $this->config('cassie')->getStr('server');
    $connection = new Connection([$server]);

    $ring = new Ring($connection);
    $ring->analyseKeyspace("MyPCBackup");

    $mxj       = new Client($server);

    var_dump_json($mxj->loadAttribute(
        'org.apache.cassandra.net','map','SimpleStates',['type' => 'FailureDetector']
      ));


    $readStage = $mxj->loadMBean(
      'org.apache.cassandra.request',
      ['type' => 'ReadStage']
    );
    var_dump_json($readStage);
    $mutationStage = $mxj->loadMBean(
      'org.apache.cassandra.request',
      ['type' => 'MutationStage']
    );
    var_dump_json($mutationStage);
    $mutationStage = $mxj->loadMBean(
      'org.apache.cassandra.db',
      [
        'type' => 'ColumnFamilies',
        'keyspace' => 'MyPCBackup',
        'columnfamily' => 'Affiliate_Hop_Followed'
      ]
    );
    var_dump_json($mutationStage);
  }

  public function renderNotFound()
  {
    $this->webpage()->setStatusCode("404");
    return new Error404();
  }

  public function getRoutes()
  {
    return array(
      '/' => 'index'
    );
  }

  public function defaultAction()
  {
    return "notFound";
  }
}
