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
  public function execute()
  {
    try
    {
      $stats = $this->_getMx4jClient()->loadMBean(
        "org.apache.cassandra.db:type=CompactionManager"
      );
    }
    catch(\Exception $e)
    {
      echo Shell::colourText(
        "A connection to ",
        Shell::COLOUR_FOREGROUND_RED
      );
      echo Shell::colourText(
        $this->host,
        Shell::COLOUR_FOREGROUND_PURPLE
      );
      echo Shell::colourText(
        " could not be established.",
        Shell::COLOUR_FOREGROUND_RED
      );
      echo "\n";
      return;
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
    if(!isset($stats->pendingTasks))
    {
      $stats->pendingTasks = 0;
    }

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

      if($activeCompactions > 0)
      {
        echo $compactionTable;
      }
    }

    echo "\nActive Compactions: " . $activeCompactions;
    echo "\nPending Compactions: " . ($stats->pendingTasks - $activeCompactions);
    echo "\n";
  }
}
