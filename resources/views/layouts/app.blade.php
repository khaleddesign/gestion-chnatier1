<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'Gestion Chantiers'))</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts et Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 antialiased" x-data="{ mobileMenuOpen: false }">
    <div id="app">
       <!-- Navigation -->
@auth
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo et navigation principale -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-900">
                            🏗️ {{ config('app.name', 'Gestion Chantiers') }}
                        </a>
                    </div>
                    
                    <!-- Navigation links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <a href="{{ route('dashboard') }}" 
                           class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : 'nav-link-inactive' }}">
                            <svg class="w-4 h-4 mr-2 inline-flex items-center" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span class="inline-flex items-center">Dashboard</span>
                        </a>
                        
                        <a href="{{ route('chantiers.index') }}" 
                           class="nav-link {{ request()->routeIs('chantiers.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                            <svg class="w-4 h-4 mr-2 inline-flex items-center" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m2.25-18v18m13.5-18v18M6.75 9h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.75m-.75 3h.75m-.75 3h.75m-3.75-16.5h3.75A2.25 2.25 0 0121 6.75v12a2.25 2.25 0 01-2.25 2.25h-3.75M16.5 7.5V21a.75.75 0 01-.75.75M6 7.5V21a.75.75 0 01-.75.75" />
                            </svg>
                            <span class="inline-flex items-center">Chantiers</span>
                        </a>
                        
                        <!-- NOUVEAU : Lien Messagerie -->
                        <a href="{{ route('messages.index') }}" 
                           class="nav-link {{ request()->routeIs('messages.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                            <svg class="w-4 h-4 mr-2 inline-flex items-center" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07 1.916V6.75" />
                            </svg>
                            <span class="inline-flex items-center">
                                Messagerie
                                @if(Auth::user()->getUnreadMessagesCount() > 0)
                                    <span class="ml-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                        {{ Auth::user()->getUnreadMessagesCount() }}
                                    </span>
                                @endif
                            </span>
                        </a>
                        
                        @can('admin-only')
                            <a href="{{ route('admin.users') }}" 
                               class="nav-link {{ request()->routeIs('admin.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                                <svg class="w-4 h-4 mr-2 inline-flex items-center" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                <span class="inline-flex items-center">Admin</span>
                            </a>
                        @endcan
                    </div>
                </div>
                
                <!-- Notifications et Menu utilisateur -->
                <div class="hidden sm:flex sm:items-center">
                    <!-- Notifications -->
                    <div class="relative flex items-center mr-4">
                        <a href="{{ route('notifications.index') }}" 
                           class="p-2 text-gray-400 hover:text-gray-500 relative flex items-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @if(Auth::user()->getNotificationsNonLues() > 0)
                                <span class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                    {{ Auth::user()->getNotificationsNonLues() }}
                                </span>
                            @endif
                        </a>
                    </div>
                    
                    <!-- Dropdown menu utilisateur -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-700">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </span>
                            </div>
                            <span class="ml-2 text-gray-700 font-medium">{{ Auth::user()->name }}</span>
                            <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <!-- Menu déroulant -->
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <div class="px-4 py-2 text-sm text-gray-500 border-b">
                                    {{ Auth::user()->email }}
                                    <br>
                                    <span class="badge badge-{{ Auth::user()->role === 'admin' ? 'danger' : (Auth::user()->role === 'commercial' ? 'warning' : 'primary') }}">
                                        {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </div>
                                <a href="{{ route('notifications.index') }}" class="dropdown-item flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    Notifications
                                </a>
                                <a href="{{ route('messages.index') }}" class="dropdown-item flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07 1.916V6.75" />
                                    </svg>
                                    Messages
                                    @if(Auth::user()->getUnreadMessagesCount() > 0)
                                        <span class="ml-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">
                                            {{ Auth::user()->getUnreadMessagesCount() }}
                                        </span>
                                    @endif
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item w-full text-left flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                        </svg>
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Menu mobile -->
                <div class="sm:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Menu mobile -->
        <div x-show="mobileMenuOpen" class="sm:hidden border-t border-gray-200">
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="block pl-3 pr-4 py-2 text-gray-600 hover:text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('chantiers.index') }}" class="block pl-3 pr-4 py-2 text-gray-600 hover:text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m2.25-18v18m13.5-18v18M6.75 9h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.75m-.75 3h.75m-.75 3h.75m-3.75-16.5h3.75A2.25 2.25 0 0121 6.75v12a2.25 2.25 0 01-2.25 2.25h-3.75M16.5 7.5V21a.75.75 0 01-.75.75M6 7.5V21a.75.75 0 01-.75.75" />
                    </svg>
                    Chantiers
                </a>
                
                <!-- NOUVEAU : Lien Messagerie mobile -->
                <a href="{{ route('messages.index') }}" class="block pl-3 pr-4 py-2 text-gray-600 hover:text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07 1.916V6.75" />
                    </svg>
                    Messagerie
                    @if(Auth::user()->getUnreadMessagesCount() > 0)
                        <span class="ml-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ Auth::user()->getUnreadMessagesCount() }}
                        </span>
                    @endif
                </a>
                
                @can('admin-only')
                    <a href="{{ route('admin.users') }}" class="block pl-3 pr-4 py-2 text-gray-600 hover:text-gray-900 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        Admin
                    </a>
                @endcan
            </div>
        </div>
    </nav>
@endauth
        
        <!-- Messages Flash -->
        @if(session('success'))
            <div class="alert alert-success animate-fade-in-up" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
                <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-error animate-fade-in-up" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{ session('error') }}
                <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        
        @if(session('warning'))
            <div class="alert alert-warning animate-fade-in-up" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                {{ session('warning') }}
                <button @click="show = false" class="ml-auto text-yellow-600 hover:text-yellow-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info animate-fade-in-up" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                {{ session('info') }}
                <button @click="show = false" class="ml-auto text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        
        <!-- Contenu principal -->
        <main class="min-h-screen">
            @yield('content')
        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @stack('scripts')
    @yield('scripts')
</body>
</html>