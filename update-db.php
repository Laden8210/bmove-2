<?php

require_once 'config/config.php';

$databaseDir = __DIR__ . '/database';


// Check if migrations table exists and has the right structure
$result = $conn->query("SHOW TABLES LIKE 'migrations'");
if ($result && $result->num_rows > 0) {
    // Table exists, check structure
    $result = $conn->query("DESCRIBE migrations");
    $hasFilename = false;
    $hasMigration = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'filename') $hasFilename = true;
        if ($row['Field'] === 'migration') $hasMigration = true;
    }
    
    if (!$hasFilename && $hasMigration) {
        // Update existing table structure
        $conn->query("ALTER TABLE migrations CHANGE migration filename VARCHAR(255) NOT NULL");
        $conn->query("ALTER TABLE migrations CHANGE applied_at executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "ℹ️ Updated migrations table structure.<br>";
    }
} else {
    // Create new migrations table
    $migrationsTable = "CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if (!$conn->query($migrationsTable)) {
        die("❌ Failed to create migrations table: " . $conn->error);
    }
}

$executed = [];
$result = $conn->query("SELECT filename FROM migrations");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $executed[] = $row['filename'];
    }
    $result->free();
} else {
    // If migrations table doesn't exist or query fails, start with empty array
    echo "ℹ️ Migrations table not found or empty, will execute all migrations.<br>";
}


$sqlFiles = glob($databaseDir . '/*.sql');
if (empty($sqlFiles)) {
    die("⚠️ No .sql files found in {$databaseDir}");
}

foreach ($sqlFiles as $file) {
    $filename = basename($file);


    if (in_array($filename, $executed)) {
        echo "⏭️ Skipped: {$filename} (already executed)<br>";
        continue;
    }

    echo "📄 Executing: {$filename} ... ";

    $sql = file_get_contents($file);
    if ($sql === false) {
        echo "❌ Failed to read file.<br>";
        continue;
    }

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        // record migration
        $stmt = $conn->prepare("INSERT INTO migrations (filename) VALUES (?)");
        $stmt->bind_param("s", $filename);
        $stmt->execute();
        $stmt->close();

        echo "✅ Success<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

$conn->close();
