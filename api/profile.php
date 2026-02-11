<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security Check with Persistent Auth (Vercel-compatible)
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['auth_token'])) {
        try {
            $token_hash = hash('sha256', $_COOKIE['auth_token']);
            $stmt = $conn->prepare("SELECT id, name, skills FROM users WHERE auth_token = :token");
            $stmt->execute(['token' => $token_hash]);

            if ($user = $stmt->fetch()) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_skills'] = explode(',', $user['skills']);
            }
            else {
                setcookie('auth_token', '', time() - 3600, '/');
                header("Location: login.php?error=invalid_token");
                exit();
            }
        }
        catch (PDOException $e) {
            error_log("Auth token error: " . $e->getMessage());
            setcookie('auth_token', '', time() - 3600, '/');
            header("Location: login.php?error=db_error");
            exit();
        }
    }
    else {
        header("Location: login.php");
        exit();
    }
}


$user_id = $_SESSION['user_id'];
$message = "";

// 2. Handle Form Submission
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $skills = trim($_POST['skills']);

    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE users SET name = :name, skills = :skills WHERE id = :id");
        $stmt->execute(['name' => $name, 'skills' => $skills, 'id' => $user_id]);

        try {
            if ($stmt->execute(['name' => $name, 'skills' => $skills, 'id' => $user_id])) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_skills'] = $skills;
                $message = "<div class='alert alert-success'>✅ Profile updated successfully!</div>";
            }
        }
        catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>❌ Error updating profile: " . $e->getMessage() . "</div>";
        }
    }
    else {
        $message = "<div class='alert alert-danger'>❌ Name cannot be empty.</div>";
    }
}

// 3. Handle Feedback Submission
if (isset($_POST['submit_feedback'])) {
    $event_id = intval($_POST['event_id']);
    $rating = intval($_POST['rating']);
    $feedback_msg = trim($_POST['feedback_message']);

    if ($rating > 0 && !empty($feedback_msg)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, event_id, rating, message) VALUES (:uid, :eid, :rating, :msg)");
        $stmt->execute(['uid' => $user_id, 'eid' => $event_id, 'rating' => $rating, 'msg' => $feedback_msg]);

        try {
            if ($stmt->execute(['uid' => $user_id, 'eid' => $event_id, 'rating' => $rating, 'msg' => $feedback_msg])) {
                // Simulated Email to Admin
                $admin_email = "admin@hackhub.com";
                $subject = "New Event Feedback";
                $email_content = "User ID: $user_id\nEvent ID: $event_id\nRating: $rating\nMessage: $feedback_msg";
                // mail($admin_email, $subject, $email_content); // Commented out for local env

                $message = "<div class='alert alert-success'>✅ Thank you! Your feedback has been sent to our team.</div>";
            }
        }
        catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>❌ Error submitting feedback: " . $e->getMessage() . "</div>";
        }
    }
    else {
        $message = "<div class='alert alert-danger'>❌ Please provide a rating and message.</div>";
    }
}

// 4. Fetch User Data (Source of Truth)
$stmt = $conn->prepare("SELECT name, email, skills FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if ($user) {
// User found
}
else {
    // Fallback if user not found (shouldn't happen)
    session_destroy();
    header("Location: login.php");
    exit();
}

// Skills Logic for Display/Tags
$skills_array = !empty($user['skills']) ? explode(',', $user['skills']) : [];

$page_title = "My Profile | HackHub";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 fade-in">
    <div class="glass-card mx-auto" style="max-width: 800px;">

        <!-- Back Button -->
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary text-sm">
                ← Back to Dashboard
            </a>
        </div>

        <div class="text-center mb-5">
            <div class="icon-xl">👤</div>
            <h1 class="text-title text-gradient">My Profile</h1>
            <p class="text-muted text-subtitle">Manage your account details</p>
        </div>

        <?php echo $message; ?>

        <!-- Feedback Section (Only if pending) -->
        <?php
$today = date("Y-m-d H:i:s");
$sql_pending = "SELECT h.id, h.title FROM hackathons h 
                        WHERE h.created_by = '$user_id' 
                        AND h.event_end < '$today' 
                        AND h.id NOT IN (SELECT event_id FROM feedback WHERE user_id = '$user_id')";
$result_pending = $conn->query($sql_pending);

if ($result_pending && $result_pending->rowCount() > 0):
?>
        <div class="glass-panel p-4 mb-5"
            style="border: 1px solid var(--accent-color); background: rgba(108, 99, 255, 0.05);">
            <h4 class="text-accent mb-3">📢 We Value Your Feedback!</h4>
            <p class="text-muted mb-4">You recently organized an event that has ended. Please let us know how it went.
            </p>

            <?php while ($event = $result_pending->fetch()): ?>
            <div class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h5 class="mb-3">Event: <strong>
                        <?php echo htmlspecialchars($event['title']); ?>
                    </strong></h5>
                <form method="POST">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">

                    <div class="form-group mb-3">
                        <label class="form-label text-sm">Rate your experience (1-5)</label>
                        <select name="rating" class="form-control" required>
                            <option value="">Select Rating</option>
                            <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (Good)</option>
                            <option value="3">⭐⭐⭐ (Average)</option>
                            <option value="2">⭐⭐ (Poor)</option>
                            <option value="1">⭐ (Terrible)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-sm">Feedback Message</label>
                        <textarea name="feedback_message" class="form-control" rows="3"
                            placeholder="Tell us what features helped you and what we can improve..."
                            required></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="btn btn-primary btn-sm">Submit Feedback</button>
                </form>
            </div>
            <?php
    endwhile; ?>
        </div>
        <?php
endif; ?>

        <form method="POST">
            <div class="grid-3 mb-5" style="grid-template-columns: 1fr 1fr;">

                <!-- Personal Details (Editable) -->
                <div class="glass-panel p-4">
                    <h5 class="text-primary mb-3">Personal Details</h5>

                    <div class="form-group mb-3">
                        <label class="form-label text-sm">Full Name</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-sm">Email Address (Cannot change)</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>"
                            disabled style="opacity: 0.7; cursor: not-allowed;">
                    </div>
                </div>

                <!-- Skills (Editable) -->
                <div class="glass-panel p-4">
                    <h5 class="text-accent mb-3">My Skills & Interests</h5>

                    <div class="form-group mb-3">
                        <label class="form-label text-sm">Skills (comma separated)</label>
                        <input type="text" name="skills" class="form-control"
                            value="<?php echo htmlspecialchars($user['skills']); ?>"
                            placeholder="e.g., PHP, Design, AI">
                    </div>

                    <div class="mt-3">
                        <p class="text-sm text-muted mb-2">Current Tags:</p>
                        <?php if (!empty($skills_array)): ?>
                        <?php foreach ($skills_array as $skill): ?>
                        <?php if (trim($skill) != ''): ?>
                        <span class="badge bg-secondary tag-badge mb-1">
                            <?php echo htmlspecialchars(trim($skill)); ?>
                        </span>
                        <?php
        endif; ?>
                        <?php
    endforeach; ?>
                        <?php
else: ?>
                        <span class="text-xs text-muted">No skills listed yet.</span>
                        <?php
endif; ?>
                    </div>
                </div>

            </div>

            <!-- Save Button -->
            <div class="text-center">
                <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 0.8rem 2rem;">
                    💾 Save Changes
                </button>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>