<!-- views/accounts/index.php -->
<?php 
$pageTitle = 'Account Detail & Management'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Account Detail -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Account Detail &mdash; Financial Portfolio</span>
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
                Client Accounts: Give each account a short code / number (e.g. 12, 13, 14, 57, 91, or Name).
            </div>
            <div class="flex items-center space-x-2">
                <?php if ($isAdmin): ?>
                    <button type="button" onclick="openAddBankModal()" class="win-btn win-btn-sm text-purple-900">
                        + Add Bank
                    </button>
                <?php endif; ?>
                <button type="button" onclick="openNewAccountModal()" class="win-btn font-bold text-blue-900">
                    + Add New Account
                </button>
            </div>
        </div>

        <!-- Accounts Grid Table (Desktop Style) -->
        <div class="overflow-x-auto bg-white">
            <table class="w-full win-table border-collapse">
                <thead>
                    <tr>
                        <th style="width: 120px;">Acc No / Code</th>
                        <th>Account Name</th>
                        <th style="width: 220px;">Bank Name</th>
                        <th style="width: 140px; text-align: right;">Opening Balance</th>
                        <th style="width: 140px; text-align: right;">Current Balance</th>
                        <th style="width: 80px; text-align: center;">Status</th>
                        <th style="width: 190px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="accounts-table-body">
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-400 italic">
                                No accounts configured yet. Click "+ Add New Account" above to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): ?>
                            <?php 
                                $ident = !empty($acc['account_identifier']) ? $acc['account_identifier'] : $acc['id'];
                            ?>
                            <tr id="account-row-<?= $acc['id'] ?>" class="cursor-pointer">
                                <td class="font-mono font-bold text-blue-900">
                                    <span class="px-2 py-0.5 rounded bg-blue-100 border border-blue-300"><?= e($ident) ?></span>
                                </td>
                                <td class="font-bold text-gray-900">
                                    <?= e($acc['account_title']) ?>
                                </td>
                                <td class="text-gray-800 font-medium">
                                    <?= e($acc['bank_name'] ?: 'Not Assigned') ?>
                                </td>
                                <td class="font-mono text-right text-gray-700">
                                    <?= CurrencyHelper::format($acc['initial_balance']) ?>
                                </td>
                                <td class="font-mono font-bold text-right <?= (CurrencyHelper::comp($acc['current_balance'], '0.00') >= 0) ? 'text-gray-900' : 'text-red-600' ?>">
                                    <?= CurrencyHelper::format($acc['current_balance']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold <?= $acc['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= e($acc['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center space-x-1">
                                    <a href="/atm/transactions/rapid?account_id=<?= $acc['id'] ?>" class="win-btn win-btn-sm text-emerald-900 font-bold" title="Open TransactionFrm for this account">
                                        Entry
                                    </a>
                                    <a href="/atm/statements?account_id=<?= $acc['id'] ?>" class="win-btn win-btn-sm text-blue-900 font-bold" title="Generate Bank Statement">
                                        Report
                                    </a>
                                    <button type="button" onclick="openEditAccountModal(<?= htmlspecialchars(json_encode($acc), ENT_QUOTES, 'UTF-8') ?>)" class="win-btn win-btn-sm text-gray-800">
                                        Edit
                                    </button>
                                    <button type="button" onclick="deleteAccount(<?= $acc['id'] ?>)" class="win-btn win-btn-sm text-red-700">
                                        Del
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Simplified Account Modal (Matching Client Workflow: Code, Bank & Initial Balance Only) -->
<div id="account-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-sm w-full shadow-2xl">
        <div class="win-titlebar">
            <span id="account-modal-title">New Account Setup</span>
            <span onclick="closeModal('account-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>

        <form id="account-form" class="p-4 space-y-3 bg-[#f2f5fa]">
            <input type="hidden" id="account-id" name="id" value="">
            <input type="hidden" id="account-title-hidden" name="account_title" value="">

            <!-- Field 1: Account Number / Code -->
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">
                    Account Number / Code *
                </label>
                <input type="text" id="account-identifier" name="account_identifier" required placeholder="e.g. 12, 13, 14, 57, 91, or Name" class="win-input w-full font-mono font-bold text-sm text-blue-950">
                <span class="text-[10px] text-gray-500 block mt-0.5">
                    Enter any number (e.g. 12, 57) or name.
                </span>
            </div>

            <!-- Field 2: Bank Name -->
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">
                    Bank Name *
                </label>
                <select id="account-bank" name="bank_name" required class="searchable-select win-input w-full bg-white">
                    <option value="">-- Choose Bank --</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= e($b['bank_name']) ?>"><?= e($b['bank_name']) ?> (<?= e($b['bank_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Field 3: Opening Balance -->
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">
                    Opening Balance (PKR)
                </label>
                <input type="text" inputmode="decimal" id="account-initial-balance" name="initial_balance" value="0.00" class="win-input w-full font-mono font-bold">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('account-modal')" class="win-btn">
                    Cancel
                </button>
                <button type="submit" id="save-account-btn" class="win-btn font-black text-blue-900">
                    Save Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Add Bank Modal -->
<div id="add-bank-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-sm w-full shadow-2xl">
        <div class="win-titlebar">
            <span>Add New Bank</span>
            <span onclick="closeModal('add-bank-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>

        <form id="add-bank-form" class="p-4 space-y-3 bg-[#f2f5fa]">
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Bank Name *</label>
                <input type="text" id="new-bank-name" required placeholder="e.g. Bank of Punjab" class="win-input w-full text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Bank Code (Optional)</label>
                <input type="text" id="new-bank-code" placeholder="e.g. BOP" class="win-input w-full text-xs font-mono uppercase">
            </div>
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('add-bank-modal')" class="win-btn">Cancel</button>
                <button type="submit" id="save-bank-btn" class="win-btn font-bold text-purple-900">Add Bank</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="/atm/public/assets/js/searchable-select.js"></script>
<script>
function openNewAccountModal() {
    $('#account-modal-title').text('New Account Setup');
    $('#account-id').val('');
    $('#account-identifier').val('');
    $('#account-bank').val('');
    $('#account-initial-balance').val('0.00').prop('disabled', false);
    if (window.refreshSearchableSelect) {
        window.refreshSearchableSelect($('#account-bank')[0]);
    }
    openModal('account-modal');
    setTimeout(function() { $('#account-identifier').focus(); }, 100);
}

function openEditAccountModal(acc) {
    $('#account-modal-title').text('Edit Account #' + (acc.account_identifier || acc.id));
    $('#account-id').val(acc.id);
    $('#account-identifier').val(acc.account_identifier || acc.id);
    $('#account-title-hidden').val(acc.account_title || acc.account_identifier || acc.id);
    $('#account-bank').val(acc.bank_name || '');

    var hasPosted = parseInt(acc.posted_tx_count || 0) > 0;
    if (hasPosted) {
        $('#account-initial-balance').val(acc.initial_balance).prop('disabled', true);
    } else {
        $('#account-initial-balance').val(acc.initial_balance).prop('disabled', false);
    }

    if (window.refreshSearchableSelect) {
        window.refreshSearchableSelect($('#account-bank')[0]);
    }
}

function openAddBankModal() {
    $('#new-bank-name').val('');
    $('#new-bank-code').val('');
    openModal('add-bank-modal');
}

$('#account-form').on('submit', function(e) {
    e.preventDefault();
    var ident = $('#account-identifier').val().trim();
    if (!ident) {
        alert('Account Number / Code is required.');
        $('#account-identifier').focus();
        return;
    }
    $('#account-title-hidden').val(ident);

    var formData = $(this).serialize();
    $('#save-account-btn').prop('disabled', true);

    $.post('/atm/api/accounts/save', formData, function(res) {
        $('#save-account-btn').prop('disabled', false);
        if (res.success) {
            closeModal('account-modal');
            window.location.reload();
        } else {
            alert(res.message || 'Error saving account.');
        }
    }, 'json').fail(function(xhr) {
        $('#save-account-btn').prop('disabled', false);
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error saving account.';
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            var errs = Object.values(xhr.responseJSON.errors).join('\n');
            msg += '\n' + errs;
        }
        alert(msg);
    });
});

$('#add-bank-form').on('submit', function(e) {
    e.preventDefault();
    var name = $('#new-bank-name').val().trim();
    var code = $('#new-bank-code').val().trim();

    $('#save-bank-btn').prop('disabled', true);
    $.post('/atm/api/master/banks/add', { bank_name: name, bank_code: code }, function(res) {
        $('#save-bank-btn').prop('disabled', false);
        if (res.success && res.data) {
            closeModal('add-bank-modal');
            var $opt = $('<option>')
                .attr('value', res.data.bank_name)
                .text(res.data.bank_name + ' (' + res.data.bank_code + ')')
                .prop('selected', true);
            $('#account-bank').append($opt);
            if (window.refreshSearchableSelect) {
                window.refreshSearchableSelect($('#account-bank')[0]);
            }
        } else {
            alert(res.message || 'Error adding bank.');
        }
    }, 'json').fail(function(xhr) {
        $('#save-bank-btn').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error adding bank.');
    });
});

function deleteAccount(id) {
    if (!confirm('Are you sure you want to delete Account #' + id + '?')) return;
    $.post('/atm/api/accounts/' + id + '/delete', function(res) {
        if (res.success) {
            $('#account-row-' + id).fadeOut(200, function() { $(this).remove(); });
        } else {
            alert(res.message || 'Error deleting account.');
        }
    }, 'json').fail(function(xhr) {
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error deleting account.');
    });
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
