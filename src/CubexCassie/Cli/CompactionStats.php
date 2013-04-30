<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Cli;

use Cubex\Cli\Shell;
use Cubex\Helpers\DateTimeHelper;
use Cubex\Helpers\Numbers;
use Cubex\Helpers\Strings;
use Cubex\Text\TextTable;
use CubexCassie\Components\MX4J\Client;

class CompactionStats extends BaseCliTool
{
  /**
   * Range of servers to loop through
   * @valuerequired
   */
  public $multihost;

  public $remaining;
  /**
   * @valuerequired
   */
  public $pause = 2;

  protected $_previousBytes;
  protected $_previousTime;
  protected $_bytesPerSecond;
  protected $_time;

  public function execute()
  {
    if($this->multihost)
    {
      if($this->remaining)
      {
        echo Shell::colourText(
          "Remaining calculations are not currently supported with multi hosts",
          Shell::COLOUR_FOREGROUND_RED
        );
        return;
      }
      $hosts = Strings::stringToRange($this->multihost);
    }
    else
    {
      $hosts = [$this->host];
    }

    foreach($hosts as $host)
    {
      if($this->remaining)
      {
        while(true)
        {
          Shell::redrawScreen($this->_getGetStats($host));
          flush();
          ob_flush();
          sleep($this->pause);
        }
      }
      else
      {
        echo $this->_getGetStats($host);
      }
      echo "\n";
    }
  }

  protected function _getGetStats($host = null)
  {
    $screenOut = '';
    try
    {
      if($host === null)
      {
        $client = $this->_getMx4jClient();
      }
      else
      {
        $client = new Client($host, $this->jmxPort, $this->timeout);
      }
      $stats = $client->loadMBean(
        "org.apache.cassandra.db:type=CompactionManager"
      );
    }
    catch(\Exception $e)
    {
      $screenOut .= Shell::colourText(
        "A connection to ",
        Shell::COLOUR_FOREGROUND_RED
      );
      $screenOut .= Shell::colourText(
        $host,
        Shell::COLOUR_FOREGROUND_PURPLE
      );
      $screenOut .= Shell::colourText(
        " could not be established.",
        Shell::COLOUR_FOREGROUND_RED
      );
      $screenOut .= "\n";
      return $screenOut;
    }

    $compactionTable = new TextTable();
    $compactionTable->setColumnHeaders(
      "Compaction Type",
      "Keyspace",
      "Column Family",
      "Completed",
      "Total",
      "Progress",
      "Remaining",
      "Speed"
    );
    $activeCompactions = 0;

    $this->_time = time();

    if(is_array($stats->compactions))
    {
      $totals     = ['completed' => 0, 'total' => 0];
      $maxSeconds = 0;

      foreach($stats->compactions as $compaction)
      {
        $activeCompactions++;

        $totalRemaining = $this->_calculateBps(
          $compaction->id,
          $compaction->completed,
          $compaction->total
        );

        $secs = $bps = 'Unknown';

        if($this->_previousTime > 0)
        {
          if($totalRemaining['seconds'] > 0)
          {
            if($totalRemaining['seconds'] > $maxSeconds)
            {
              $maxSeconds = $totalRemaining['seconds'];
            }
            $secs = DateTimeHelper::secondsToTime($totalRemaining['seconds']);
          }

          if($totalRemaining['bps'] > 0)
          {
            $bps = Numbers::bytesToHumanReadable($totalRemaining['bps']);
          }
        }

        $compactionTable->appendRow(
          $compaction->taskType,
          $compaction->keyspace,
          $compaction->columnfamily,
          Numbers::bytesToHumanReadable($compaction->completed),
          Numbers::bytesToHumanReadable($compaction->total),
          (round($compaction->completed / $compaction->total * 100, 2) . '%'),
          $secs,
          $bps
        );

        $totals['completed'] += $compaction->completed;
        $totals['total'] += $compaction->total;
        $this->_previousBytes[md5(
          $compaction->id . $compaction->total
        )] = $compaction->completed;
      }

      if($activeCompactions > 1)
      {
        $compactionTable->appendSpacer();

        $totalRemaining = $this->_calculateBps(
          'total',
          $totals['completed'],
          $totals['total']
        );
        $secs           = $bps = 'Unknown';

        if($this->_previousTime > 0)
        {
          if($totalRemaining['seconds'] > 0)
          {
            if($maxSeconds > $totalRemaining['seconds'])
            {
              $totalRemaining['seconds'] = $maxSeconds;
            }
            $secs = DateTimeHelper::secondsToTime($totalRemaining['seconds']);
          }

          if($totalRemaining['bps'] > 0)
          {
            $bps = Numbers::bytesToHumanReadable($totalRemaining['bps']);
          }
        }

        $compactionTable->appendRow(
          'Active Totals',
          '',
          '',
          Numbers::bytesToHumanReadable($totals['completed']),
          Numbers::bytesToHumanReadable($totals['total']),
          (round(
            $totals['completed'] / $totals['total'] * 100,
            2
          ) . '%'),
          $secs,
          $bps
        );
      }

      if($activeCompactions > 0)
      {
        $screenOut .= $compactionTable;
      }

      $this->_previousBytes['total'] = $totals['completed'];
    }

    $this->_previousTime = $this->_time;

    $pending = 0;
    if(isset($stats->pendingTasks))
    {
      $pending = ($stats->pendingTasks - $activeCompactions);
      if($pending < 0)
      {
        $pending = 0;
      }
    }

    $screenOut .= "\nActive Compactions: " . $activeCompactions;
    $screenOut .= "\nPending Compactions: " . $pending;
    $screenOut .= "\n";
    return $screenOut;
  }

  protected function _calculateBps($id, $completed, $total)
  {
    if($id !== 'total')
    {
      $id = md5($id . $total);
    }
    $processed = $completed - $this->_previousBytes[$id];
    $taken     = $this->_time - $this->_previousTime;
    $bps       = ($processed / $taken);
    if($bps > 10)
    {
      $this->_bytesPerSecond[$id][] = $bps;
    }

    $abps = array_sum($this->_bytesPerSecond[$id]) / count(
      $this->_bytesPerSecond[$id]
    );

    $remaining   = $total - $completed;
    $secondsLeft = $remaining / $abps;

    if($processed === 0 && $id !== 'total')
    {
      return ['seconds' => 0, 'bps' => 0];
    }
    else
    {
      return ['seconds' => $secondsLeft, 'bps' => $abps];
    }
  }
}
