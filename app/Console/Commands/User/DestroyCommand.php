<?php

namespace App\Console\Commands\User;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\Table;

class DestroyCommand extends Command
{
    protected $signature = 'user:destroy {id}
        {--destroy : Destroy the user}';

    protected $description = 'Deletes a user with all its data';

    private Table $table;

    public function handle()
    {
        $user = User::find($this->argument('id'));

        if (!$user) {
            $this->error('User ' . $this->argument('id') . 'not found');
            return self::FAILURE;
        }

        $this->line('Deleting User: ' . $user->name);
        $this->initOutputTable();

        $this->destroyUserRelations($user);
        $this->destroyUser($user);

        return self::SUCCESS;
    }

    private function initOutputTable()
    {
        $this->table = new Table($this->output->getOutput()->section());
        $this->table->setHeaders(['table', 'count']);
        $this->table->render();
    }

    /**
     * Deletes all data related to the user by getting all tables with a user_id column
     */
    private function destroyUserRelations(User $user)
    {
        $this->disableForeignKeyChecks();
        $db_tables = $this->getDbTablesWithUserIdColumn();

        try {
            foreach ($db_tables as $db_table) {
                $this->destroyUserRelation($user, $db_table->TABLE_NAME);
            }
        }
        catch (\Throwable $th) {
            report($th);
        }
        finally {
            $this->enableForeignKeyChecks();
        }
    }

    private function disableForeignKeyChecks()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    }

    private function getDbTablesWithUserIdColumn()
    {
        return DB::select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = "user_id" AND TABLE_SCHEMA = "' . config('database.connections.mysql.database') . '"');
    }

    /**
     * Deletes all data related to the user in a specific table
     */
    private function destroyUserRelation(User $user, string $db_table_name)
    {
        $query = DB::table($db_table_name)->where('user_id', $user->id);
        $count = $this->option('destroy') ? $query->delete() : $query->count();
        $this->table->appendRow([$db_table_name, $count]);
    }

    /**
     * Deletes the user
     */
    private function destroyUser(User $user)
    {
        if (!$this->option('destroy')) {
            return false;
        }

        return $user->delete();
    }

    private function enableForeignKeyChecks()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
