<?php

$conn = require __DIR__ . '/config/config.php';

// Ensure migrations table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Get applied migrations
$result = $conn->query("SELECT migration FROM migrations");
$applied = [];
while ($row = $result->fetch_assoc()) {
    $applied[] = $row['migration'];
}

$migrationsDir = __DIR__ . '/migrations/';
$files = scandir($migrationsDir);
$toApply = [];

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && !in_array($file, $applied)) {
        $toApply[] = $file;
    }
}

// Sort so they run in order
sort($toApply);

if (empty($toApply)) {
    echo "✅ No new migrations to apply.\n";
    exit;
}

foreach ($toApply as $migrationFile) {
    $path = $migrationsDir . $migrationFile;
    require_once $path;

    // Infer class name from filename (strip extension)
    $className = pathinfo($migrationFile, PATHINFO_FILENAME);

    if (!class_exists($className)) {
        echo "❌ Class $className not found in $migrationFile\n";
        exit(1);
    }

    $migration = new $className();

    echo "🚀 Running migration: $className\n";
    try {
        $migration->up($conn);

        // Mark as applied
        $stmt = $conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->bind_param("s", $migrationFile);
        $stmt->execute();

        echo "✅ Applied: $className\n";
    } catch (Exception $e) {
        echo "❌ Error in $className: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "🎉 All migrations applied successfully!\n";
