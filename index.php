<?php
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sarap Local - Your local food marketplace connecting food lovers with amazing local vendors. Fresh, authentic food delivered to your doorstep.">
    <meta name="keywords" content="local food, food delivery, fresh ingredients, community vendors, Sarap Local">
    <meta name="theme-color" content="#C46A2B">
    <title>Sarap Local - Your Local Food Marketplace</title>

    <link rel="icon" type="image/png" href="images/S.png">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&family=Pacifico&display=swap" rel="stylesheet">
</head>
<body>
    <!-- ===================== NAVIGATION ===================== -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <span class="nav-logo-text brand-script">Sarap Local</span>
            </a>

            <div class="nav-menu">
                <a href="#how-it-works" class="nav-link">How it works</a>
                <a href="#for-customers" class="nav-link">For customers</a>
                <a href="#for-vendors" class="nav-link">For vendors</a>
            </div>

            <div class="nav-auth">
                <a href="login.php" class="btn btn-outline">I'm a customer</a>
                <a href="signup.php" class="btn btn-primary">I'm a vendor</a>
            </div>

            <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div class="mobile-menu">
            <div class="mobile-menu-content">
                <a href="#how-it-works" class="mobile-nav-link">How it works</a>
                <a href="#for-customers" class="mobile-nav-link">For customers</a>
                <a href="#for-vendors" class="mobile-nav-link">For vendors</a>

                <div class="mobile-auth">
                    <a href="login.php" class="btn btn-outline">I'm a customer</a>
                    <a href="signup.php" class="btn btn-primary">I'm a vendor</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ===================== HERO SECTION ===================== -->
    <section class="hero" id="top">
        <div class="hero-bg-image"></div>
        <div class="hero-background">
            <div class="hero-pattern"></div>
        </div>
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <span class="hero-title-main">Discover Local</span>
                    <span class="hero-title-accent">Food Excellence</span>
                </h1>
                <p class="hero-subtitle">
                    Connect with passionate local vendors and experience authentic,
                    fresh cuisine delivered straight from your community to your table.
                </p>
                <div class="hero-actions">
                    <a href="login.php" class="btn btn-primary btn-hero">
                        <i class="fas fa-bowl-rice"></i>
                        <span>Order as customer</span>
                    </a>
                    <a href="signup.php" class="btn btn-outline btn-hero">
                        <i class="fas fa-store"></i>
                        <span>Sell as vendor</span>
                    </a>
                </div>
            </div>
            <div class="hero-visual">
                <img src="images/S.png" alt="Sarap Local Logo" class="hero-logo-image" />
            </div>
        </div>
    </section>

    <section class="features" id="for-customers">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Made for hungry customers</h2>
                <p class="section-subtitle">Browse real local vendors, discover hidden gems, and enjoy food that actually feels homemade.</p>
            </div>

            <div class="features-grid">
                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="feature-title">Curated local dishes</h3>
                    <p class="feature-description">Explore menus from home cooks and neighborhood restaurants, with photos, descriptions, and clear pricing.</p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="feature-title">Smart search & filters</h3>
                    <p class="feature-description">Filter by cuisine, budget, distance, and more. Find exactly what you are craving in just a few taps.</p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="feature-title">Favorites & reviews</h3>
                    <p class="feature-description">Save your go-to dishes, rate your orders, and support your favorite vendors with honest feedback.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">How Sarap Local works</h2>
                <p class="section-subtitle">Whether you are a customer or a vendor, getting started only takes a few minutes.</p>
            </div>

            <div class="steps-grid">
                <article class="step-card">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Create your account</h3>
                    <p class="step-description">Sign up as a customer to order food, or as a vendor to start selling your specialties.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Discover or publish dishes</h3>
                    <p class="step-description">Customers browse the marketplace while vendors upload menus, photos, and pricing.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Order, track, and enjoy</h3>
                    <p class="step-description">Place an order, track its status in real time, and enjoy fresh food from nearby kitchens.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Loved by the community</h2>
                <p class="section-subtitle">Sarap Local connects real people with real food from everyday lunches to weekend gatherings.</p>
            </div>

            <div class="testimonials-grid">
                <article class="testimonial-card">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">“I discovered so many home cooks in my neighborhood. The food feels personal, not generic fast food.”</p>
                    <div class="testimonial-author">
                        <div>
                            <div class="author-name">Mika, Customer</div>
                            <div class="author-role">Works-from-home foodie</div>
                        </div>
                    </div>
                </article>

                <article class="testimonial-card">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">“Sarap Local helped me turn my weekend food sideline into a steady income stream.”</p>
                    <div class="testimonial-author">
                        <div>
                            <div class="author-name">JR, Vendor</div>
                            <div class="author-role">Home-based grill master</div>
                        </div>
                    </div>
                </article>

                <article class="testimonial-card">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">“A simple, focused marketplace that highlights local talent. The experience feels warm and authentic.”</p>
                    <div class="testimonial-author">
                        <div>
                            <div class="author-name">Ana, Community Lead</div>
                            <div class="author-role">Local food organizer</div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="contact" id="for-vendors">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <div class="section-header" style="text-align:left; margin-bottom:2rem;">
                        <h2 class="section-title">Grow your food business</h2>
                        <p class="section-subtitle">Vendors get tools for menu management, orders, analytics, and direct customer feedback.</p>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div>
                            <h3>Vendor-friendly dashboard</h3>
                            <p>Manage your products, track orders, and see your best-selling dishes at a glance.</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3>Insights & analytics</h3>
                            <p>Understand what customers love, when they order, and how your sales grow over time.</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <h3 style="margin-bottom:1rem; font-size:1.25rem; font-weight:600;">Become a Sarap Local vendor</h3>
                    <p style="margin-bottom:1.5rem; color:#64748b;">Ready to start selling? Create a vendor account in minutes and set up your first menu.</p>
                    <a href="signup.php" class="btn btn-primary btn-large" style="width:100%; justify-content:center; display:flex;">
                        <i class="fas fa-door-open"></i>
                        <span>Login or sign up as vendor</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2 class="cta-title">Bring Sarap Local to your community</h2>
            <p class="cta-subtitle">Whether you cook, sell, or simply love to eat, Sarap Local is your home for local flavors.</p>
            <div class="cta-actions">
                <a href="login.php" class="btn btn-primary btn-large">
                    <i class="fas fa-burger"></i>
                    <span>Start as customer</span>
                </a>
                <a href="signup.php" class="btn btn-outline btn-large">
                    <i class="fas fa-kitchen-set"></i>
                    <span>Start as vendor</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== FOOTER ===================== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="images/S.png" alt="Sarap Local" class="footer-logo-img">
                        <span class="footer-logo-text">Sarap Local</span>
                    </div>
                    <p class="footer-description">A modern marketplace for local food. Discover nearby vendors, support small businesses, and enjoy meals made with care.</p>
                    <div class="footer-social">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

                <div class="footer-links">
                    <div>
                        <h4 class="footer-heading">Product</h4>
                        <ul class="footer-list">
                            <li><a href="#how-it-works" class="footer-link">How it works</a></li>
                            <li><a href="#for-customers" class="footer-link">For customers</a></li>
                            <li><a href="#for-vendors" class="footer-link">For vendors</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="footer-heading">For vendors</h4>
                        <ul class="footer-list">
                            <li><a href="signup.php" class="footer-link">Vendor login</a></li>
                            <li><a href="signup.php" class="footer-link">Vendor sign up</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="footer-heading">Support</h4>
                        <ul class="footer-list">
                            <li><a href="#contact" class="footer-link">Contact us</a></li>
                            <li><a href="#" class="footer-link">Help center</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p class="footer-copyright">&copy; <?php echo date('Y'); ?> Sarap Local. All rights reserved.</p>
                    <div class="footer-legal">
                        <a href="#" class="footer-legal-link">Privacy</a>
                        <a href="#" class="footer-legal-link">Terms</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
    // Optional: add a small scroll effect for navbar if styles support .scrolled
    (function(){
        var navbar = document.querySelector('.navbar');
        if (!navbar) return;

        function onScroll(){
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        window.addEventListener('scroll', onScroll);
        onScroll();

        var toggle = navbar.querySelector('.mobile-menu-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                navbar.classList.toggle('mobile-nav-open');
            });
        }

        document.addEventListener('click', function (e) {
            if (!navbar.contains(e.target)) {
                navbar.classList.remove('mobile-nav-open');
            }
        });
    })();

    // Register service worker for PWA support
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('service-worker.js').catch(function (err) {
                console.warn('Service worker registration failed:', err);
            });
        });
    }
    </script>
</body>
</html>
