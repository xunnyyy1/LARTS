/* ============================================================
   LARTS – Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // ── Real-time clock ─────────────────────────────────────────
    function pad(n) { return String(n).padStart(2, '0'); }

    function updateClocks() {
        const now = new Date();
        const timeStr = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const dateStr = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;

        document.querySelectorAll('[data-live-time]').forEach(el => { el.textContent = timeStr; });
        document.querySelectorAll('[data-live-date]').forEach(el => { el.textContent = dateStr; });

        const topTime = document.getElementById('liveClockTime');
        const topDate = document.getElementById('liveClockDate');
        if (topTime) topTime.textContent = timeStr;
        if (topDate) topDate.textContent = dateStr;
    }

    updateClocks();
    setInterval(updateClocks, 1000);

    // ── Persistent Sidebar Collapse (desktop) ───────────────────
    const body = document.body;
    const collapseToggle = document.getElementById('sidebarCollapseToggle');

    function applySidebarCollapsed(collapsed) {
        const shouldCollapse = collapsed && window.innerWidth > 768;
        body.classList.toggle('sidebar-collapsed', shouldCollapse);

        if (collapseToggle) {
            const icon = collapseToggle.querySelector('i');
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            if (icon) icon.className = isCollapsed ? 'bi bi-layout-sidebar-inset-reverse' : 'bi bi-layout-sidebar-inset';
            collapseToggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }

        // Ensure sidebar items never show hover tooltips via title/data attrs
        const sidebarRoot = document.getElementById('sidebar');
        if (sidebarRoot) {
            sidebarRoot.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                const tip = bootstrap.Tooltip.getInstance(el);
                if (tip) tip.dispose();
                el.removeAttribute('data-bs-toggle');
                el.removeAttribute('data-bs-placement');
            });
            sidebarRoot.querySelectorAll('[title]').forEach(el => {
                // keep native browser tooltips off inside sidebar
                el.removeAttribute('title');
            });
        }
    }

    const storedCollapsed = localStorage.getItem('larts.sidebarCollapsed') === '1';
    applySidebarCollapsed(storedCollapsed);

    collapseToggle?.addEventListener('click', () => {
        const next = !(localStorage.getItem('larts.sidebarCollapsed') === '1');
        localStorage.setItem('larts.sidebarCollapsed', next ? '1' : '0');
        applySidebarCollapsed(next);
    });

    window.addEventListener('resize', () => {
        const current = localStorage.getItem('larts.sidebarCollapsed') === '1';
        applySidebarCollapsed(current);
    });

    // ── Sidebar Toggle ─────────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const mainWrap = document.getElementById('mainWrap');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Auto-dismiss alerts ────────────────────────────────────
    document.querySelectorAll('.alert.fade.show').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 5000);
    });

    // ── Confirm delete ────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm(btn.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ── Search filter ─────────────────────────────────────────
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase();
            document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ── Income/Expense balance calculator (inline) ───────────
    function updateBalance() {
        const inc  = parseFloat(document.getElementById('calc_income')?.value)  || 0;
        const exp  = parseFloat(document.getElementById('calc_expenses')?.value) || 0;
        const bal  = inc - exp;
        const el   = document.getElementById('calc_balance');
        if (!el) return;
        el.textContent = '₱ ' + bal.toLocaleString('en-PH', {minimumFractionDigits:2});
        el.className = bal > 0 ? 'text-success fw-bold' : bal < 0 ? 'text-danger fw-bold' : 'text-warning fw-bold';
        const statusEl = document.getElementById('calc_status');
        if (statusEl) {
            if (inc > exp)       statusEl.textContent = 'Financially Stable';
            else if (inc === exp) statusEl.textContent = 'At Risk';
            else                  statusEl.textContent = 'Financially Vulnerable';
            statusEl.className = bal > 0 ? 'badge bg-success' : bal < 0 ? 'badge bg-danger' : 'badge bg-warning text-dark';
        }
    }
    document.getElementById('calc_income')?.addEventListener('input', updateBalance);
    document.getElementById('calc_expenses')?.addEventListener('input', updateBalance);

    // ── Print page ────────────────────────────────────────────
    document.querySelectorAll('[data-print]').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

    // ── Tooltips ──────────────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

});