<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#7c73d9',
                        accent: '#f97316',
                        light: '#f8fafc',
                        dark: '#1e293b'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .stats-card {
            border-left: 4px solid #4f46e5;
        }
        
        .pet-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
        }
        
        .qr-code img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
        }
        
        .btn-primary {
            background-color: #4f46e5;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #3730a3;
        }
        
        .notification-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
            position: absolute;
            top: -2px;
            right: -2px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex-1 p-6 md:p-8">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-dark">Dashboard</h2>
                <p class="text-sm text-gray-500">Welcome back, John! Here's your pet overview.</p>
            </div>
            
            <div class="flex items-center space-x-4 mt-4 md:mt-0">
                <div class="relative cursor-pointer">
                    <div class="bg-gray-100 p-2 rounded-lg">
                        <i class="fas fa-bell text-gray-500"></i>
                        <span class="notification-dot"></span>
                    </div>
                </div>
                <div class="flex items-center bg-white pl-1 pr-4 py-1 rounded-full shadow-sm">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="User" class="h-10 w-10 rounded-full object-cover">
                    <span class="ml-2 text-gray-700 font-medium">John Santos</span>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stats-card card p-6 bg-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Registered Pets</p>
                        <h3 class="text-2xl font-bold text-dark mt-1">3</h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-paw text-blue-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                    Last added: Bruno (Dog)
                </p>
            </div>
            
            <div class="stats-card card p-6 bg-white" style="border-left-color: #10b981;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Vaccinations</p>
                        <h3 class="text-2xl font-bold text-dark mt-1">5</h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-syringe text-green-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Next due: Oct 15, 2025
                </p>
            </div>
            
            <div class="stats-card card p-6 bg-white" style="border-left-color: #f59e0b;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Upcoming Appointments</p>
                        <h3 class="text-2xl font-bold text-dark mt-1">2</h3>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-lg">
                        <i class="fas fa-calendar text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                    Next: Tomorrow, 10:00 AM
                </p>
            </div>
            
            <div class="stats-card card p-6 bg-white" style="border-left-color: #ef4444;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Actions</p>
                        <h3 class="text-2xl font-bold text-dark mt-1">1</h3>
                    </div>
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                    License renewal required
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card p-6 bg-white">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-dark">My Pets</h3>
                        <button class="btn-primary text-sm">
                            <i class="fas fa-plus mr-2"></i> Add Pet
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <div class="relative">
                                    <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?ixlib=rb-1.2.1&auto=format&fit=crop&w=200&q=80" alt="Pet" class="pet-avatar mr-4">
                                    <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
                                        <i class="fas fa-dog text-xs"></i>
                                    </span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-dark">Bruno</h4>
                                    <p class="text-sm text-gray-500">Golden Retriever • 3 years old</p>
                                    <div class="flex items-center mt-1">
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Licensed</span>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full ml-2">Vaccinated</span>
                                    </div>
                                </div>
                            </div>
                            <div class="qr-code">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Bruno-12345" alt="QR Code">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <div class="relative">
                                    <img src="https://images.unsplash.com/photo-1592194996308-7b43878e84a6?ixlib=rb-1.2.1&auto=format&fit=crop&w=200&q=80" alt="Pet" class="pet-avatar mr-4">
                                    <span class="absolute -bottom-1 -right-1 bg-orange-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
                                        <i class="fas fa-cat text-xs"></i>
                                    </span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-dark">Mimi</h4>
                                    <p class="text-sm text-gray-500">Siamese Cat • 2 years old</p>
                                    <div class="flex items-center mt-1">
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Licensed</span>
                                        <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full ml-2">Vaccination Due</span>
                                    </div>
                                </div>
                            </div>
                            <div class="qr-code">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Mimi-67890" alt="QR Code">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card p-6 bg-white">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-dark">Alerts & Reminders</h3>
                        <a href="#" class="text-sm text-primary font-medium hover:text-secondary">View All</a>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-info-circle text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-dark">Vaccination Reminder</h4>
                                <p class="text-sm text-gray-600">Bruno's rabies vaccine is due in 7 days</p>
                                <p class="text-xs text-gray-500 mt-1">2 hours ago</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                            <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-dark">License Expiry</h4>
                                <p class="text-sm text-gray-600">Mimi's pet license expires in 30 days</p>
                                <p class="text-xs text-gray-500 mt-1">1 day ago</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-dark">Payment Confirmed</h4>
                                <p class="text-sm text-gray-600">Your payment of ₱500 has been processed</p>
                                <p class="text-xs text-gray-500 mt-1">2 days ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-6">
                <div class="card p-6 bg-white">
                    <h3 class="text-lg font-semibold text-dark mb-6">Quick Actions</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <a href="#" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="bg-blue-100 p-3 rounded-lg mb-2">
                                <i class="fas fa-id-card text-blue-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 text-center">Renew License</span>
                        </a>
                        
                        <a href="#" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="bg-green-100 p-3 rounded-lg mb-2">
                                <i class="fas fa-syringe text-green-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 text-center">Book Vaccination</span>
                        </a>
                        
                        <a href="#" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="bg-purple-100 p-3 rounded-lg mb-2">
                                <i class="fas fa-credit-card text-purple-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 text-center">Make Payment</span>
                        </a>
                        
                        <a href="#" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="bg-red-100 p-3 rounded-lg mb-2">
                                <i class="fas fa-qrcode text-red-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 text-center">View QR Codes</span>
                        </a>
                    </div>
                </div>
                
                <div class="card p-6 bg-white">
                    <h3 class="text-lg font-semibold text-dark mb-6">Community Events</h3>
                    
                    <div class="bg-blue-50 p-4 rounded-lg mb-4 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-blue-800">Community Vaccination Day</span>
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Free</span>
                        </div>
                        <p class="text-sm text-blue-700">October 28, 2025 • 9:00 AM - 4:00 PM</p>
                        <p class="text-sm text-blue-600 mt-1">Butuan City Hall Grounds</p>
                        <button class="mt-3 text-xs bg-blue-500 text-white px-3 py-1 rounded-full hover:bg-blue-600 transition-colors">
                            Register Now
                        </button>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-green-800">Anti-Rabies Campaign</span>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Free</span>
                        </div>
                        <p class="text-sm text-green-700">November 15, 2025 • 8:00 AM - 3:00 PM</p>
                        <p class="text-sm text-green-600 mt-1">Butuan City Public Market</p>
                        <button class="mt-3 text-xs bg-green-500 text-white px-3 py-1 rounded-full hover:bg-green-600 transition-colors">
                            Learn More
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>