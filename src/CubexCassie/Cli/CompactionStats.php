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
    $activeCompactions  = 0;

    if(is_array($stats->compactions))
    {
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
      }
      echo $compactionTable;
    }

    echo "\nActive Compactions: " . $activeCompactions;
    echo "\nPending Compactions: " . ($stats->pendingTasks - $activeCompactions);
    echo "\n";
  }
}
