<?php
session_start();
include '../config/db.php';
$page_title = "Upcoming Hackathons | HackHub";
include '../includes/header.php';

// Fetch All Upcoming Events
$sql = "SELECT * FROM hackathons WHERE event_start >= NOW() ORDER BY event_start ASC";
$result = $conn->query($sql);
?>

<div class="container my-5 fade-in">

    <div class="text-center mb-5">
        <h1 class="text-gradient text-title">Explore Hackathons</h1>
        <p class="text-muted text-subtitle">Find and join the best tech events happening around you.</p>
    </div>

    <div class="grid-3">
        <?php if ($result->rowCount() > 0): ?>
        <?php while ($row = $result->fetch()): ?>
        <div class="glass-card">
            <h5 class="mb-2">
                <?php echo htmlspecialchars($row['title']); ?>
            </h5>
            <p class="text-muted mb-3 text-sm">
                📅
                <?php echo date('D, d M', strtotime($row['event_start'])); ?> |
                📍
                <?php echo htmlspecialchars($row['venue']); ?>
            </p>

            <div class="mb-3">
                <?php
        $tags = explode(',', $row['event_tags']);
        foreach ($tags as $tag) {
            if (trim($tag) != '')
                echo "<span class='badge bg-secondary tag-badge'>$tag</span>";
        }
?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="event_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="width: 100%;">
                Details & Register
            </a>
            <?php
        else: ?>
            <a href="login.php" class="btn btn-primary" style="width: 100%;">
                Login to View Details
            </a>
            <?php
        endif; ?>
        </div>
        <?php
    endwhile; ?>
        <?php
else: ?>
        <div class="glass-panel p-4 text-center" style="grid-column: 1 / -1;">
            <h3>No upcoming events found. 😢</h3>
            <p>Check back later or host your own!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="event_create.php" class="btn btn-success mt-3">+ Host an Event</a>
            <?php
    endif; ?>
        </div>
        <?php
endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>