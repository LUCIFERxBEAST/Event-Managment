<?php
session_start();
include __DIR__ . '/../config/db.php';
$page_title = "HackHub | The Future of Hackathon Management";
include __DIR__ . '/../includes/header.php';

// Fetch Real Stats
// 1. Active Hackathons
$sql_hackathons = "SELECT count(*) as count FROM hackathons WHERE event_start >= NOW()";
$result_hackathons = $conn->query($sql_hackathons);
$active_hackathons = ($result_hackathons->rowCount() > 0) ? $result_hackathons->fetch()['count'] : 0;

// 2. Registered Devs
$sql_users = "SELECT count(*) as count FROM users";
$result_users = $conn->query($sql_users);
$registered_users = ($result_users->rowCount() > 0) ? $result_users->fetch()['count'] : 0;
?>

<div class="container fade-in">

    <!-- Hero Section -->
    <section class="text-center my-5 py-5">
        <h1 class="text-gradient text-hero">
            Organize Hackathons <br> Like a Pro.
        </h1>
        <p class="text-muted text-subhero">
            The all-in-one platform to create events, manage registrations, scan QR tickets, and issue certificates.
            Seamless, secure, and stunning.
        </p>

        <div class="flex-center gap-1-5">
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">
                🚀 Go to Dashboard
            </a>
            <a href="event_create.php" class="btn btn-secondary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">
                + Create Event
            </a>
            <?php
else: ?>
            <a href="register.php" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">
                Get Started Free
            </a>
            <a href="login.php" class="btn btn-secondary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">
                Login
            </a>
            <?php
endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container my-5">
        <div class="glass-panel p-4 flex-between text-center" style="flex-wrap: wrap; justify-content: space-around;">
            <div>
                <div class="stat-number">
                    <?php echo $active_hackathons; ?>+
                </div>
                <div class="text-muted fw-600">Active Hackathons</div>
            </div>
            <div>
                <div class="stat-number">
                    <?php echo $registered_users; ?>+
                </div>
                <div class="text-muted fw-600">Devs Registered</div>
            </div>
            <div>
                <div class="stat-number">99%</div>
                <div class="text-muted fw-600">Satisfaction Rate</div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="mb-5">
        <h3 class="text-center mb-5 reveal-text">Everything You Need</h3>
        <div class="grid-3">

            <!-- Feature 1 -->
            <div class="glass-card tilt-card text-center">
                <div class="tilt-content">
                    <div class="icon-lg">📅</div>
                    <h4 class="text-primary">Event Management</h4>
                    <p class="text-muted">
                        Create and customize your hackathon pages in seconds. Manage dates, venues, and descriptions
                        with
                        ease.
                    </p>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="glass-card tilt-card text-center">
                <div class="tilt-content">
                    <div class="icon-lg">🎟️</div>
                    <h4 class="text-accent">QR Ticketing</h4>
                    <p class="text-muted">
                        Automated unique QR codes for every attendee. Check-in participants instantly with our built-in
                        scanner.
                    </p>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="glass-card tilt-card text-center">
                <div class="tilt-content">
                    <div class="icon-lg">🎓</div>
                    <h4 class="text-success">Auto Certificates</h4>
                    <p class="text-muted">
                        Automatically generate and distribute participation certificates to attendees who checked in.
                    </p>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="glass-card tilt-card text-center">
                <div class="tilt-content">
                    <div class="icon-lg">🔒</div>
                    <h4 class="text-danger">Secure Access</h4>
                    <p class="text-muted">
                        Role-based access for organizers and security guards. OTP-based login for maximum security.
                    </p>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="glass-card tilt-card text-center">
                <div class="tilt-content">
                    <div class="icon-lg">📊</div>
                    <h4 class="text-warning">Real-time Stats</h4>
                    <p class="text-muted">
                        Track registrations and check-ins in real-time. Know exactly who is at your event.
                    </p>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="glass-card tilt-card text-center">
                <div class="tilt-content">
                    <div class="icon-lg">📱</div>
                    <h4 class="text-secondary">Mobile First</h4>
                    <p class="text-muted">
                        Fully responsive design. Manage your event or access your ticket from any device, anywhere.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- CTA Section -->
    <section class="glass-panel text-center p-4 my-5" style="border-radius: var(--border-radius);">
        <h2 class="mb-3 reveal-text">Ready to host your next Hackathon?</h2>
        <p class="text-muted mb-4 reveal-text">Join thousands of organizers making their events successful.</p>
        <a href="register.php" class="btn btn-primary">Create Your Account Now</a>
    </section>

    <!-- Cyber Grid Background -->
    <div class="cyber-grid"></div>

    <!-- Floating Background Shapes -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <script>
        // 1. 3D Tilt Effect
        const cards = document.querySelectorAll('.tilt-card');
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = ((y - centerY) / centerY) * -10;
                const rotateY = ((x - centerX) / centerX) * 10;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = `perspective(1000px) rotateX(0) rotateY(0)`;
            });
        });

        // 3. Typing Effect
        const textElement = document.querySelector('.text-subhero');
        if (textElement) {
            const text = textElement.innerText;
            textElement.innerText = '';
            let i = 0;
            function typeWriter() {
                if (i < text.length) {
                    textElement.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 30);
                }
            }
            setTimeout(typeWriter, 500);
        }

        // 4. Advanced Scroll Effects
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;

            // A. Navbar Transformation
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }

            // B. Parallax Shapes
            const shapes = document.querySelectorAll('.floating-shape');
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.15;
                shape.style.transform = `translateY(${scrollY * speed}px)`;
            });

            // C. Scroll Reveal (Cards & Text)
            const reveals = document.querySelectorAll('.glass-card, .reveal-text');
            const windowHeight = window.innerHeight;
            const revealPoint = 150;

            reveals.forEach(reveal => {
                const revealTop = reveal.getBoundingClientRect().top;
                if (revealTop < windowHeight - revealPoint) {
                    reveal.classList.add('active');
                    reveal.style.opacity = "1";
                    reveal.style.transform = "translateY(0)";
                }
            });
        });

        // Trigger scroll once on load to show initial elements
        window.dispatchEvent(new Event('scroll'));
    </script>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>