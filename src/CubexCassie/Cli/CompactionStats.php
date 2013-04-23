<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Cli;

use Cubex\Cli\Shell;
use Cubex\Helpers\Numbers;
use Cubex\Text\TextTable;

class CompactionStats extends BaseCliTool
{
  public $remaining;
  /**
   * @valuerequired
   */
  public $pause = 2;

  protected $_previousBytes;
  protected $_previousTime;
  protected $_bytesPerSecond;

  public function execute()
  {
    if($this->remaining)
    {
      while(true)
      {
        Shell::redrawScreen($this->_getGetStats());
        flush();
        ob_flush();
        sleep($this->pause);
      }
    }
    else
    {
      echo $this->_getGetStats();
    }
  }

  protected function _getGetStats()
  {
    $screenOut = '';
    try
    {
      $stats = $this->_getMx4jClient()->loadMBean(
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
        $this->host,
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
      "total",
      "Progress"
    );
    $activeCompactions = 0;

    if(is_array($stats->compactions))
    {
      $totals = ['completed' => 0, 'total' => 0];

      foreach($stats->compactions as $compaction)
      {
        $activeCompactions++;
        $compactionTable->appendRow(
          $compaction->taskType,
          $compaction->keyspace,
          $compaction->columnfamily,
          Numbers::bytesToHumanReadable($compaction->completed),
          Numbers::bytesToHumanReadable($compaction->total),
          (round($compaction->completed / $compaction->total * 100, 2) . '%')
        );
        $totals['completed'] += $compaction->completed;
        $totals['total'] += $compaction->total;
      }

      if($activeCompactions > 1)
      {
        $compactionTable->appendSpacer();

        $compactionTable->appendRow(
          'Active Totals',
          '',
          '',
          Numbers::bytesToHumanReadable($totals['completed']),
          Numbers::bytesToHumanReadable($totals['total']),
          (round(
            $totals['completed'] / $totals['total'] * 100,
            2
          ) . '%')
        );
      }

      if($activeCompactions > 0)
      {
        $screenOut .= $compactionTable;
      }

      $time                    = time();
      $processed               = $totals['completed'] - $this->_previousBytes;
      $taken                   = $time - $this->_previousTime;
      $this->_bytesPerSecond[] = ($processed / $taken);

      $abps = array_sum($this->_bytesPerSecond) / count($this->_bytesPerSecond);

      $remaining   = $totals['total'] - $totals['completed'];
      $secondsLeft = $remaining / $abps;

      if($this->_previousTime > 0)
      {
        if($secondsLeft > 0)
        {
          $screenOut .= "\nEstimated Time Remaining: ";
          $screenOut .= Numbers::formatMicroTime($secondsLeft);
        }
        $screenOut .= "\nEstimated Data Speed: ";
        $screenOut .= Numbers::bytesToHumanReadable($abps) . '/s';
      }

      $this->_previousBytes = $totals['completed'];
      $this->_previousTime  = $time;
    }
    $pending = ($stats->pendingTasks - $activeCompactions);
    if($pending < 0)
    {
      $pending = 0;
    }

    $screenOut .= "\nActive Compactions: " . $activeCompactions;
    $screenOut .= "\nPending Compactions: " . $pending;
    $screenOut .= "\n";
    return $screenOut;
  }
}
