<?php
require_once __DIR__ . '/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// ─── Auth Helpers ────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: ' . BASE_URL . '/dashboard/index.php?err=unauthorized');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id']   ?? 0,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ];
}

function isAdmin(): bool   { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function isStaff(): bool   { return in_array($_SESSION['user_role'] ?? '', ['admin','staff']); }
function isEncoder(): bool { return isset($_SESSION['user_role']); }

// ─── Flash Messages ──────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash(): string {
    $f = getFlash();
    if (!$f) return '';
    $icons = ['success'=>'check-circle','danger'=>'exclamation-triangle','warning'=>'exclamation-circle','info'=>'info-circle'];
    $icon  = $icons[$f['type']] ?? 'info-circle';
    return '<div class="alert alert-'.$f['type'].' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-'.$icon.'"></i>
        <span>'.htmlspecialchars($f['msg']).'</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// ─── Sanitize ────────────────────────────────────────────────
function clean(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

// ─── Economic Status Logic ───────────────────────────────────
function computeStatus(float $income, float $expenses): string {
    if ($income > $expenses)  return 'stable';
    if ($income == $expenses) return 'at_risk';
    return 'vulnerable';
}

function statusBadge(string $status): string {
    $map = [
        'stable'     => ['success', 'Stable',     'check-circle'],
        'at_risk'    => ['warning', 'At Risk',     'exclamation-circle'],
        'vulnerable' => ['danger',  'Vulnerable',  'exclamation-triangle'],
    ];
    [$cls, $label, $icon] = $map[$status] ?? ['secondary','Unknown','question'];
    return '<span class="badge bg-'.$cls.'"><i class="bi bi-'.$icon.' me-1"></i>'.$label.'</span>';
}

function formatMoney(float $v): string {
    return '₱ ' . number_format($v, 2);
}