<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPetCares - Pet Licensing and Control System in Cantilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/616/616408.png">
    <style>
        :root {
            --primary: #3a86ff;
            --secondary: #ff9e00;
            --accent: #ff006e;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            scroll-behavior: smooth;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .pet-pattern {
            background-color: #e0f2fe;
            opacity: 0.8;
            background-image: radial-gradient(#3a86ff 0.8px, #e0f2fe 0.8px);
            background-size: 16px 16px;
        }
        
        .hero-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1548681528-6a5c45b66b42?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, #f59e0b 100%);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #3a86ff 0%, #2563eb 100%);
            color: white;
            font-size: 28px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .nav-link {
            position: relative;
            padding-bottom: 5px;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .mobile-menu.active {
            transform: translateX(0);
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>
<body class="bg-gray-50">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 12H19a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h4.5m0-4v1.67a.33.33 0 00.5.28l1.79-1.28a.5.5 0 01.71 0l1.79 1.28a.33.33 0 00.5-.28V8m-6 4h6M12 12a4 4 0 00-4-4h0a4 4 0 00-4 4v0a2 2 0 002 2h4a2 2 0 002-2zM12 12a4 4 0 014-4h0a4 4 0 014 4v0a2 2 0 01-2 2h-4a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">PawPet<span class="text-blue-600">Cares</span></h1>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="nav-link text-gray-600 hover:text-blue-600 transition-colors font-medium">Features</a>
                <a href="#vision" class="nav-link text-gray-600 hover:text-blue-600 transition-colors font-medium">Our Vision</a>
                <a href="#how-it-works" class="nav-link text-gray-600 hover:text-blue-600 transition-colors font-medium">How It Works</a>
                <a href="#testimonials" class="nav-link text-gray-600 hover:text-blue-600 transition-colors font-medium">Testimonials</a>
                <a href="#contact" class="nav-link text-gray-600 hover:text-blue-600 transition-colors font-medium">Contact</a>
            </div>
            
            <div class="hidden md:flex items-center space-x-4">
                <a href="login.php" class="text-gray-600 font-semibold hover:text-blue-600 transition-colors">Log In</a>
                <a href="register.php" class="btn-primary text-white font-semibold px-5 py-2.5 rounded-lg shadow-md">Register Your Pet</a>
            </div>
            
            <button id="menu-toggle" class="md:hidden text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
        </nav>
        
        <div id="mobile-menu" class="mobile-menu fixed inset-0 bg-white z-50 px-6 py-4 md:hidden">
            <div class="flex justify-between items-center mb-10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 12H19a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h4.5m0-4v1.67a.33.33 0 00.5.28l1.79-1.28a.5.5 0 01.71 0l1.79 1.28a.33.33 0 00.5-.28V8m-6 4h6M12 12a4 4 0 00-4-4h0a4 4 0 00-4 4v0a2 2 0 002 2h4a2 2 0 002-2zM12 12a4 4 0 014-4h0a4 4 0 014 4v0a2 2 0 01-2 2h-4a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">PawPet<span class="text-blue-600">Cares</span></h1>
                </div>
                <button id="menu-close" class="text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex flex-col space-y-6">
                <a href="#features" class="text-lg font-medium text-gray-700 py-2 border-b border-gray-100">Features</a>
                <a href="#vision" class="text-lg font-medium text-gray-700 py-2 border-b border-gray-100">Our Vision</a>
                <a href="#how-it-works" class="text-lg font-medium text-gray-700 py-2 border-b border-gray-100">How It Works</a>
                <a href="#testimonials" class="text-lg font-medium text-gray-700 py-2 border-b border-gray-100">Testimonials</a>
                <a href="#contact" class="text-lg font-medium text-gray-700 py-2 border-b border-gray-100">Contact</a>
                
                <div class="pt-6 border-t border-gray-100 flex flex-col space-y-4">
                    <a href="login.php" class="w-full text-center py-3 px-5 rounded-lg text-gray-700 font-semibold bg-gray-100 hover:bg-gray-200 transition-colors">Log In</a>
                    <a href="register.php" class="w-full text-center btn-primary text-white font-semibold py-3 px-5 rounded-lg shadow-md">Register Your Pet</a>
                </div>
            </div>
        </div>
    </header>

    <section class="relative min-h-screen hero-bg text-white flex items-center">
        <div class="container mx-auto px-6 py-20 flex flex-col items-center text-center">
            <div class="max-w-3xl">
                <div class="inline-block bg-white/10 backdrop-blur-md rounded-full px-5 py-2 mb-6">
                    <span class="text-sm font-semibold">Cantilan's Official Pet Management System</span>
                </div>
                
                <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
                    Ensuring a Safer, Healthier Community for Pets in Cantilan
                </h1>
                
                <p class="text-xl md:text-2xl mb-10 text-gray-200 max-w-2xl mx-auto">
                    Join PawPetCares to easily manage your pet's licensing, vaccinations, and health records online. Responsible pet ownership starts here.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="register.php" class="btn-primary text-white font-bold px-8 py-4 rounded-lg text-lg shadow-lg">
                        Get Started Today
                    </a>
                    <button class="bg-white/10 backdrop-blur-md text-white font-bold px-8 py-4 rounded-lg text-lg border border-white/20 hover:bg-white/20 transition-all">
                        <i class="fas fa-play-circle mr-2"></i> Watch Video
                    </button>
                </div>
            </div>
            
            <div class="mt-16 floating">
                <img src="https://images.unsplash.com/photo-1583337130417-3346a1be7dee?q=80&w=1000&auto=format&fit=crop" alt="Happy dog" class="w-64 h-64 rounded-2xl shadow-2xl object-cover">
            </div>
        </div>
        
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
            <a href="#features" class="text-white text-4xl">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">1,200+</div>
                    <div class="text-gray-600">Pets Registered</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">98%</div>
                    <div class="text-gray-600">Vaccination Rate</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">15+</div>
                    <div class="text-gray-600">Veterinary Partners</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">24/7</div>
                    <div class="text-gray-600">Support Available</div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-20 pet-pattern">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-blue-600 font-semibold uppercase tracking-wider text-sm">Our Services</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Everything You Need for Your Pet</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    We provide a seamless digital experience for all essential pet management services in Cantilan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-center">Online Pet Registration</h3>
                    <p class="text-gray-600 text-center">Easily register your pets for licensing, deworming, anti-rabies, and vaccination services from home.</p>
                </div>

                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-center">QR-Enabled Dog Tags</h3>
                    <p class="text-gray-600 text-center">Each pet receives a unique QR code linked to their owner's details for quick identification if lost.</p>
                </div>

                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-center">Vaccination Schedules</h3>
                    <p class="text-gray-600 text-center">Stay updated with timely notifications and reminders about upcoming vaccination sessions in your area.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-center">Secure Online Payments</h3>
                    <p class="text-gray-600 text-center">We offer convenient and safe payment options for all services through our secure online portal.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="vision" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2">
                    <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?q=80&w=1924&auto=format&fit=crop" alt="Happy dog with owner" class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                </div>
                <div class="md:w-1/2">
                    <span class="text-blue-600 font-semibold uppercase tracking-wider text-sm">Our Vision & Commitment</span>
                    <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-6">A Thriving Community of Healthy Pets</h2>
                    <p class="text-gray-600 mb-6 text-lg">
                        We aim to create a community where every pet is licensed, vaccinated, and well-cared for, reducing the incidence of diseases and promoting responsible pet ownership.
                    </p>
                    <p class="text-gray-600 mb-8">
                        PawPetCares is committed to supporting local pet owners and veterinary services in Cantilan. By integrating modern technology with community-focused care, we strive to make pet management seamless and accessible to all.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button class="btn-primary text-white font-semibold px-6 py-3.5 rounded-lg">
                            Learn More About Us
                        </button>
                        <button class="border border-blue-600 text-blue-600 font-semibold px-6 py-3.5 rounded-lg hover:bg-blue-50 transition-all">
                            <i class="fas fa-download mr-2"></i> Download Brochure
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-blue-600 font-semibold uppercase tracking-wider text-sm">Simple Process</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">How PawPetCares Works</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Managing your pet's health and safety has never been easier with our simple three-step process.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="text-5xl font-bold text-blue-600 mb-4">01</div>
                    <h3 class="text-xl font-bold mb-3">Create an Account</h3>
                    <p class="text-gray-600 mb-4">Sign up and create a profile for you and your beloved pet in just a few minutes.</p>
                    <div class="flex items-center text-blue-600 font-medium">
                        <span>Get started</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
                
                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="text-5xl font-bold text-blue-600 mb-4">02</div>
                    <h3 class="text-xl font-bold mb-3">Register Services</h3>
                    <p class="text-gray-600 mb-4">Choose the services you need, like licensing or vaccinations, and complete the process online.</p>
                    <div class="flex items-center text-blue-600 font-medium">
                        <span>View services</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
                
                <div class="bg-white p-8 rounded-2xl card-hover shadow-md border border-gray-100">
                    <div class="text-5xl font-bold text-blue-600 mb-4">03</div>
                    <h3 class="text-xl font-bold mb-3">Receive Your QR Tag</h3>
                    <p class="text-gray-600 mb-4">Get your pet's QR-enabled tag and stay informed with automated health reminders.</p>
                    <div class="flex items-center text-blue-600 font-medium">
                        <span>See examples</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <a href="register.php" class="btn-secondary text-white font-semibold px-8 py-3.5 rounded-lg">
                    Start Registration Process
                </a>
            </div>
        </div>
    </section>

    <section id="testimonials" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-blue-600 font-semibold uppercase tracking-wider text-sm">Happy Pet Owners</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">What Pet Owners Say</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Hear from our community members who have used PawPetCares for their pet management needs.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="testimonial-card">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mr-4">MR</div>
                        <div>
                            <h4 class="font-bold">Maria Rodriguez</h4>
                            <p class="text-gray-500 text-sm">Dog Owner</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">"PawPetCares made registering my two dogs so easy! The QR tag gives me peace of mind knowing they can be identified if they ever get lost."</p>
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mr-4">JS</div>
                        <div>
                            <h4 class="font-bold">John Santos</h4>
                            <p class="text-gray-500 text-sm">Cat Owner</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">"The vaccination reminders are a lifesaver! I never miss my cat's important shots thanks to PawPetCares' notification system."</p>
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mr-4">AL</div>
                        <div>
                            <h4 class="font-bold">Anna Lim</h4>
                            <p class="text-gray-500 text-sm">Multiple Pet Owner</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">"Managing all my pets' records in one place has been incredible. The platform is user-friendly and the customer support is excellent!"</p>
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 gradient-bg text-white">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Register Your Pet?</h2>
            <p class="text-xl mb-10 max-w-2xl mx-auto">Join hundreds of responsible pet owners in Cantilan who are using PawPetCares to keep their pets safe and healthy.</p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="register.php" class="bg-white text-blue-600 font-bold px-8 py-4 rounded-lg text-lg shadow-lg hover:bg-gray-100 transition-all">
                    Register Now
                </a>
                <button class="bg-white/10 backdrop-blur-md text-white font-bold px-8 py-4 rounded-lg text-lg border border-white/20 hover:bg-white/20 transition-all">
                    Contact Support
                </button>
            </div>
        </div>
    </section>

    <footer id="contact" class="bg-gray-900 text-white pt-16 pb-8">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div class="lg:col-span-1">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 12H19a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h4.5m0-4v1.67a.33.33 0 00.5.28l1.79-1.28a.5.5 0 01.71 0l1.79 1.28a.33.33 0 00.5-.28V8m-6 4h6M12 12a4 4 0 00-4-4h0a4 4 0 00-4 4v0a2 2 0 002 2h4a2 2 0 002-2zM12 12a4 4 0 014-4h0a4 4 0 014 4v0a2 2 0 01-2 2h-4a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold">PawPet<span class="text-blue-400">Cares</span></h2>
                    </div>
                    <p class="text-gray-400 mb-6">Modernizing pet care and management for the community of Cantilan.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                        <li><a href="#vision" class="text-gray-400 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white transition-colors">How It Works</a></li>
                        <li><a href="#testimonials" class="text-gray-400 hover:text-white transition-colors">Testimonials</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">FAQs</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Our Services</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Pet Licensing</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Vaccination Records</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">QR Pet Tags</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Health Reminders</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Online Payments</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Pet Lost & Found</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Contact Us</h3>
                    <p class="text-gray-400 mb-4">Have questions? Reach out to us!</p>
                    <a href="mailto:support@pawpetcares.com" class="font-semibold text-blue-400 hover:text-blue-300 transition-colors block mb-4">
                        support@pawpetcares.com
                    </a>
                    <p class="text-gray-400 mb-2">
                        <i class="fas fa-map-marker-alt mr-2"></i> Cantilan, Surigao del Sur, Philippines
                    </p>
                    <p class="text-gray-400 mb-2">
                        <i class="fas fa-phone-alt mr-2"></i> (086) 234-5678
                    </p>
                    <p class="text-gray-400">
                        <i class="fas fa-clock mr-2"></i> Mon-Fri: 8:00 AM - 5:00 PM
                    </p>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
                <p>&copy; 2024 PawPetCares. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu functionality
        const menuToggle = document.getElementById('menu-toggle');
        const menuClose = document.getElementById('menu-close');
        const mobileMenu = document.getElementById('mobile-menu');
        
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        menuClose.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
        
        // Close mobile menu when clicking on links
        const mobileLinks = document.querySelectorAll('#mobile-menu a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        });
        
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.card-hover, .testimonial-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            animatedElements.forEach(el => {
                el.style.opacity = 0;
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(el);
            });
        });
    </script>

</body>
</html>