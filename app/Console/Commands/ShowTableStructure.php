<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowTableStructure extends Command
{
    protected $signature = 'table:show {table}';
    protected $description = 'Show the structure of a table';

    public function handle()
    {
        $table = $this->argument('table');
        $columns = DB::select("SHOW COLUMNS FROM $table");
        
        $this->table(
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
            collect($columns)->map(function($column) {
                return [
                    $column->Field,
                    $column->Type,
                    $column->Null,
                    $column->Key,
                    $column->Default,
                    $column->Extra,
                ];
            })
        );
    }
}
