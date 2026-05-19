/* ============================================================
   LARTS – Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

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