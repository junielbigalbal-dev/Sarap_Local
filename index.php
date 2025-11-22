<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
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
    <!-- ===================== HERO SECTION ===================== -->
    <section class="hero relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-32" id="top">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-orange-100 rounded-full filter blur-3xl opacity-50"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-72 h-72 bg-yellow-100 rounded-full filter blur-3xl opacity-50"></div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Text -->
                <div class="hero-text text-center lg:text-left">
                    <span class="inline-block py-2 px-4 rounded-full bg-orange-50 text-orange-600 text-sm font-semibold mb-6 shadow-sm border border-orange-100 animate-pop-in">
                        <i class="fas fa-star mr-2 text-yellow-400"></i> #1 Local Food Marketplace
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                        Discover <span class="text-gradient">Local Food</span> <br> Excellence
                    </h1>
                    <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                        Connect with passionate local vendors and experience authentic,
                        fresh cuisine delivered straight from your community to your table.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="login.php" class="btn-primary btn-pill text-lg group">
                            <i class="fas fa-bowl-rice group-hover:rotate-12 transition-transform"></i>
                            Order Now
                        </a>
                        <a href="signup.php" class="btn-outline btn-pill text-lg">
                            <i class="fas fa-store"></i>
                            Become a Vendor
                        </a>
                    </div>
                    <div class="mt-10 flex items-center justify-center lg:justify-start gap-4 text-sm text-gray-500">
                        <div class="flex -space-x-3">
                            <div class="w-10 h-10 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs font-bold text-gray-500">A</div>
                            <div class="w-10 h-10 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center text-xs font-bold text-gray-600">B</div>
                            <div class="w-10 h-10 rounded-full bg-gray-400 border-2 border-white flex items-center justify-center text-xs font-bold text-gray-700">C</div>
                        </div>
                        <p class="font-medium">Trusted by <span class="text-orange-600 font-bold">500+</span> foodies</p>
                    </div>
                </div>

                <!-- Hero Visual -->
                <div class="hero-visual relative hidden lg:block">
                    <div class="relative z-10 flex justify-center">
                        <div class="relative w-80 h-80 bg-white rounded-full shadow-2xl flex items-center justify-center p-8 animate-float">
                            <img src="images/S.png" alt="Sarap Local" class="w-full h-full object-contain">
                            
                            <!-- Floating Badges -->
                            <div class="absolute -top-4 -right-4 bg-white p-4 rounded-2xl shadow-lg animate-bounce-slow">
                                <span class="text-3xl">🥗</span>
                            </div>
                            <div class="absolute -bottom-4 -left-4 bg-white p-4 rounded-2xl shadow-lg animate-bounce-slow" style="animation-delay: 1.5s;">
                                <span class="text-3xl">🍜</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== FEATURES SECTION ===================== -->
    <section class="features py-20 bg-white" id="for-customers">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">Made for hungry customers</h2>
                <p class="text-lg text-gray-600">Browse real local vendors, discover hidden gems, and enjoy food that actually feels homemade.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <article class="bg-orange-50 rounded-3xl p-8 card-hover border border-orange-100">
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 text-2xl mb-6">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Curated local dishes</h3>
                    <p class="text-gray-600 leading-relaxed">Explore menus from home cooks and neighborhood restaurants, with photos, descriptions, and clear pricing.</p>
                </article>

                <article class="bg-orange-50 rounded-3xl p-8 card-hover border border-orange-100">
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 text-2xl mb-6">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Smart search & filters</h3>
                    <p class="text-gray-600 leading-relaxed">Filter by cuisine, budget, distance, and more. Find exactly what you are craving in just a few taps.</p>
                </article>

                <article class="bg-orange-50 rounded-3xl p-8 card-hover border border-orange-100">
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 text-2xl mb-6">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Favorites & reviews</h3>
                    <p class="text-gray-600 leading-relaxed">Save your go-to dishes, rate your orders, and support your favorite vendors with honest feedback.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== HOW IT WORKS ===================== -->
    <section class="how-it-works py-20 bg-cream" id="how-it-works">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">How Sarap Local works</h2>
                <p class="text-lg text-gray-600">Whether you are a customer or a vendor, getting started only takes a few minutes.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                <!-- Connecting Line (Desktop) -->
                <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-orange-200 -z-10 transform -translate-y-1/2"></div>

                <article class="bg-white rounded-3xl p-8 shadow-card relative text-center">
                    <div class="w-12 h-12 bg-orange-600 text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-6 border-4 border-white shadow-lg">1</div>
                    <h3 class="text-xl font-bold mb-3">Create account</h3>
                    <p class="text-gray-600">Sign up as a customer to order food, or as a vendor to start selling your specialties.</p>
                </article>

                <article class="bg-white rounded-3xl p-8 shadow-card relative text-center">
                    <div class="w-12 h-12 bg-orange-600 text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-6 border-4 border-white shadow-lg">2</div>
                    <h3 class="text-xl font-bold mb-3">Discover & Order</h3>
                    <p class="text-gray-600">Customers browse the marketplace while vendors upload menus, photos, and pricing.</p>
                </article>

                <article class="bg-white rounded-3xl p-8 shadow-card relative text-center">
                    <div class="w-12 h-12 bg-orange-600 text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-6 border-4 border-white shadow-lg">3</div>
                    <h3 class="text-xl font-bold mb-3">Enjoy & Track</h3>
                    <p class="text-gray-600">Place an order, track its status in real time, and enjoy fresh food from nearby kitchens.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== TESTIMONIALS ===================== -->
    <section class="testimonials py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">Loved by the community</h2>
                <p class="text-lg text-gray-600">Sarap Local connects real people with real food from everyday lunches to weekend gatherings.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <article class="bg-white rounded-3xl p-8 shadow-card card-hover border border-gray-100">
                    <div class="text-orange-400 text-4xl mb-6">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="text-gray-600 mb-8 italic">“I discovered so many home cooks in my neighborhood. The food feels personal, not generic fast food.”</p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div>
                            <div class="font-bold text-gray-900">Mika</div>
                            <div class="text-sm text-orange-600">Customer</div>
                        </div>
                    </div>
                </article>

                <article class="bg-white rounded-3xl p-8 shadow-card card-hover border border-gray-100">
                    <div class="text-orange-400 text-4xl mb-6">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="text-gray-600 mb-8 italic">“Sarap Local helped me turn my weekend food sideline into a steady income stream.”</p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div>
                            <div class="font-bold text-gray-900">JR</div>
                            <div class="text-sm text-orange-600">Vendor</div>
                        </div>
                    </div>
                </article>

                <article class="bg-white rounded-3xl p-8 shadow-card card-hover border border-gray-100">
                    <div class="text-orange-400 text-4xl mb-6">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="text-gray-600 mb-8 italic">“A simple, focused marketplace that highlights local talent. The experience feels warm and authentic.”</p>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div>
                            <div class="font-bold text-gray-900">Ana</div>
                            <div class="text-sm text-orange-600">Community Lead</div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== VENDOR CTA SECTION ===================== -->
    <section class="contact py-20 bg-orange-900 text-white relative overflow-hidden" id="for-vendors">
        <!-- Background Pattern -->
        <div class="absolute top-0 right-0 w-full h-full opacity-10">
            <div class="absolute right-0 top-0 w-96 h-96 bg-white rounded-full filter blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute left-0 bottom-0 w-96 h-96 bg-orange-500 rounded-full filter blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="contact-info">
                    <span class="inline-block py-1 px-3 rounded-full bg-orange-800 text-orange-200 text-sm font-semibold mb-6">
                        For Business
                    </span>
                    <h2 class="text-3xl lg:text-5xl font-bold mb-6 leading-tight">Grow your food business with us</h2>
                    <p class="text-orange-100 text-lg mb-10 leading-relaxed">Vendors get professional tools for menu management, order tracking, analytics, and direct customer feedback—all in one place.</p>

                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="w-12 h-12 bg-orange-800 rounded-2xl flex items-center justify-center text-orange-300 text-xl flex-shrink-0">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Vendor-friendly dashboard</h3>
                                <p class="text-orange-200">Manage your products, track orders, and see your best-selling dishes at a glance.</p>
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <div class="w-12 h-12 bg-orange-800 rounded-2xl flex items-center justify-center text-orange-300 text-xl flex-shrink-0">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Insights & analytics</h3>
                                <p class="text-orange-200">Understand what customers love, when they order, and how your sales grow over time.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white text-gray-900 rounded-3xl p-8 lg:p-10 shadow-2xl">
                    <h3 class="text-2xl font-bold mb-2">Become a Sarap Local vendor</h3>
                    <p class="text-gray-600 mb-8">Ready to start selling? Create a vendor account in minutes and set up your first menu.</p>
                    
                    <a href="signup.php" class="btn-primary btn-pill w-full justify-center text-lg mb-4">
                        <i class="fas fa-rocket"></i>
                        Start Selling Today
                    </a>
                    <p class="text-center text-sm text-gray-500">
                        Already have an account? <a href="login.php" class="text-orange-600 font-semibold hover:underline">Log in</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== FINAL CTA ===================== -->
    <section class="cta py-20 bg-cream text-center">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl lg:text-5xl font-bold mb-6 text-gray-900">Bring Sarap Local to your community</h2>
            <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">Whether you cook, sell, or simply love to eat, Sarap Local is your home for local flavors.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="login.php" class="btn-primary btn-pill text-lg">
                    <i class="fas fa-burger"></i>
                    Start as Customer
                </a>
                <a href="signup.php" class="btn-outline btn-pill text-lg">
                    <i class="fas fa-kitchen-set"></i>
                    Start as Vendor
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== FOOTER ===================== -->
    <footer class="bg-gray-900 text-white pt-20 pb-10">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                            <img src="images/S.png" alt="Sarap Local" class="w-6 h-6">
                        </div>
                        <span class="text-xl font-bold font-heading">Sarap Local</span>
                    </div>
                    <p class="text-gray-400 mb-6 leading-relaxed">A modern marketplace for local food. Discover nearby vendors, support small businesses, and enjoy meals made with care.</p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-orange-600 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-orange-600 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-orange-600 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Product</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#how-it-works" class="hover:text-orange-500 transition-colors">How it works</a></li>
                        <li><a href="#for-customers" class="hover:text-orange-500 transition-colors">For customers</a></li>
                        <li><a href="#for-vendors" class="hover:text-orange-500 transition-colors">For vendors</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">For Vendors</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="signup.php" class="hover:text-orange-500 transition-colors">Vendor login</a></li>
                        <li><a href="signup.php" class="hover:text-orange-500 transition-colors">Vendor sign up</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Support</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#contact" class="hover:text-orange-500 transition-colors">Contact us</a></li>
                        <li><a href="#" class="hover:text-orange-500 transition-colors">Help center</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-gray-500 text-sm">
                <p>&copy; <?php echo date('Y'); ?> Sarap Local. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
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

    // Scroll Animation Observer
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target); // Only animate once
                }
            });
        }, observerOptions);

        // Target elements to animate
        const animatedElements = document.querySelectorAll('.feature-card, .step-card, .testimonial-card, .contact-item');
        animatedElements.forEach((el, index) => {
            el.classList.add('animate-pop-in');
            el.style.animationDelay = `${index * 0.1}s`; // Stagger effect
            observer.observe(el);
        });
    });
    </script>
</body>
</html>
