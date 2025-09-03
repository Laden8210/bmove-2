<?php

if ($argc < 2) {
    echo "❌ Usage: php make:migrate MigrationName\n";
    exit(1);
}

$name = $argv[1];
$dir = __DIR__ . '/migrations';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$timestamp = date('YmdHis');
$className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
$filename = $dir . '/' . $className . '.php';

$template = <<<PHP
<?php
require_once __DIR__ . '/../TableBuilder.php';

class {$className} {
    public function up(\$conn) {
        \$table = new TableBuilder();

        // Example: Create a table
        // \$sql = \$table->create('users', function(\$t) {
        //     \$t->id();
        //     \$t->string('name', 100);
        //     \$t->string('email', 150);
        //     \$t->unique('email');
        //     \$t->timestamps();
        // });
        // \$conn->query(\$sql);

        // Example: Update a table
        // \$sql = \$table->update('users', function(\$t) {
        //     \$t->addColumn('phone', 'varchar', 20);
        // });
        // \$conn->query(\$sql);
    }

    public function down(\$conn) {
        \$table = new TableBuilder();

        // Example: Drop a table
        // \$conn->query("DROP TABLE IF EXISTS `users`");

        // Example: Rollback update
        // \$sql = \$table->update('users', function(\$t) {
        //     \$t->dropColumn('phone');
        // });
        // \$conn->query(\$sql);
    }
}
PHP;

file_put_contents($filename, $template);

echo "✅ Migration created: " . basename($filename) . "\n";
