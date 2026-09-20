<!-- views/users/index.php -->
<?php $pageTitle = 'User Administration'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for User Administration -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span>User Administration &mdash; Access Security</span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <!-- Window Action Bar -->
        <div class="bg-[#f0f4f9] border-b border-[#cbd9e8] p-2 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-[#1e395b] font-semibold">
                Manage operator credentials, system roles, active statuses, and account ownership.
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" onclick="openCreateUserModal()" class="win-btn font-bold text-purple-900">
                    + Create New User
                </button>
            </div>
        </div>

        <!-- Users Grid Table (Desktop Style) -->
        <div class="overflow-x-auto bg-white">
            <table class="w-full win-table border-collapse">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th style="width: 100px; text-align: center;">Role</th>
                        <th style="width: 90px; text-align: center;">Status</th>
                        <th style="width: 90px; text-align: center;">Accounts</th>
                        <th style="width: 110px; text-align: center;">Transactions</th>
                        <th style="width: 150px;">Last Login</th>
                        <th style="width: 150px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50 transition cursor-pointer" id="user-row-<?= $u['id'] ?>">
                            <td>
                                <span class="font-bold text-gray-900"><?= e($u['full_name']) ?></span>
                                <span class="text-[10px] text-gray-500 font-mono ml-1">@<?= e($u['username']) ?> (#<?= $u['id'] ?>)</span>
                            </td>
                            <td class="text-gray-700 text-xs">
                                <?= e($u['email']) ?>
                            </td>
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-300' : 'bg-blue-100 text-blue-800 border border-blue-300' ?>">
                                    <?= e($u['role']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded uppercase <?= $u['status'] === 'active' ? 'bg-green-100 text-green-800' : ($u['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td class="text-center font-mono font-bold text-gray-800 text-xs">
                                <?= $u['account_count'] ?>
                            </td>
                            <td class="text-center font-mono font-bold text-gray-800 text-xs">
                                <?= $u['tx_count'] ?>
                            </td>
                            <td class="text-gray-600 font-mono text-xs">
                                <?= $u['last_login_at'] ? e($u['last_login_at']) : 'Never' ?>
                            </td>
                            <td class="text-center space-x-1 whitespace-nowrap">
                                <button type="button" onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" class="win-btn win-btn-sm text-blue-900">
                                    Edit
                                </button>
                                <button type="button" onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= e($u['username']) ?>')" class="win-btn win-btn-sm text-purple-900">
                                    Password
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Desktop Modal: Create User -->
<div id="create-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-md w-full shadow-2xl">
        <div class="win-titlebar">
            <span>Create New System User</span>
            <span onclick="closeModal('create-user-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>
        <form id="create-user-form" class="p-4 space-y-3 bg-[#f2f5fa]">
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Username *</label>
                <input type="text" name="username" required placeholder="e.g. javed_iqbal" class="win-input w-full">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Full Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Javed Iqbal" class="win-input w-full">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Email *</label>
                <input type="email" name="email" required placeholder="user@domain.local" class="win-input w-full">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Initial Password *</label>
                <input type="password" name="password" required minlength="6" placeholder="Minimum 6 characters" class="win-input w-full">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Role</label>
                    <select name="role" class="win-input w-full bg-white">
                        <option value="user">Normal User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Status</label>
                    <select name="status" class="win-input w-full bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('create-user-modal')" class="win-btn">Cancel</button>
                <button type="submit" id="submit-create-user-btn" class="win-btn font-bold text-purple-900">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Desktop Modal: Edit User -->
<div id="edit-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-md w-full shadow-2xl">
        <div class="win-titlebar">
            <span id="edit-user-modal-title">Edit User</span>
            <span onclick="closeModal('edit-user-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>
        <form id="edit-user-form" class="p-4 space-y-3 bg-[#f2f5fa]">
            <input type="hidden" id="edit-user-id" name="id">
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Full Name *</label>
                <input type="text" id="edit-user-fullname" name="full_name" required class="win-input w-full">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Email *</label>
                <input type="email" id="edit-user-email" name="email" required class="win-input w-full">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Role</label>
                    <select id="edit-user-role" name="role" class="win-input w-full bg-white">
                        <option value="user">Normal User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Status</label>
                    <select id="edit-user-status" name="status" class="win-input w-full bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('edit-user-modal')" class="win-btn">Cancel</button>
                <button type="submit" id="submit-edit-user-btn" class="win-btn font-bold text-purple-900">Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- Desktop Modal: Reset Password -->
<div id="reset-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-sm w-full shadow-2xl">
        <div class="win-titlebar">
            <span id="reset-modal-title">Reset Password</span>
            <span onclick="closeModal('reset-password-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>
        <form id="reset-password-form" class="p-4 space-y-3 bg-[#f2f5fa]">
            <input type="hidden" id="reset-user-id" name="id">
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">New Password *</label>
                <input type="password" id="reset-password-val" name="password" required minlength="6" placeholder="Min 6 characters" class="win-input w-full">
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('reset-password-modal')" class="win-btn">Cancel</button>
                <button type="submit" id="submit-reset-btn" class="win-btn font-bold text-purple-900">Set Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateUserModal() {
    $('#create-user-form')[0].reset();
    openModal('create-user-modal');
}

function openEditUserModal(u) {
    $('#edit-user-modal-title').text('Edit User @' + u.username);
    $('#edit-user-id').val(u.id);
    $('#edit-user-fullname').val(u.full_name);
    $('#edit-user-email').val(u.email);
    $('#edit-user-role').val(u.role);
    $('#edit-user-status').val(u.status);
    openModal('edit-user-modal');
}

function openResetPasswordModal(id, username) {
    $('#reset-modal-title').text('Reset Password: @' + username);
    $('#reset-user-id').val(id);
    $('#reset-password-val').val('');
    openModal('reset-password-modal');
}

$('#create-user-form').on('submit', function(e) {
    e.preventDefault();
    $('#submit-create-user-btn').prop('disabled', true);
    $.post('/atm/api/users/create', $(this).serialize(), function(res) {
        $('#submit-create-user-btn').prop('disabled', false);
        if (res.success) {
            window.showToast('User created successfully!', 'success');
            closeModal('create-user-modal');
            setTimeout(function() { window.location.reload(); }, 600);
        } else {
            alert(res.message || 'Error creating user.');
        }
    }, 'json').fail(function(xhr) {
        $('#submit-create-user-btn').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error creating user.');
    });
});

$('#edit-user-form').on('submit', function(e) {
    e.preventDefault();
    var id = $('#edit-user-id').val();
    $('#submit-edit-user-btn').prop('disabled', true);
    $.post('/atm/api/users/' + id + '/update', $(this).serialize(), function(res) {
        $('#submit-edit-user-btn').prop('disabled', false);
        if (res.success) {
            window.showToast('User profile updated!', 'success');
            closeModal('edit-user-modal');
            setTimeout(function() { window.location.reload(); }, 600);
        } else {
            alert(res.message || 'Error updating user.');
        }
    }, 'json').fail(function(xhr) {
        $('#submit-edit-user-btn').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error updating user.');
    });
});

$('#reset-password-form').on('submit', function(e) {
    e.preventDefault();
    var id = $('#reset-user-id').val();
    $('#submit-reset-btn').prop('disabled', true);
    $.post('/atm/api/users/' + id + '/reset-password', $(this).serialize(), function(res) {
        $('#submit-reset-btn').prop('disabled', false);
        if (res.success) {
            window.showToast('Password reset successfully!', 'success');
            closeModal('reset-password-modal');
        } else {
            alert(res.message || 'Error resetting password.');
        }
    }, 'json').fail(function(xhr) {
        $('#submit-reset-btn').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error resetting password.');
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
