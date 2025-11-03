<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon-32x32.png') }}">

    {{-- Toaster CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <title>Yolixa - Empowering Influencers with Web3 Tipping</title>

    {{-- Tailwind CDN --}}
    <script src="{{ asset('assets/js/talwind_cdn.js') }}"></script>

    {{-- Freighter CDN --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-freighter-api/3.0.0/index.min.js"></script>

    {{-- Tailwind Config --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'yolixa-blue': '#004aad',
                        'yolixa-purple': '#cb6ce6',
                        'dark-bg': '#0d0d0d'
                    }
                }
            }
        }
    </script>

    {{-- Fonts & CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/fonts/inter_font_family.css') }}">

    <style>
        * {
        font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
        background: linear-gradient(135deg, #004aad 0%, #cb6ce6 100%);
        }
        .gradient-text {
        background: linear-gradient(135deg, #004aad, #cb6ce6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        }
        .glow-effect {
        box-shadow: 0 0 30px rgba(203, 108, 230, 0.3);
        }
        .card-hover {
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .card-hover:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(203, 108, 230, 0.2);
        border-color: rgba(203, 108, 230, 0.3);
        }
        .floating {
        animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
        }
        .pulse-glow {
        animation: pulse-glow 2s ease-in-out infinite alternate;
        }
        @keyframes pulse-glow {
        from { box-shadow: 0 0 20px rgba(203, 108, 230, 0.4); }
        to { box-shadow: 0 0 40px rgba(203, 108, 230, 0.8); }
        }
        .hero-bg {
        background: radial-gradient(ellipse at center, rgba(0, 74, 173, 0.1) 0%, transparent 70%),
        radial-gradient(ellipse at 80% 20%, rgba(203, 108, 230, 0.1) 0%, transparent 50%);
        }
        .mobile-menu {
        transform: translateX(100%);
        transition: transform 0.3s ease-in-out;
        }
        .mobile-menu.open {
        transform: translateX(0);
        }
        .nav-glass {
        background: rgba(13, 13, 13, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(203, 108, 230, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .mobile-glass {
        background: rgba(13, 13, 13, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
        }
        .logo-glow {
        box-shadow: 0 0 20px rgba(203, 108, 230, 0.3);
        }
        .nav-link {
        position: relative;
        overflow: hidden;
        }
        .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(203, 108, 230, 0.2), transparent);
        transition: left 0.5s;
        }
        .nav-link:hover::before {
        left: 100%;
        }
        .cta-button {
        position: relative;
        overflow: hidden;
        }
        .cta-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s;
        }
        .cta-button:hover::before {
        left: 100%;
        }
        .mobile-nav-link {
        position: relative;
        overflow: hidden;
        }
        .mobile-nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #004aad, #cb6ce6);
        transition: width 0.3s ease;
        }
        .mobile-nav-link:hover::after {
        width: 100%;
        }
        /* Navbar scroll effect */
        .nav-scrolled {
        background: rgba(13, 13, 13, 0.95) !important;
        border-bottom-color: rgba(203, 108, 230, 0.4) !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4) !important;
        }

        #toast-container > div {
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            color: #fff;
        }

        /* ✅ SUCCESS */
        #toast-container > .toast-success {
            background-color: #16a34a !important; /* Tailwind Green-600 */
            border-left: 6px solid #22c55e;
        }

        /* ⚠️ WARNING */
        #toast-container > .toast-warning {
            background-color: #facc15 !important; /* Tailwind Yellow-400 */
            color: #000;
            border-left: 6px solid #ca8a04;
        }

        /* ❌ ERROR */
        #toast-container > .toast-error {
            background-color: #dc2626 !important; /* Tailwind Red-600 */
            border-left: 6px solid #ef4444;
        }

        /* ℹ️ INFO */
        #toast-container > .toast-info {
            background-color: #2563eb !important; /* Tailwind Blue-600 */
            border-left: 6px solid #3b82f6;
        }
    </style>

    @stack('css')
</head>
<body class="bg-dark-bg text-white overflow-x-hidden">
    @include('layout.header')

    @yield('content')

    @include('layout.footer')

    {{-- Toaster CDN --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {{-- Wallet Connect --}}
    <script src="https://unpkg.com/@walletconnect/client/dist/umd/index.min.js"></script>
    <script src="https://unpkg.com/@walletconnect/qrcode-modal/dist/umd/index.min.js"></script>
    <script src="https://unpkg.com/@web3modal/wagmi@3.5.3/dist/index.js"></script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000"
        };
    </script>

    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const closeMenuBtn = document.getElementById('close-menu');
        const mobileMenuBtnIcon = mobileMenuBtn.querySelector('svg');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.add('open');
            mobileMenuBtnIcon.style.transform = 'rotate(90deg)';
        });

        closeMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            mobileMenuBtnIcon.style.transform = 'rotate(0deg)';
        });

        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                mobileMenuBtnIcon.style.transform = 'rotate(0deg)';
            });
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('nav-scrolled');
            } else {
                nav.classList.remove('nav-scrolled');
            }
        });

        function openWalletModal() {
            const modal = document.getElementById('walletModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            const blockchainSelect = modal.querySelector('#walletBlockchainSelect');
            const walletSelect = modal.querySelector('#walletWalletSelect');

            if (blockchainSelect) blockchainSelect.selectedIndex = 0;
            if (walletSelect) walletSelect.selectedIndex = 0;
        }

        function closeWalletModal() {
            const modal = document.getElementById('walletModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function openCreatorModal() {
            const modal = document.getElementById('creatorModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCreatorModal() {
            const modal = document.getElementById('creatorModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function saveCreatorDetails() {
            const name = document.getElementById('creatorName').value.trim();
            const email = document.getElementById('creatorEmail').value.trim();
            const blockchain = document.getElementById('creatorBlockchainSelect').value.trim();
            const wallet = document.querySelector('.creatorWalletSelect').value.trim();

            if (!name || !email || !blockchain || !wallet) {
                alert("Please fill all fields.");
                return;
            }

            console.log("Creator Details:", { name, email, blockchain, wallet });
            toastr.success(`Welcome ${name}! You have successfully joined as a creator.`);

            closeCreatorModal();
        }
    </script>

    <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'97be0440f7dbc976',t:'MTc1NzMzMDAwOC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script>
    @stack('js')
</body>
</html>
