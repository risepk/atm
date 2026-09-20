<!-- views/transactions/edit.php -->
<?php $pageTitle = 'Edit Transaction #' . $transaction['id']; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-6xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Transaction Editor -->
    <div class="win-window mb-4">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit Transaction #<?= $transaction['id'] ?></span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <!-- Desktop Ribbon Sub-Bar -->
        <div class="ribbon-bar px-3 py-1.5 flex flex-wrap items-center justify-between gap-2 border-b border-[#a3bedc]">
            <div class="flex items-center space-x-2">
                <a href="/atm/transactions" class="win-btn win-btn-sm text-[#1a385f]">
                    &larr; Transaction Register
                </a>
            </div>
            <div class="text-[11px] text-[#1e395b] font-medium">
                <span>Shortcut: <kbd class="px-1 py-0.5 bg-white border border-[#9ab7d3] text-[#1a385f] rounded text-[10px] font-mono">Ctrl+Enter</kbd> = Update Transaction</span>
            </div>
        </div>

        <form id="tx-edit-form" class="p-4 bg-[#f2f5fa] space-y-3">
            <input type="hidden" id="tx-id" value="<?= $transaction['id'] ?>">

            <!-- 1. Core Financial Details Panel -->
            <div class="bg-[#f8fafd] border border-[#b8cee6] p-3 rounded-xs space-y-3">
                <div class="text-xs font-bold uppercase tracking-wider text-[#102a45] border-b border-[#cbd9e8] pb-1">
                    1. Account &amp; Financial Amounts
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <!-- Searchable Account Selector -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Target Account *</label>
                        <select id="account-select" name="account_id" required class="searchable-select win-input w-full bg-white font-medium text-xs">
                            <?php foreach ($accounts as $acc): ?>
                                <?php $ident = !empty($acc['account_identifier']) ? $acc['account_identifier'] : $acc['id']; ?>
                                <option value="<?= $acc['id'] ?>" <?= ($transaction['account_id'] == $acc['id']) ? 'selected' : '' ?>>
                                    <?= e($ident) ?> &bull; <?= e($acc['account_title']) ?> (Bal: PKR <?= CurrencyHelper::format($acc['current_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Transaction Date -->
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Date *</label>
                        <input type="date" id="tx-date-input" name="transaction_date" value="<?= e($transaction['transaction_date']) ?>" required class="win-input w-full text-xs font-mono">
                    </div>

                    <!-- Reference No -->
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Cheque / Ref No</label>
                        <input type="text" id="ref-no-input" name="reference_no" value="<?= e($transaction['reference_no'] ?? '') ?>" placeholder="Optional Ref #" class="win-input w-full text-xs font-mono">
                    </div>
                </div>

                <!-- Debit vs Credit Dual Box -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <!-- Debit Box -->
                    <div class="p-2.5 bg-red-50 rounded-xs border border-red-200">
                        <div class="flex items-center justify-between mb-1">
                            <label for="debit-input" class="text-xs font-bold text-red-900 uppercase tracking-wide">Debit Amount (Money Out)</label>
                            <span class="text-[10px] text-red-700 font-semibold uppercase">Payment / Expense</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-red-500 font-bold text-xs">PKR</div>
                            <input type="text" inputmode="decimal" id="debit-input" name="debit" value="<?= (CurrencyHelper::comp($transaction['debit'], '0.00') > 0) ? CurrencyHelper::normalizeDecimal($transaction['debit']) : '' ?>" placeholder="0.00" class="win-input w-full pl-11 text-sm font-bold font-mono text-red-700 border-red-300">
                        </div>
                    </div>

                    <!-- Credit Box -->
                    <div class="p-2.5 bg-green-50 rounded-xs border border-green-200">
                        <div class="flex items-center justify-between mb-1">
                            <label for="credit-input" class="text-xs font-bold text-green-900 uppercase tracking-wide">Credit Amount (Money In)</label>
                            <span class="text-[10px] text-green-700 font-semibold uppercase">Receipt / Deposit</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-green-500 font-bold text-xs">PKR</div>
                            <input type="text" inputmode="decimal" id="credit-input" name="credit" value="<?= (CurrencyHelper::comp($transaction['credit'], '0.00') > 0) ? CurrencyHelper::normalizeDecimal($transaction['credit']) : '' ?>" placeholder="0.00" class="win-input w-full pl-11 text-sm font-bold font-mono text-green-700 border-green-300">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Narration Template & Engine Panel -->
            <div class="bg-[#f8fafd] border border-[#b8cee6] p-3 rounded-xs space-y-3">
                <div class="flex items-center justify-between border-b border-[#cbd9e8] pb-1">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#102a45]">
                        2. Narration Engine &amp; Dynamic Fields
                    </div>
                    <div class="text-[11px] text-gray-500">Pick a template or enter directly below</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Searchable Template Selection -->
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Narration Template</label>
                        <select id="template-select" data-initial="<?= $transaction['template_id'] ?>" class="searchable-select win-input w-full bg-white text-xs">
                            <option value="">-- Direct Narration (No Template) --</option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?= $tpl['id'] ?>" <?= ($transaction['template_id'] == $tpl['id']) ? 'selected' : '' ?>>
                                    <?= e($tpl['name']) ?> (<?= e($tpl['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reusable Preset Selector -->
                    <div id="preset-select-container" class="<?= empty($transaction['template_id']) ? 'hidden' : '' ?>">
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Reusable Counterparty Preset</label>
                        <select id="preset-select" class="searchable-select win-input w-full bg-[#edf4fc] text-[#0c2340] font-medium text-xs">
                            <option value="">-- Choose Preset or enter new --</option>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Form Container -->
                <div id="template-fields-panel" class="<?= empty($transaction['template_id']) ? 'hidden' : '' ?> p-3 bg-white rounded-xs border border-[#b8cee6] space-y-2">
                    <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">Dynamic Fields:</div>
                    <div id="dynamic-fields-container"></div>
                </div>
            </div>

            <!-- 3. Narration Output & Authoritative Edit Panel -->
            <div class="bg-[#f8fafd] border border-[#b8cee6] p-3 rounded-xs space-y-3">
                <div class="flex items-center justify-between border-b border-[#cbd9e8] pb-1">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#102a45]">
                        3. Live Narration &amp; Final Saved Text
                    </div>
                    <div class="flex items-center space-x-2">
                        <span id="manual-override-badge" class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-yellow-100 text-yellow-900 border border-yellow-300">
                            Custom Edits Applied
                        </span>
                        <button type="button" id="resync-narration-btn" class="win-btn win-btn-sm text-blue-900">
                            Re-sync from Fields
                        </button>
                    </div>
                </div>

                <!-- Live Compiled Preview -->
                <div class="p-2.5 bg-[#eaf2fb] rounded-xs border border-[#b8cee6]">
                    <div class="text-[10px] uppercase font-bold text-[#1c4573] tracking-wider">Live Pattern Compilation Preview:</div>
                    <div id="narration-preview-text" class="text-xs text-[#0a2540] font-mono font-medium mt-0.5 select-all break-words">
                        <?= e($transaction['narration']) ?>
                    </div>
                </div>

                <!-- Editable Final Narration Textarea (Authoritative) -->
                <div>
                    <div class="flex items-center justify-between mb-0.5">
                        <label for="final-narration-textarea" class="block text-xs font-bold text-[#1a385f] uppercase">
                            Final Transaction Narration (Saved to Ledger) *
                        </label>
                        <span class="text-[11px] text-gray-500">Authoritative narration string for historical records</span>
                    </div>
                    <textarea id="final-narration-textarea" rows="2" required class="win-input w-full font-mono text-xs"><?= e($transaction['narration']) ?></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                    <a href="/atm/transactions" class="win-btn">
                        Cancel
                    </a>
                    <button type="submit" id="update-tx-btn" class="win-btn font-black text-blue-950">
                        Update Transaction (Ctrl+Enter)
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
window.ATM_TEMPLATES = <?= json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.INITIAL_PAYLOAD = <?= !empty($transaction['template_payload']) ? $transaction['template_payload'] : '{}' ?>;
window.IS_EDIT_MODE = true;
</script>
<script src="/atm/public/assets/js/searchable-select.js"></script>
<script src="/atm/public/assets/js/narration-engine.js"></script>
<script src="/atm/public/assets/js/transaction-form.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

