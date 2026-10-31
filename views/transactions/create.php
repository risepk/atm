<!-- views/transactions/create.php -->
<?php $pageTitle = 'Detailed Transaction Entry'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-6xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Detailed Transaction Entry -->
    <div class="win-window mb-4">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Detailed Transaction Entry &mdash; Narration Studio</span>
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
                <a href="/atm/transactions/rapid<?= !empty($defaultAccountId) ? '?account_id=' . (int)$defaultAccountId : '' ?>" class="win-btn win-btn-sm text-green-900 font-bold">
                    ⚡ Switch to Rapid Entry
                </a>
                <a href="/atm/transactions" class="win-btn win-btn-sm text-[#1a385f]">
                    &larr; Transaction Register
                </a>
            </div>
            <div class="text-[11px] text-[#1e395b] font-medium">
                <span>Shortcuts: <kbd class="px-1 py-0.5 bg-white border border-[#9ab7d3] text-[#1a385f] rounded text-[10px] font-mono">Ctrl+Enter</kbd> Save &bull; <kbd class="px-1 py-0.5 bg-white border border-[#9ab7d3] text-[#1a385f] rounded text-[10px] font-mono">Alt+N</kbd> Save &amp; New</span>
            </div>
        </div>

        <form id="tx-entry-form" class="p-4 bg-[#f2f5fa] space-y-3">
            <!-- 1. Core Financial Details Panel -->
            <div class="bg-[#f8fafd] border border-[#b8cee6] p-3 rounded-xs space-y-3">
                <div class="text-xs font-bold uppercase tracking-wider text-[#102a45] border-b border-[#cbd9e8] pb-1">
                    1. Target Account &amp; Financial Amounts
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <!-- Searchable Account Selector -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Target Account *</label>
                        <select id="account-select" name="account_id" required class="searchable-select win-input w-full bg-white font-medium text-xs">
                            <option value="">-- Select Account --</option>
                            <?php foreach ($accounts as $acc): ?>
                                <?php $ident = !empty($acc['account_identifier']) ? $acc['account_identifier'] : $acc['id']; ?>
                                <option value="<?= $acc['id'] ?>" <?= ($defaultAccountId == $acc['id']) ? 'selected' : '' ?>>
                                    <?= e($ident) ?> &bull; <?= e($acc['account_title']) ?> (Bal: PKR <?= CurrencyHelper::format($acc['current_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Transaction Date -->
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Date *</label>
                        <input type="date" id="tx-date-input" name="transaction_date" value="<?= date('Y-m-d') ?>" required class="win-input w-full text-xs font-mono">
                    </div>

                    <!-- Optional Reference No -->
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Cheque / Ref No</label>
                        <input type="text" id="ref-no-input" name="reference_no" placeholder="Optional Ref #" class="win-input w-full text-xs font-mono">
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
                            <input type="text" inputmode="decimal" id="debit-input" name="debit" placeholder="0.00" class="win-input w-full pl-11 text-sm font-bold font-mono text-red-700 border-red-300">
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
                            <input type="text" inputmode="decimal" id="credit-input" name="credit" placeholder="0.00" class="win-input w-full pl-11 text-sm font-bold font-mono text-green-700 border-green-300">
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
                    <div class="text-[11px] text-gray-500">Pick an active template or enter directly below</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Searchable Template Selection Pre-Populated from Server -->
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Narration Template</label>
                        <select id="template-select" class="searchable-select win-input w-full bg-white text-xs">
                            <option value="">-- Direct Narration (No Template) --</option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?= $tpl['id'] ?>"><?= e($tpl['name']) ?> (<?= e($tpl['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reusable Preset Selector -->
                    <div id="preset-select-container" class="hidden">
                        <div class="flex items-center justify-between mb-0.5">
                            <label class="block text-xs font-bold text-[#1a385f] uppercase">Reusable Counterparty Preset</label>
                            <button type="button" id="open-save-preset-btn" class="text-[11px] font-bold text-blue-700 hover:underline">
                                + Save Current as Preset
                            </button>
                        </div>
                        <select id="preset-select" class="searchable-select win-input w-full bg-[#edf4fc] text-[#0c2340] font-medium text-xs">
                            <option value="">-- Choose Preset or enter new --</option>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Form Container -->
                <div id="template-fields-panel" class="hidden p-3 bg-white rounded-xs border border-[#b8cee6] space-y-2">
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
                        <span id="manual-override-badge" class="hidden px-1.5 py-0.5 text-[10px] font-bold rounded bg-yellow-100 text-yellow-900 border border-yellow-300">
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
                        (Narration will appear here as fields are entered...)
                    </div>
                </div>

                <!-- Editable Final Narration Textarea (Authoritative) -->
                <div>
                    <div class="flex items-center justify-between mb-0.5">
                        <label for="final-narration-textarea" class="block text-xs font-bold text-[#1a385f] uppercase">
                            Final Transaction Narration (Saved to Ledger) *
                        </label>
                        <span class="text-[11px] text-gray-500">Freely edit or append custom text</span>
                    </div>
                    <textarea id="final-narration-textarea" rows="2" required placeholder="Type transaction description or select a template above..." class="win-input w-full font-mono text-xs"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                    <a href="/atm/transactions" class="win-btn">
                        Cancel
                    </a>
                    <button type="button" id="save-new-tx-btn" class="win-btn bg-green-600 text-white font-bold">
                        Save &amp; New (Alt+N)
                    </button>
                    <button type="button" id="save-tx-btn" class="win-btn font-black text-blue-950">
                        Save &amp; Close (Ctrl+Enter)
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<!-- Modal: Save Custom Preset (Desktop Style) -->
<div id="save-preset-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-md w-full shadow-2xl">
        <div class="win-titlebar">
            <span>Save Reusable Counterparty Preset</span>
            <span onclick="closeModal('save-preset-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>
        <div class="p-4 space-y-3 bg-[#f2f5fa]">
            <p class="text-xs text-gray-600">Save current template values as a reusable preset.</p>
            <div class="space-y-2">
                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Preset Name *</label>
                    <input type="text" id="preset-modal-name" placeholder="e.g. Apex Trading, Main Vendor" class="win-input w-full text-xs">
                </div>
                <?php if ($isAdmin): ?>
                    <div class="flex items-center space-x-2 pt-1">
                        <input type="checkbox" id="preset-modal-global" class="h-3.5 w-3.5 text-blue-600 rounded border-gray-400">
                        <label for="preset-modal-global" class="text-xs text-[#1a385f] font-medium">Global Preset (Available to all users)</label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('save-preset-modal')" class="win-btn">Cancel</button>
                <button type="button" id="submit-save-preset-btn" class="win-btn font-black text-blue-950">Save Preset</button>
            </div>
        </div>
    </div>
</div>

<script>
window.ATM_TEMPLATES = <?= json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ATM_CUSTOM_TYPES = <?= json_encode($customTypes ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<script src="/atm/public/assets/js/searchable-select.js"></script>
<script src="/atm/public/assets/js/narration-engine.js"></script>
<script>
if (window.NarrationEngine && NarrationEngine.loadCustomTypes && window.ATM_CUSTOM_TYPES) {
    NarrationEngine.loadCustomTypes(window.ATM_CUSTOM_TYPES);
}
</script>
<script src="/atm/public/assets/js/transaction-form.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
