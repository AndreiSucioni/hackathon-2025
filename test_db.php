<?php

$dbPath = __DIR__ . '/database/db.sqlite';
echo "Testing path: $dbPath\n";

if (!file_exists($dbPath)) {
    echo "❌ Eroare: Fișierul nu există la această cale.\n";
    exit;
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Conectat! Tabele găsite: " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "❌ Eroare la conectare: " . $e->getMessage() . "\n";
}
