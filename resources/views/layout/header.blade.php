<!-- Navigation -->
<nav class="fixed w-full z-50 bg-dark-bg/80 backdrop-blur-xl border-b border-yolixa-purple/20 nav-glass">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo -->
      <div class="flex items-center space-x-4">
        <div class="relative group">
          <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center logo-glow transition-all duration-300 group-hover:scale-110">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
          </div>
          <div class="absolute inset-0 w-12 h-12 gradient-bg rounded-xl opacity-0 group-hover:opacity-30 blur-lg transition-opacity duration-300"></div>
        </div>
        <div class="text-3xl font-black gradient-text tracking-tight">Yolixa</div>
      </div>

      <!-- Right Side: Links + Connect Wallet -->
      <div class="flex items-center">
        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center space-x-1">
          <a href="#features" class="nav-link px-4 py-2 rounded-lg font-medium transition-all duration-300 hover:bg-yolixa-purple/10 hover:text-yolixa-purple">
            Features
          </a>
          <a href="#how-it-works" class="nav-link px-4 py-2 rounded-lg font-medium transition-all duration-300 hover:bg-yolixa-purple/10 hover:text-yolixa-purple">
            How It Works
          </a>
          <a href="#roadmap" class="nav-link px-4 py-2 rounded-lg font-medium transition-all duration-300 hover:bg-yolixa-purple/10 hover:text-yolixa-purple">
            Roadmap
          </a>
          <a href="{{ route('whitepaper') }}" target="_blank" class="nav-link px-4 py-2 rounded-lg font-medium transition-all duration-300 hover:bg-yolixa-purple/10 hover:text-yolixa-purple">
            Whitepaper
          </a>
          <a href="#contact" class="nav-link px-4 py-2 rounded-lg font-medium transition-all duration-300 hover:bg-yolixa-purple/10 hover:text-yolixa-purple">
            Contact
          </a>
        </div>

        <!-- Single Connect Wallet Button (for both views) -->
        <button id="connectWalletBtn"
                onclick="openWalletModal()"
                class="border border-yolixa-purple px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-yolixa-purple/10 transition-all duration-300 ml-4">
          Connect Wallet
        </button>

        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn"
                class="ml-3 p-2 text-white hover:text-yolixa-purple transition-all duration-300 hover:bg-yolixa-purple/10 rounded-lg md:hidden">
          <svg class="w-6 h-6 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu"
       class="mobile-menu fixed top-0 right-0 h-full w-80 bg-[#0a0a0a]/95 backdrop-blur-lg border-l border-yolixa-purple/30 shadow-2xl shadow-yolixa-purple/10 md:hidden transition-all duration-300 z-50">
    <div class="p-8">
      <div class="flex justify-between items-center mb-8">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 gradient-bg rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
          </div>
          <div class="text-xl font-bold gradient-text">Yolixa</div>
        </div>
        <button id="close-menu"
                class="p-2 text-white hover:text-yolixa-purple transition-all duration-300 hover:bg-yolixa-purple/10 rounded-lg">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Mobile Links -->
      <div class="space-y-2">
        <a href="#features" class="mobile-nav-link block px-4 py-3 rounded-lg font-medium hover:bg-yolixa-purple/10 hover:text-yolixa-purple hover:translate-x-2 transition-all duration-300">Features</a>
        <a href="#how-it-works" class="mobile-nav-link block px-4 py-3 rounded-lg font-medium hover:bg-yolixa-purple/10 hover:text-yolixa-purple hover:translate-x-2 transition-all duration-300">How It Works</a>
        <a href="#roadmap" class="mobile-nav-link block px-4 py-3 rounded-lg font-medium hover:bg-yolixa-purple/10 hover:text-yolixa-purple hover:translate-x-2 transition-all duration-300">Roadmap</a>
        <a href="{{ route('whitepaper') }}" target="_blank" class="mobile-nav-link block px-4 py-3 rounded-lg font-medium hover:bg-yolixa-purple/10 hover:text-yolixa-purple hover:translate-x-2 transition-all duration-300">Whitepaper</a>
        <a href="#contact" class="mobile-nav-link block px-4 py-3 rounded-lg font-medium hover:bg-yolixa-purple/10 hover:text-yolixa-purple hover:translate-x-2 transition-all duration-300">Contact</a>
      </div>
    </div>
  </div>
</nav>
