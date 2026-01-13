<header
  id="site-header"
  class="sticky top-0 z-50 bg-white/90 backdrop-blur-md shadow-lg border-b border-gray-200"
>
  <div class="container mx-auto px-6 flex items-center justify-between h-16">

    <!-- ===== LOGO ===== -->
    <a href="/smarttourist/public/index.php" class="flex items-center">
      <div class="h-20 flex items-center justify-center">
        <img
          src="assets/image/logo.png"
          alt="SmartTourist"
          class="h-20 w-auto object-contain"
        />
      </div>
    </a>

    <!-- ===== NAV ===== -->
    <nav class="hidden md:flex items-center gap-10 text-base tracking-wide font-semibold">

      <!-- Du lịch (Mega menu trigger) -->
      <div
        id="menu-trigger"
        class="relative cursor-pointer group"
      >
        <span
          id="menu-label"
          class="pb-1 transition-all duration-300 hover:text-[var(--gold)] group-hover:scale-105  text-gray-700"
        >
          Du lịch
        </span>
      </div>

      <a href="about.php"
         class="hover:text-[var(--gold)] transition-all duration-300 hover:scale-105  text-gray-700">
        Giới thiệu
      </a>

      <a href="contact.php"
         class="hover:text-[var(--gold)] transition-all duration-300 hover:scale-105  text-gray-700">
        Liên hệ
      </a>
    </nav>

    <!-- ===== CTA ===== -->

  </div>

  <!-- ===== MEGA MENU ===== -->
  <?php include __DIR__ . '/mega-menu.php'; ?>
</header>

<!-- ===== HEADER SCRIPT ===== -->
<script>
  const trigger = document.getElementById('menu-trigger');
  const menu = document.getElementById('mega-menu');
  const label = document.getElementById('menu-label');
  const header = document.getElementById('site-header');

  let isOpen = false;

  function openMenu() {
    isOpen = true;
    menu.classList.remove('hidden');
    label.classList.add('text-[var(--gold)]', 'border-b-2', 'border-[var(--gold)]');
  }

  function closeMenu() {
    isOpen = false;
    menu.classList.add('hidden');
    label.classList.remove('text-[var(--gold)]', 'border-b-2', 'border-[var(--gold)]');
  }

  trigger.addEventListener('mouseenter', openMenu);
  trigger.addEventListener('click', () => {
    isOpen ? closeMenu() : openMenu();
  });

  header.addEventListener('mouseleave', closeMenu);
</script>
