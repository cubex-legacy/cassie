<?php
/**
 * @author  brooke.bryan
 */

namespace CubexCassie\Cli;

use Cubex\Helpers\Numbers;
use Cubex\Text\TextTable;

class CompactionStats extends BaseCliTool
{
  public function execute()
  {
    $stats = $this->_getMx4jClient()->loadMBean(
      "org.apache.cassandra.db:type=CompactionManager"
    );

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

    echo $compactionTable;

    echo "\nActive Compactions: " . $activeCompactions;
    echo "\nPending Compactions: " . ($stats->pendingTasks - $activeCompactions);
    echo "\n";
  }
}
