<?php
/** @var array $users */

use App\Core\Helpers;

$actions = '<button type="button" onclick="openCreate()" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white transition hover:bg-brand-700">' . icon('plus', 'h-4 w-4') . 'Add User</button>';
?>
<?= page_header('Users', 'Manage who can access the system and their role.', $actions) ?>

<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">All users</h3></div>
    <div class="table-wrap">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-800"><?= Helpers::e((string) $u['name']) ?></td>
                        <td class="px-5 py-3 text-slate-600"><?= Helpers::e((string) $u['email']) ?></td>
                        <td class="px-5 py-3"><?= badge((string) $u['role'], $u['role'] === 'ADMIN' ? 'blue' : 'slate') ?></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-1">
                                <?php $editJson = json_encode(['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>
                                <button type="button" title="Edit" onclick='openEdit(<?= $editJson ?>)' class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"><?= icon('pencil', 'h-4 w-4') ?></button>
                                <form method="post" action="<?= url('/users/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('Delete this user?');">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" title="Delete" class="flex h-8 w-8 items-center justify-center rounded-lg text-red-600 hover:bg-red-50"><?= icon('trash', 'h-4 w-4') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
    <div class="mx-auto mt-10 w-full max-w-md rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 id="userModalTitle" class="text-base font-semibold text-slate-900">Add User</h2>
            <button type="button" onclick="closeModal('userModal')" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="userForm" method="post" action="<?= url('/users') ?>" class="space-y-4 p-5">
            <?= \App\Core\Csrf::field() ?>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                <input type="text" name="name" id="u_name" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" id="u_email" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" id="u_password_label">Password</label>
                <input type="password" name="password" id="u_password" placeholder="At least 6 characters" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
                <select name="role" id="u_role" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="OPERATOR">Operator</option>
                    <option value="ADMIN">Administrator</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('userModal')" class="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="userSubmit" class="h-10 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">Add user</button>
            </div>
        </form>
    </div>
</div>

<script>
    const USER_BASE = <?= json_encode(url('/users')) ?>;
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function openCreate() {
        document.getElementById('userModalTitle').textContent = 'Add User';
        document.getElementById('userForm').action = USER_BASE;
        document.getElementById('userSubmit').textContent = 'Add user';
        document.getElementById('u_name').value = '';
        document.getElementById('u_email').value = '';
        document.getElementById('u_password').value = '';
        document.getElementById('u_password').required = true;
        document.getElementById('u_password_label').textContent = 'Password';
        document.getElementById('u_role').value = 'OPERATOR';
        openModal('userModal');
    }

    function openEdit(u) {
        document.getElementById('userModalTitle').textContent = 'Edit User';
        document.getElementById('userForm').action = USER_BASE + '/' + u.id + '/update';
        document.getElementById('userSubmit').textContent = 'Save changes';
        document.getElementById('u_name').value = u.name;
        document.getElementById('u_email').value = u.email;
        document.getElementById('u_password').value = '';
        document.getElementById('u_password').required = false;
        document.getElementById('u_password_label').textContent = 'New password (leave blank to keep)';
        document.getElementById('u_role').value = u.role;
        openModal('userModal');
    }

    document.querySelectorAll('.fixed.inset-0').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.add('hidden');
        });
    });
</script>