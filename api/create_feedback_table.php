<?php
include '../config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS feedback (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    rating INT NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES hackathons(id)
)";

try {
    $conn->exec($sql);
    echo "Table 'feedback' created successfully";
}
catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}

$conn = null;
?>