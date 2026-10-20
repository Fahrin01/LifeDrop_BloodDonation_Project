<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$admin = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($targetId === $admin['id']) {
        flash_set('danger', 'You cannot perform this action on your own account.');
        redirect('admin/users.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        flash_set('danger', 'User not found.');
    } elseif ($action === 'suspend') {
        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = :id")->execute([':id' => $targetId]);
        flash_set('success', 'User suspended.');
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = :id")->execute([':id' => $targetId]);
        flash_set('success', 'User activated.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $targetId]);
        flash_set('success', 'User account deleted.');
    }
    redirect('admin/users.php');
}

$search = clean($_GET['q'] ?? '');
$roleFilter = clean($_GET['role'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');

$sql = 'SELECT * FROM users WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (full_name LIKE :q OR email LIKE :q OR phone LIKE :q)';
    $params[':q'] = "%$search%";
}
if (in_array($roleFilter, ['donor', 'seeker', 'both', 'admin'], true)) {
    $sql .= ' AND role = :role';
    $params[':role'] = $roleFilter;
}
if (in_array($statusFilter, ['active', 'suspended'], true)) {
    $sql .= ' AND status = :status';
    $params[':status'] = $statusFilter;
}
$sql .= ' ORDER BY created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('users'); ?>
    <div class="dash-main">
        <h1>Manage Users</h1>
        <p class="dash-welcome"><?= count($users) ?> user(s) shown</p>

        <form class="filters-bar" method="get" action="<?= e(SITE_URL) ?>/admin/users.php">
            <div class="filters-grid" style="grid-template-columns:2fr 1fr 1fr auto;">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name, email, or phone">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="">Any</option>
                        <option value="donor" <?= $roleFilter === 'donor' ? 'selected' : '' ?>>Donor</option>
                        <option value="seeker" <?= $roleFilter === 'seeker' ? 'selected' : '' ?>>Seeker</option>
                        <option value="both" <?= $roleFilter === 'both' ? 'selected' : '' ?>>Both</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Any</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email / Phone</th><th>Role</th><th>Blood Group</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['full_name']) ?> <?= $u['is_verified'] ? '<i class="fa-solid fa-circle-check" style="color:var(--green-600);" title="Verified"></i>' : '' ?></td>
                        <td><?= e($u['email']) ?><br><span class="text-muted"><?= e($u['phone']) ?></span></td>
                        <td><span class="badge badge-muted"><?= e(ucfirst($u['role'])) ?></span></td>
                        <td><?= e($u['blood_group'] ?? '—') ?></td>
                        <td><span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                        <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                        <td class="table-actions">
                            <?php if ($u['id'] !== $admin['id']): ?>
                                <?php if ($u['status'] === 'active'): ?>
                                <form method="post" style="display:inline;" data-confirm="Suspend this user?">
                                    <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= e($u['id']) ?>"><input type="hidden" name="action" value="suspend">
                                    <button type="submit" class="btn btn-light btn-sm">Suspend</button>
                                </form>
                                <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= e($u['id']) ?>"><input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" style="display:inline;" data-confirm="Permanently delete this account? This cannot be undone.">
                                    <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= e($u['id']) ?>"><input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:0.8rem;">(you)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
