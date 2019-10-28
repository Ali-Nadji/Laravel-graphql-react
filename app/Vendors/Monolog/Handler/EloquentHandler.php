<?php

namespace App\Vendors\Monolog\Handler;

use Models\Db\Error\Logs;
use Monolog\Handler\AbstractProcessingHandler;

class EloquentHandler extends AbstractProcessingHandler
{

    /**
     * Write Logs
     * @param array $record
     * @throws \Exception
     */
    protected function write(array $record)
    {

        // Traitement des ID binaire
        foreach ($record['context'] as $k => &$v) {
            if (preg_match('#[0-9a-zA-Z]{32}#', bin2hex($v))) {
                $v = uuid($v)->string;
            };
        }

        $logs = new Logs();
        $logs->uuid = generateNewUUID();
        $logs->env = $record['channel'];
        $logs->message = $record['message'];
        $logs->level = $record['level_name'];
        $logs->context = $record['context'];
        $logs->extra = $record['extra'];
        $logs->save();
    }
}