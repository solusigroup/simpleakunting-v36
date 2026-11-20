    </main> <!-- Penutup .p-4 -->
    
    <footer class="footer mt-auto py-3 text-muted text-center">
        <div class="container">
            <span>
                &copy; <?php echo date('Y'); ?> - SIMPLE AKUNTING created by <a href="https://simpleakunting.id/" target="_blank" class="fw-bold text-decoration-none">Kurniawan</a>
            </span>
        </div>
    </footer>

    <nav class="mobile-bottom-nav">
        <div class="nav-item">
            <a href="<?php echo BASEURL; ?>/dashboard" class="nav-link <?php echo ($current_controller == 'dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Beranda</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?php echo BASEURL; ?>/penjualan" class="nav-link <?php echo ($current_controller == 'penjualan') ? 'active' : ''; ?>">
                <i class="bi bi-cart-check"></i>
                <span>Jual</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?php echo BASEURL; ?>/kas" class="nav-link <?php echo ($current_controller == 'kas') ? 'active' : ''; ?>">
                <i class="bi bi-bank"></i>
                <span>Kas</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?php echo BASEURL; ?>/laporan" class="nav-link <?php echo ($current_controller == 'laporan') ? 'active' : ''; ?>">
                <i class="bi bi-pie-chart-fill"></i>
                <span>Laporan</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="#" class="nav-link" id="mobile-menu-toggle">
                <i class="bi bi-list"></i>
                <span>Menu</span>
            </a>
        </div>
    </nav>

</div> <!-- Penutup .main-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const themeToggle = document.getElementById('theme-toggle');
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            // Sidebar Toggle (Mobile & Desktop)
            const savedSidebar = localStorage.getItem('sidebar-collapsed') === 'true';
            if (savedSidebar && window.innerWidth >= 992) {
                body.classList.add('sidebar-collapsed');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        body.classList.toggle('sidebar-open');
                    } else {
                        body.classList.toggle('sidebar-collapsed');
                        localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed'));
                    }
                });
            }

            // Theme Toggle
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);

            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const currentTheme = body.getAttribute('data-theme');
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    setTheme(newTheme);
                });
            }

            function setTheme(theme) {
                body.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                if (themeIcon) {
                    themeIcon.className = theme === 'light' ? 'bi bi-moon-fill fs-5' : 'bi bi-sun-fill fs-5';
                }
            }
            
            // Auto-hide sidebar on small screens after clicking link
            const sidebarLinks = document.querySelectorAll('.sidebar a.nav-link:not([data-bs-toggle])');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        body.classList.remove('sidebar-open');
                    }
                });
            });

            // Mobile Menu Toggle from bottom nav
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    body.classList.toggle('sidebar-open');
                });
            }

            // Global CSRF Protection Handler for Forms
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form && form.tagName === 'FORM' && (form.method || '').toUpperCase() === 'POST') {
                    if (!form.querySelector('input[name="csrf_token"]')) {
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        csrfInput.value = window.CSRF_TOKEN || '';
                        form.appendChild(csrfInput);
                    }
                }
            }, true);

            // Global fetch interceptor to attach X-CSRF-TOKEN header
            const originalFetch = window.fetch;
            window.fetch = function(url, options) {
                options = options || {};
                const method = (options.method || 'GET').toUpperCase();
                if (method === 'POST' || method === 'PUT' || method === 'DELETE' || method === 'PATCH') {
                    options.headers = options.headers || {};
                    if (options.headers instanceof Headers) {
                        if (!options.headers.has('X-CSRF-TOKEN') && window.CSRF_TOKEN) {
                            options.headers.append('X-CSRF-TOKEN', window.CSRF_TOKEN);
                        }
                    } else if (typeof options.headers === 'object') {
                        if (!options.headers['X-CSRF-TOKEN'] && window.CSRF_TOKEN) {
                            options.headers['X-CSRF-TOKEN'] = window.CSRF_TOKEN;
                        }
                    }
                }
                return originalFetch.call(this, url, options);
            };

            // Safety Interceptor: Convert any remaining GET delete links to POST with CSRF token
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href) return;
                
                if (href.includes('/hapus/') || href.includes('_hapus/') || href.includes('/delete/')) {
                    e.preventDefault();
                    const confirmMsg = link.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
                    if (confirm(confirmMsg)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = href;
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = 'csrf_token';
                        csrf.value = window.CSRF_TOKEN || '';
                        form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            });

    });
    </script>
    <!-- PWA Helper Script -->
    <script src="<?php echo BASEURL; ?>/js/pwa.js?v=3.6"></script>
</body>
</html>

