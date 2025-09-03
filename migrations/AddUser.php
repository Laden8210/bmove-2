<?php
require_once __DIR__ . '/../TableBuilder.php';

class AddUser
{
    public function up($conn)
    {
        $table = new TableBuilder();


        $sql = $table->update('users', function ($t) {
            $t->addColumn('is_deleted', 'boolean', 0);
        });
        $conn->query($sql);
    }

    public function down($conn)
    {
        $table = new TableBuilder();

        // Example: Drop a table
        $conn->query("DROP TABLE IF EXISTS `users`");

        // Example: Rollback update
        // $sql = $table->update('users', function($t) {
        //     $t->dropColumn('phone');
        // });
        // $conn->query($sql);
    }
}
