<!-- 
================================================================================
File: views/transactions/index.php
Browser URL: http://localhost/atm/transactions.php (or /atm/ledger.php)
Description: Transaction Ledger & Financial Journal (ٹرانزیکشن لیجر، سرچ اور ہسٹری)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. اس فائل میں تمام ٹرانزیکشنز کا مکمل کھاتہ (Ledger Journal) ظاہر ہوتا ہے۔
2. یہاں سے تاریخ، اکاؤنٹ یا کی ورڈ لکھ کر ٹرانزیکشنز تلاش (Filter) کی جا سکتی ہیں۔
3. ہر ٹرانزیکشن کے آگے "Edit" کا بٹن اسے /atm/transactions/rapid.php میں لوڈ کرتا ہے۔
4. اوپر "Transaction (Rapid Entry)" کا بٹن /atm/transactions/rapid.php پر لے جاتا ہے۔
================================================================================
-->
<?php 
$pageTitle = 'Transaction Ledger'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Transaction Ledger -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span>Transaction Ledger &mdash; Historical Journal</span>
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
                Financial Journal: Full audit trail with search, date range filtering, editing, and voiding.
            </div>
            <div class="flex items-center space-x-2">
                <a href="/atm/transactions/rapid.php<?= !empty($filters['account_id']) ? '?account_id=' . (int)$filters['account_id'] : '' ?>" class="win-btn font-bold text-emerald-900">
                    ⚡ Transaction (Rapid Entry)
                </a>
                <a href="/atm/statements.php<?= !empty($filters['account_id']) ? '?account_id=' . (int)$filters['account_id'] : '' ?>" class="win-btn text-blue-900">
                    📊 Statement Generator
                </a>
            </div>
        </div>

        <!-- Desktop Filter Bar -->
        <div class="bg-[#f7fafc] border-b border-[#cbd9e8] p-3">
            <form method="GET" action="/atm/transactions.php" class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block font-bold text-[#1a385f] uppercase mb-1">Account</label>
                    <select name="account_id" class="searchable-select win-input w-full bg-white">
                        <option value="">-- All Accounts --</option>
                        <?php foreach ($accounts as $acc): ?>
                            <?php $ident = !empty($acc['account_identifier']) ? $acc['account_identifier'] : $acc['id']; ?>
                            <option value="<?= $acc['id'] ?>" <?= ($filters['account_id'] == $acc['id']) ? 'selected' : '' ?>>
                                <?= e($ident) ?> &bull; <?= e($acc['account_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-[#1a385f] uppercase mb-1">From Date</label>
                    <input type="date" name="start_date" value="<?= e($filters['start_date'] ?? '') ?>" class="win-input w-full">
                </div>

                <div>
                    <label class="block font-bold text-[#1a385f] uppercase mb-1">To Date</label>
                    <input type="date" name="end_date" value="<?= e($filters['end_date'] ?? '') ?>" class="win-input w-full">
                </div>

                <div>
                    <label class="block font-bold text-[#1a385f] uppercase mb-1">Search Narration / Ref</label>
                    <div class="flex space-x-1.5">
                        <input type="text" name="search" placeholder="Keywords..." value="<?= e($filters['search'] ?? '') ?>" class="win-input w-full">
                        <button type="submit" class="win-btn font-bold text-blue-900">Filter</button>
                        <a href="/atm/transactions.php" class="win-btn text-gray-700">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Transactions Grid Table (Desktop Style) -->
        <div class="overflow-x-auto bg-white">
            <table class="w-full win-table border-collapse">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 100px;">Date</th>
                        <th style="width: 160px;">Account</th>
                        <th>Particulars (Narration)</th>
                        <th style="width: 120px;">Ref #</th>
                        <th style="width: 130px; text-align: right;">Debit (Dr)</th>
                        <th style="width: 130px; text-align: right;">Credit (Cr)</th>
                        <th style="width: 120px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="transactions-table-body">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-400 italic">
                                No transactions matching the specified criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr id="tx-row-<?= $tx['id'] ?>" class="cursor-pointer">
                                <td class="font-mono text-gray-600 text-xs font-bold">
                                    #<?= $tx['id'] ?>
                                </td>
                                <td class="font-mono text-xs text-gray-800 whitespace-nowrap">
                                    <?= e($tx['transaction_date']) ?>
                                </td>
                                <td class="font-bold text-gray-900 whitespace-nowrap">
                                    <span class="font-mono text-blue-900 text-xs mr-1">#<?= $tx['account_identifier'] ?? $tx['account_id'] ?></span>
                                    <?= e($tx['account_title']) ?>
                                </td>
                                <td class="text-xs text-gray-800">
                                    <?= e($tx['narration']) ?>
                                </td>
                                <td class="font-mono text-xs text-gray-600 whitespace-nowrap">
                                    <?= e($tx['reference_no'] ?: '-') ?>
                                </td>
                                <td class="font-mono font-bold text-right text-red-700 whitespace-nowrap">
                                    <?= (CurrencyHelper::comp($tx['debit'], '0.00') > 0) ? CurrencyHelper::format($tx['debit']) : '-' ?>
                                </td>
                                <td class="font-mono font-bold text-right text-green-700 whitespace-nowrap">
                                    <?= (CurrencyHelper::comp($tx['credit'], '0.00') > 0) ? CurrencyHelper::format($tx['credit']) : '-' ?>
                                </td>
                                <td class="text-center space-x-1 whitespace-nowrap">
                                    <?php if ($tx['status'] === 'posted'): ?>
                                        <a href="/atm/transactions/rapid.php?account_id=<?= $tx['account_id'] ?>" class="win-btn win-btn-sm text-blue-900" title="Open in TransactionFrm">
                                            Edit
                                        </a>
                                        <button type="button" onclick="voidTransaction(<?= $tx['id'] ?>)" class="win-btn win-btn-sm text-red-700" title="Void transaction">
                                            Void
                                        </button>
                                    <?php else: ?>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-700 border border-gray-300">Voided</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Desktop Pagination Bar -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="px-3 py-2 bg-[#f0f4f9] border-t border-[#cbd9e8] flex items-center justify-between text-xs text-gray-700 select-none">
                <div>
                    Showing page <strong><?= $pagination['page'] ?></strong> of <strong><?= $pagination['total_pages'] ?></strong> (<?= $pagination['total'] ?> total entries)
                </div>
                <div class="space-x-1">
                    <?php if ($pagination['page'] > 1): ?>
                        <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['page'] - 1])) ?>" class="win-btn win-btn-sm">Previous</a>
                    <?php endif; ?>
                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                        <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1])) ?>" class="win-btn win-btn-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="/atm/public/assets/js/searchable-select.js"></script>
<script>
function voidTransaction(id) {
    var reason = prompt('Please enter the reason for voiding Transaction #' + id + ':');
    if (reason === null) return; // User cancelled

    $.post('/atm/api/transactions/' + id + '/void', { reason: reason }, function(res) {
        if (res.success) {
            window.showToast('Transaction #' + id + ' voided successfully.', 'success');
            $('#tx-row-' + id).fadeOut(300, function() { $(this).remove(); });
        } else {
            alert(res.message || 'Error voiding transaction.');
        }
    }, 'json').fail(function(xhr) {
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error voiding transaction.');
    });
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
