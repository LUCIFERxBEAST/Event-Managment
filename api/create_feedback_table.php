<?php
include '../config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    rating INT NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES hackathons(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'feedback' created successfully";
}
else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>