<!-- 
================================================================================
File: views/transactions/rapid.php
Browser URL: http://localhost/atm/transactions/rapid.php (or /atm/rapid.php)
Description: TransactionFrm — Rapid Transaction Entry Window (ٹرانزیکشن اندراج فارم)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. یہ فائل روزانہ ٹرانزیکشنز درج کرنے والی مین ونڈو (TransactionFrm) ہے۔
2. 'Particulars' فیلڈ کو مکمل چوڑائی دی گئی ہے تاکہ لمبی تفصیل (Narration) آسانی سے لکھی جا سکے۔
3. Particulars فیلڈ کے نیچے چھوٹے بٹنز (IBFT, ATM Cash Withdrawal, Raast وغیرہ) موجود ہیں
   جن پر کلک کرنے سے آٹو ٹیکسٹ اور ویری ایبلز درج ہو جاتے ہیں۔
4. اگر آپ نے ان پٹ فیلڈز (Date, Voucher No, Dr/Cr, Balance) یا نیچے حالیہ ٹرانزیکشنز کے ٹیبل
   کا لے آؤٹ تبدیل کرنا ہو تو اسی فائل کے اندر متعلقہ حصوں کو ایڈٹ کر سکتے ہیں۔
================================================================================
-->
<?php 
$pageTitle = 'TransactionFrm — Data Entry'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 

$activeAccount = null;
foreach ($accounts as $acc) {
    if ((int)$acc['id'] === (int)$defaultAccountId) {
        $activeAccount = $acc;
        break;
    }
}
if (!$activeAccount && !empty($accounts)) {
    $activeAccount = $accounts[0];
}

// Unique banks from master banks or accounts
$uniqueBanks = array_values(array_unique(array_filter(array_column($accounts, 'bank_name'))));
sort($uniqueBanks);
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-3">
    <!-- Desktop Window Frame matching Screenshot 1 & 2 (TransactionFrm) -->
    <div class="win-window mb-4">
        <!-- Titlebar -->
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>TransactionFrm</span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <!-- Form Body (Screenshots 1 & 2 Exact Layout) -->
        <div class="p-3 sm:p-4 bg-[#eceff4] text-[#111827] text-xs space-y-3">
            <input type="hidden" id="editing-tx-id" value="">
            <input type="hidden" id="selected-account-id" value="<?= $activeAccount ? $activeAccount['id'] : '' ?>">

            <!-- Toast / Status Message -->
            <div id="status-toast" class="hidden px-3 py-1.5 rounded border text-xs font-bold flex items-center justify-between">
                <span id="status-toast-msg"></span>
                <button type="button" onclick="$('#status-toast').addClass('hidden')" class="ml-2 font-bold">&times;</button>
            </div>

            <!-- Row 1: Bank Name, Account Number, [ Check ], [ Update Bank ] -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                <div class="md:col-span-4 flex items-center space-x-2">
                    <label for="frm-bank-name" class="font-bold text-[#1a385f] whitespace-nowrap min-w-[75px]">Bank Name</label>
                    <select id="frm-bank-name" class="win-input w-full bg-white font-medium text-xs">
                        <option value="">-- Select Bank --</option>
                        <?php foreach ($uniqueBanks as $bName): ?>
                            <option value="<?= e($bName) ?>" <?= ($activeAccount && $activeAccount['bank_name'] === $bName) ? 'selected' : '' ?>>
                                <?= e($bName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-5 flex items-center space-x-2">
                    <label for="frm-account-number" class="font-bold text-[#1a385f] whitespace-nowrap min-w-[95px]">Account Number</label>
                    <input type="text" id="frm-account-number" list="accounts-datalist" placeholder="Enter Acc # or Code (e.g. 57, 12, 13)" value="<?= $activeAccount ? e($activeAccount['account_identifier'] ?? $activeAccount['id']) : '' ?>" class="win-input w-full font-mono font-bold text-blue-950 text-xs">
                    <datalist id="accounts-datalist">
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= e($acc['account_identifier'] ?? $acc['id']) ?>"><?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?>)</option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="md:col-span-3 flex items-center space-x-2">
                    <button type="button" id="btn-check-account" class="win-btn font-bold text-blue-950 px-4">
                        Check
                    </button>
                    <button type="button" id="btn-update-bank" class="win-btn text-[#1c3a63] px-3">
                        Update Bank
                    </button>
                    <!-- Live balance indicator -->
                    <span class="text-[11px] font-mono font-bold text-blue-900 ml-auto whitespace-nowrap" id="live-balance-tag">
                        Bal: PKR <span id="balance-val"><?= $activeAccount ? CurrencyHelper::format($activeAccount['current_balance']) : '0.00' ?></span>
                    </span>
                </div>
            </div>

            <!-- Row 2: Entry Date, Dr, Cr, Reference No -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                <!-- Entry Date -->
                <div class="md:col-span-3 flex items-center space-x-1.5">
                    <label for="frm-entry-date" class="font-bold text-[#1a385f] whitespace-nowrap min-w-[70px]">Entry Date :</label>
                    <input type="date" id="frm-entry-date" value="<?= date('Y-m-d') ?>" class="win-input w-full font-semibold text-xs font-mono">
                </div>

                <!-- Dr (Debit) -->
                <div class="md:col-span-2 flex items-center space-x-1.5">
                    <label for="frm-dr" class="font-bold text-red-700 min-w-[20px]">Dr</label>
                    <input type="text" inputmode="decimal" id="frm-dr" value="0" class="win-input w-full font-mono font-bold text-red-600 text-xs">
                </div>

                <!-- Cr (Credit) -->
                <div class="md:col-span-2 flex items-center space-x-1.5">
                    <label for="frm-cr" class="font-bold text-green-700 min-w-[20px]">Cr</label>
                    <input type="text" inputmode="decimal" id="frm-cr" value="0" class="win-input w-full font-mono font-bold text-green-600 text-xs">
                </div>

                <!-- Reference No -->
                <div class="md:col-span-5 flex items-center space-x-1.5">
                    <label for="frm-ref-no" class="font-bold text-[#1a385f] whitespace-nowrap min-w-[85px]">Reference No</label>
                    <input type="text" id="frm-ref-no" placeholder="Ref / Cheque #" class="win-input w-full font-mono text-xs">
                </div>
            </div>

            <!-- Row 3: Particulars (Full Row for Maximum Narration Space) with Combobox, [✕] Clear, and [↻] Regenerator -->
            <div class="flex items-center space-x-2 relative w-full">
                <label for="frm-particulars" class="font-bold text-[#1a385f] whitespace-nowrap min-w-[70px]">Particulars :</label>
                <div class="relative flex-1 flex items-center">
                    <input type="text" id="frm-particulars" placeholder="Type or pick narration (e.g. Cash Deposit, Transfer...)" autocomplete="off" class="win-input w-full pr-12 text-xs font-medium text-gray-900">
                    
                    <!-- Clear cross [✕] button (hidden when input is empty) -->
                    <button type="button" id="btn-clear-particulars" class="hidden absolute right-6 text-gray-400 hover:text-red-600 font-bold text-xs p-0.5 leading-none transition-colors" title="Clear text (Esc)">
                        ✕
                    </button>
                    <!-- Toggle dropdown chevron -->
                    <button type="button" id="btn-toggle-particulars-dd" class="absolute right-1.5 text-gray-400 hover:text-gray-700 p-0.5 text-[10px]" title="Show past narrations & templates">
                        ▼
                    </button>

                    <!-- Autocomplete & Past Narrations Dropdown (Now Spanning Full Row Width) -->
                    <div id="particulars-dropdown" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-[#7ea3cc] rounded shadow-xl z-50 max-h-64 overflow-y-auto divide-y divide-gray-100 text-xs">
                        <!-- Populated via AJAX / Past Narrations -->
                    </div>
                </div>

                <!-- Single [↻] Regenerator button placed outside the input field -->
                <button type="button" id="btn-regen-tokens" class="win-btn px-3 py-1 text-blue-800 hover:text-blue-950 font-black text-xs flex items-center justify-center shrink-0 border border-[#7ea3cc] bg-[#f0f4fa] hover:bg-[#d9e6f5]" title="Regenerate variables (STAN, Reference, ATM ID) in-place (F2)">
                    ↻
                </button>
            </div>

            <!-- Quick Template Action Buttons (Direct One-Click Narration & In-Place Regeneration) -->
            <div id="quick-templates-wrapper" class="flex flex-wrap items-center gap-1.5 pt-0.5">
                <span class="text-[11px] font-bold text-[#1a385f] whitespace-nowrap min-w-[70px]">Templates :</span>
                <div id="quick-template-chips" class="flex flex-wrap items-center gap-1.5 flex-1">
                    <!-- Populated dynamically via JS based on Dr / Cr direction -->
                </div>
            </div>

            <!-- Row 4: Action Buttons [ Save ] [ Update ] [ Refresh ] & Keyboard Guide -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-[#cbd9e8]">
                <div class="text-[11px] text-[#1e395b] font-medium flex flex-wrap items-center gap-1.5">
                    <span class="font-bold text-blue-900">⌨ Keyboard:</span>
                    <span><kbd class="px-1.5 py-0.5 bg-white border border-[#9ab7d3] rounded text-[10px] font-mono font-bold text-blue-950">Enter</kbd> Next Field / Save</span>
                    <span><kbd class="px-1.5 py-0.5 bg-white border border-[#9ab7d3] rounded text-[10px] font-mono font-bold text-blue-950">F2</kbd> / <kbd class="px-1.5 py-0.5 bg-white border border-[#9ab7d3] rounded text-[10px] font-mono font-bold text-blue-950">↻</kbd> Regen STAN</span>
                    <span><kbd class="px-1.5 py-0.5 bg-white border border-[#9ab7d3] rounded text-[10px] font-mono font-bold text-blue-950">Ctrl+Enter</kbd> Save</span>
                    <span><kbd class="px-1.5 py-0.5 bg-white border border-[#9ab7d3] rounded text-[10px] font-mono font-bold text-blue-950">↑ / ↓</kbd> Suggestion</span>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" id="btn-save" class="win-btn font-black text-blue-950 px-6 py-1 text-xs">
                        <u>S</u>ave (Enter)
                    </button>
                    <button type="button" id="btn-update" disabled class="win-btn font-bold text-amber-900 px-6 py-1 text-xs opacity-50 cursor-not-allowed">
                        <u>U</u>pdate
                    </button>
                    <button type="button" id="btn-refresh" class="win-btn text-gray-800 px-6 py-1 text-xs">
                        <u>R</u>efresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Row 4: Recent Transactions Table (Screenshot 1 & 2 Exact Columns) -->
        <div class="overflow-x-auto bg-white border-t border-[#8caecf]">
            <table class="w-full win-table border-collapse" id="main-tx-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 130px;">Date</th>
                        <th style="width: 140px;">Bank Name</th>
                        <th>Particulars</th>
                        <th style="width: 110px; text-align: right;">Debit</th>
                        <th style="width: 110px; text-align: right;">Credit</th>
                        <th style="width: 120px; text-align: center;">Transaction Details</th>
                        <th style="width: 50px; text-align: center;">Edit</th>
                        <th style="width: 50px; text-align: center;">Delete</th>
                    </tr>
                </thead>
                <tbody id="tx-table-body">
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400 italic">
                            Loading transactions...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="/atm/public/assets/js/narration-engine.js"></script>

<script>
$(document).ready(function() {
    var csrfToken = '<?= CSRF::getToken() ?>';
    var accountsList = <?= json_encode(array_values($accounts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var accountsByIdentifier = {};
    var accountsById = {};

    accountsList.forEach(function(a) {
        var ident = String(a.account_identifier || a.id).trim().toLowerCase();
        accountsByIdentifier[ident] = a;
        accountsById[a.id] = a;
    });

    var currentAccount = <?= $activeAccount ? json_encode($activeAccount) : 'null' ?>;
    var masterBanksList = <?= isset($masterBanks) ? json_encode(array_values($masterBanks)) : '[]' ?>;
    if (window.NarrationEngine && NarrationEngine.loadMasterBanks) {
        NarrationEngine.loadMasterBanks(masterBanksList);
    }
    var customTypesList = <?= isset($customTypes) ? json_encode(array_values($customTypes)) : '[]' ?>;
    if (window.NarrationEngine && NarrationEngine.loadCustomTypes) {
        NarrationEngine.loadCustomTypes(customTypesList);
    }
    <?php
    $cleanTemplates = [];
    if (!empty($templates)) {
        foreach ($templates as $t) {
            if (!empty($t['is_active']) && strpos($t['code'], 'VENDOR_TEST_') !== 0) {
                $cleanTemplates[] = [
                    'id' => (int)$t['id'],
                    'code' => $t['code'],
                    'name' => $t['name'],
                    'pattern' => $t['pattern'],
                    'version' => (int)($t['version'] ?? 1),
                    'category' => $t['category'] ?? 'general',
                    'bank_id' => !empty($t['bank_id']) ? (int)$t['bank_id'] : null,
                    'bank_name' => $t['bank_name'] ?? null,
                    'fields' => $t['fields'] ?? []
                ];
            }
        }
    }
    ?>
    var availableTemplates = <?= json_encode(array_values($cleanTemplates), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var cachedTransactions = [];
    var currentEditingTx = null;

    function formatNumber(num) {
        if (num === null || num === undefined || num === '') return '0.0000';
        var n = parseFloat(String(num).replace(/,/g, ''));
        if (isNaN(n)) return '0.0000';
        return n.toFixed(4);
    }

    function showToast(msg, type) {
        var $toast = $('#status-toast');
        var $msg = $('#status-toast-msg');
        $toast.removeClass('hidden bg-green-100 text-green-900 border-green-300 bg-red-100 text-red-900 border-red-300');
        if (type === 'success') {
            $toast.addClass('bg-green-100 text-green-900 border-green-300');
        } else {
            $toast.addClass('bg-red-100 text-red-900 border-red-300');
        }
        $msg.text(msg);
        setTimeout(function() { $toast.fadeOut(300, function() { $(this).addClass('hidden').show(); }); }, 3500);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Resolve Account by identifier or ID
    function resolveAccount() {
        var query = $.trim($('#frm-account-number').val()).toLowerCase();
        if (!query) return null;
        if (accountsByIdentifier[query]) return accountsByIdentifier[query];
        // match title
        var match = accountsList.find(function(a) {
            return String(a.account_title).toLowerCase() === query || String(a.account_number).toLowerCase() === query;
        });
        return match || null;
    }

    // [ Check ] button: load account & transactions
    $('#btn-check-account').on('click', function() {
        var acc = resolveAccount();
        if (!acc) {
            var inputVal = $.trim($('#frm-account-number').val());
            showToast('Account "' + inputVal + '" not found. Check number or create in Account Detail.', 'error');
            return;
        }

        currentAccount = acc;
        $('#selected-account-id').val(acc.id);
        if (acc.bank_name) {
            $('#frm-bank-name').val(acc.bank_name);
        }
        $('#balance-val').text(parseFloat(acc.current_balance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        loadTransactions(acc.id);
        renderQuickTemplateChips();
        showToast('Loaded account: ' + acc.account_title + ' (#' + (acc.account_identifier || acc.id) + ')', 'success');
        $('#frm-dr').focus().select();
    });

    // Re-filter quick chips when Bank Name is selected/changed
    $('#frm-bank-name').on('change', function() {
        renderQuickTemplateChips();
    });

    // Enter key in Account Number triggers Check
    $('#frm-account-number').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btn-check-account').click();
        }
    });

    // [ Update Bank ] button: assign bank to this account
    $('#btn-update-bank').on('click', function() {
        var acc = currentAccount || resolveAccount();
        if (!acc) {
            showToast('Please check an account first before updating bank.', 'error');
            return;
        }
        var newBank = $('#frm-bank-name').val();
        if (!newBank) {
            showToast('Please select a bank from Bank Name dropdown.', 'error');
            return;
        }

        if (!confirm('Update Bank for Account "' + (acc.account_identifier || acc.account_title) + '" to "' + newBank + '"?')) {
            return;
        }

        $.post('/atm/api/accounts/save', {
            id: acc.id,
            account_title: acc.account_title,
            account_identifier: acc.account_identifier,
            bank_name: newBank
        }, function(res) {
            if (res.success) {
                showToast('Bank updated to ' + newBank, 'success');
                acc.bank_name = newBank;
                loadTransactions(acc.id);
            } else {
                showToast(res.message || 'Error updating bank.', 'error');
            }
        }, 'json');
    });

    // Dr / Cr exclusive entry & quick template filter
    $('#frm-dr').on('input', function() {
        var val = $(this).val();
        if (val !== '0' && val !== '' && parseFloat(val) > 0) {
            $('#frm-cr').val('0');
        }
        renderQuickTemplateChips();
    });

    $('#frm-cr').on('input', function() {
        var val = $(this).val();
        if (val !== '0' && val !== '' && parseFloat(val) > 0) {
            $('#frm-dr').val('0');
        }
        renderQuickTemplateChips();
    });

    // Auto select text on focus for rapid typing
    $('#frm-dr, #frm-cr, #frm-ref-no, #frm-account-number').on('focus', function() {
        $(this).select();
    });

    // Date: Enter moves to Dr
    $('#frm-entry-date').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#frm-dr').focus().select();
        }
    });

    // Dr: Enter
    $('#frm-dr').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var val = parseFloat($.trim($(this).val())) || 0;
            if (val > 0) {
                var p = $.trim($('#frm-particulars').val());
                if (p) {
                    saveOrUpdateEntry(!!$('#editing-tx-id').val());
                } else {
                    $('#frm-particulars').focus();
                }
            } else {
                $('#frm-cr').focus().select();
            }
        }
    });

    // Cr: Enter & Tab navigation
    $('#frm-cr').on('keydown', function(e) {
        if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            $('#frm-particulars').focus();
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            var val = parseFloat($.trim($(this).val())) || 0;
            if (val > 0) {
                var p = $.trim($('#frm-particulars').val());
                if (p) {
                    saveOrUpdateEntry(!!$('#editing-tx-id').val());
                } else {
                    $('#frm-particulars').focus();
                }
            } else {
                $('#frm-particulars').focus();
            }
        }
    });

    // Reference No: Enter saves entry & Shift+Tab goes to Particulars
    $('#frm-ref-no').on('keydown', function(e) {
        if (e.key === 'Tab' && e.shiftKey) {
            e.preventDefault();
            $('#frm-particulars').focus();
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            saveOrUpdateEntry(!!$('#editing-tx-id').val());
        }
    });

    // Particulars Suggestions & Past History
    var suggestTimer = null;
    var lastSelectedTemplate = null;
    var lastSelectedPayload = {};
    var activeDropdownIndex = -1;
    var suppressSuggestOnFocus = false;

    function toggleClearParticularsBtn() {
        var val = $('#frm-particulars').val();
        if (val && val.length > 0) {
            $('#btn-clear-particulars').removeClass('hidden');
        } else {
            $('#btn-clear-particulars').addClass('hidden');
        }
    }

    // Direction analyzer for templates based on standard banking keywords
    function getTemplateDirection(tpl) {
        var text = ((tpl.name || '') + ' ' + (tpl.code || '') + ' ' + (tpl.category || '') + ' ' + (tpl.pattern || '')).toLowerCase();
        var debitKeywords = ['outward', 'outflow', 'debit', 'withdrawal', 'withdraw', 'payment', 'bill', 'charge', 'fee', 'tax', 'purchase', 'pos', 'atm'];
        var creditKeywords = ['incoming', 'inward', 'inflow', 'credit', 'deposit', 'receipt', 'receive', 'refund', 'salary', 'dividend', 'interest', 'collection'];

        var debitScore = 0;
        var creditScore = 0;
        debitKeywords.forEach(function(k) { if (text.indexOf(k) !== -1) debitScore++; });
        creditKeywords.forEach(function(k) { if (text.indexOf(k) !== -1) creditScore++; });

        if (debitScore > creditScore) return 'debit';
        if (creditScore > debitScore) return 'credit';
        return 'both';
    }

    // Render Quick Action Template Buttons directly below Particulars input
    function renderQuickTemplateChips() {
        var $chips = $('#quick-template-chips');
        if (!$chips.length) return;
        $chips.empty();

        var isDebit = parseFloat($('#frm-dr').val()) > 0;
        var isCredit = parseFloat($('#frm-cr').val()) > 0;

        // Active bank from current selected account or form bank dropdown
        var currentBank = currentAccount ? (currentAccount.bank_name || '') : $('#frm-bank-name').val();
        currentBank = $.trim(currentBank).toLowerCase();

        var list = (availableTemplates || []).filter(function(tpl) {
            // Bank specific filtering:
            if (tpl.bank_id || tpl.bank_name) {
                if (!currentBank) return false;
                var tBank = $.trim(tpl.bank_name || '').toLowerCase();
                if (tBank && currentBank.indexOf(tBank) === -1 && tBank.indexOf(currentBank) === -1) {
                    return false;
                }
            }
            // Shared templates (bank_id is null) are always allowed!

            var dir = getTemplateDirection(tpl);
            if (isDebit && !isCredit) {
                return dir === 'debit' || dir === 'both';
            } else if (isCredit && !isDebit) {
                return dir === 'credit' || dir === 'both';
            }
            return true;
        });

        // Priority sort: directional matches first, then alphabetic
        list.sort(function(a, b) {
            var dirA = getTemplateDirection(a);
            var dirB = getTemplateDirection(b);
            if (isDebit) {
                if (dirA === 'debit' && dirB !== 'debit') return -1;
                if (dirB === 'debit' && dirA !== 'debit') return 1;
            } else if (isCredit) {
                if (dirA === 'credit' && dirB !== 'credit') return -1;
                if (dirB === 'credit' && dirA !== 'credit') return 1;
            }
            return a.name.localeCompare(b.name);
        });

        if (list.length === 0) {
            $chips.append('<span class="text-gray-400 italic text-[11px]">No matching templates.</span>');
            return;
        }

        list.forEach(function(tpl) {
            var isSelected = lastSelectedTemplate && (lastSelectedTemplate.id === tpl.id || lastSelectedTemplate.code === tpl.code);
            var $btn = $('<button type="button" class="quick-tpl-btn win-btn text-[11px] font-medium px-2.5 py-0.5 border rounded shadow-xs cursor-pointer flex items-center space-x-1 select-none transition-all">')
                .attr('data-tpl-id', tpl.id)
                .attr('data-tpl-code', tpl.code)
                .attr('title', 'Click to populate narration. Click again to regenerate variables (STAN, Reference, ATM ID).');

            if (isSelected) {
                $btn.addClass('bg-[#2e72cb] text-white border-[#1b56a3] font-bold active shadow-inner')
                    .append('<span>' + escapeHtml(tpl.name) + '</span>')
                    .append('<span class="text-[9px] opacity-90 ml-1 font-mono" title="Click again to regenerate variables">↻</span>');
            } else {
                $btn.addClass('bg-white text-[#1c3a63] border-[#8daecc] hover:bg-[#dce9fa] hover:text-blue-900')
                    .append('<span>' + escapeHtml(tpl.name) + '</span>');
            }

            $btn.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                onQuickTemplateClick(tpl, $(this));
            });

            $chips.append($btn);
        });
    }

    // Handle clicking a Quick Template Button
    function onQuickTemplateClick(tpl, $btn) {
        var currentNarration = $('#frm-particulars').val().trim();

        // If this template is already active in the input box, regenerate variables in-place!
        if (lastSelectedTemplate && (lastSelectedTemplate.id === tpl.id || lastSelectedTemplate.code === tpl.code) && currentNarration.length > 0) {
            triggerRegenerateTokens();
            $btn.addClass('ring-2 ring-blue-500 scale-95');
            setTimeout(function() { $btn.removeClass('ring-2 ring-blue-500 scale-95'); }, 200);
            return;
        }

        // Otherwise populate template freshly into input box
        applyTemplateInPlace(tpl);
        $('#frm-particulars').focus();
        showToast('Loaded template "' + tpl.name + '". Click button again or [↻] to regenerate variables.', 'success');
    }

    $('#btn-clear-particulars').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#frm-particulars').val('');
        lastSelectedTemplate = null;
        lastSelectedPayload = {};
        toggleClearParticularsBtn();
        renderQuickTemplateChips();
        $('#frm-particulars').focus();
    });

    $('#frm-particulars').on('focus', function() {
        toggleClearParticularsBtn();
    });

    $('#frm-particulars').on('input', function() {
        toggleClearParticularsBtn();
        var $dd = $('#particulars-dropdown');
        if (!$dd.hasClass('hidden')) {
            var q = $(this).val();
            clearTimeout(suggestTimer);
            suggestTimer = setTimeout(function() {
                fetchParticularSuggestions(q);
            }, 60);
        }
    });

    $('#btn-toggle-particulars-dd').on('click', function(e) {
        e.preventDefault();
        var $dd = $('#particulars-dropdown');
        if ($dd.hasClass('hidden')) {
            fetchParticularSuggestions($('#frm-particulars').val());
            $('#frm-particulars').focus();
        } else {
            $dd.addClass('hidden');
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#frm-particulars, #particulars-dropdown, #btn-toggle-particulars-dd, #btn-clear-particulars, #btn-regen-tokens').length) {
            $('#particulars-dropdown').addClass('hidden');
        }
    });

    function fetchParticularSuggestions(q) {
        var acc = currentAccount || resolveAccount();
        var isDebit = parseFloat($('#frm-dr').val()) > 0;
        var dir = isDebit ? 'debit' : 'credit';

        $.getJSON('/atm/api/narrations/suggestions', {
            q: q || '',
            account_id: acc ? acc.id : 0,
            direction: dir
        }, function(res) {
            if (res && res.success && res.data) {
                renderParticularsDropdown(res.data);
            }
        });
    }

    function renderParticularsDropdown(data) {
        var $dd = $('#particulars-dropdown');
        $dd.empty();
        activeDropdownIndex = -1;

        var templates = data.templates || [];
        var history = data.history || [];

        if (templates.length === 0 && history.length === 0) {
            $dd.addClass('hidden');
            return;
        }

        // 1. Templates section
        if (templates.length > 0) {
            $dd.append('<div class="px-2 py-1 text-[10px] font-bold uppercase text-blue-900 bg-[#e6effa] border-b border-[#aec4de]">Templates (Auto-Populate)</div>');
            templates.slice(0, 10).forEach(function(tpl) {
                var $item = $('<div class="particulars-item px-2.5 py-1.5 cursor-pointer flex justify-between items-center text-xs text-black bg-white hover:bg-[#dce9fa] hover:text-blue-700 transition-colors">')
                    .append('<span class="font-medium">' + escapeHtml(tpl.name) + '</span>')
                    .append('<span class="text-[10px] font-mono text-gray-500">[' + escapeHtml(tpl.code) + ']</span>');
                
                $item.data('type', 'template').data('obj', tpl);

                $item.on('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectParticularsItem($item);
                });
                $item.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectParticularsItem($item);
                });
                $dd.append($item);
            });
        }

        // 2. Past History section
        if (history.length > 0) {
            $dd.append('<div class="px-2 py-1 text-[10px] font-bold uppercase text-gray-700 bg-gray-100 border-b border-t border-[#cbd9e8]">Previous Narrations (History)</div>');
            history.slice(0, 10).forEach(function(h) {
                var $item = $('<div class="particulars-item px-2.5 py-1.5 cursor-pointer text-xs text-black bg-white hover:bg-[#dce9fa] hover:text-blue-700 transition-colors">')
                    .text(h.name);
                
                $item.data('type', 'history').data('text', h.name);

                $item.on('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectParticularsItem($item);
                });
                $item.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectParticularsItem($item);
                });
                $dd.append($item);
            });
        }

        $dd.removeClass('hidden');

        // Automatically highlight the first item for instant keyboard selection
        var $items = $dd.find('.particulars-item');
        if ($items.length > 0) {
            activeDropdownIndex = 0;
            updateActiveDropdownItem($items);
        }
    }

    function selectParticularsItem($item) {
        if (!$item || !$item.length) return;
        suppressSuggestOnFocus = true;
        var type = $item.data('type');
        if (type === 'template') {
            applyTemplateInPlace($item.data('obj'));
        } else {
            $('#frm-particulars').val($item.data('text') || '');
            lastSelectedTemplate = null;
            lastSelectedPayload = {};
        }
        toggleClearParticularsBtn();
        $('#particulars-dropdown').addClass('hidden');
        activeDropdownIndex = -1;
        $('#frm-particulars').focus();
        setTimeout(function() {
            suppressSuggestOnFocus = false;
        }, 250);
    }

    function applyTemplateInPlace(tpl) {
        if (!tpl) return;
        var acc = currentAccount || resolveAccount();
        var populated = NarrationEngine.autoPopulateFromTemplate(tpl, acc);
        lastSelectedTemplate = tpl;
        lastSelectedPayload = populated.payload || {};

        var text = populated.narration || populated.compiled || '';
        $('#frm-particulars').val(text);
        toggleClearParticularsBtn();
        renderQuickTemplateChips();

        // Auto-fill reference field if STAN, Reference, or Transaction ID generated
        if (populated.payload) {
            if (populated.payload.reference_no && !$('#frm-ref-no').val()) {
                $('#frm-ref-no').val(populated.payload.reference_no);
            } else if (populated.payload.stan && !$('#frm-ref-no').val()) {
                $('#frm-ref-no').val(populated.payload.stan);
            } else if (populated.payload.transaction_id && !$('#frm-ref-no').val()) {
                $('#frm-ref-no').val(populated.payload.transaction_id);
            }
        }
    }

    function updateActiveDropdownItem($items) {
        $items.removeClass('bg-[#dce9fa] text-blue-700 font-bold border-l-4 border-blue-600').addClass('text-black bg-white');
        if (activeDropdownIndex >= 0 && activeDropdownIndex < $items.length) {
            var $cur = $items.eq(activeDropdownIndex);
            $cur.removeClass('text-black bg-white').addClass('bg-[#dce9fa] text-blue-700 font-bold border-l-4 border-blue-600');
            
            // Auto scroll container so item is visible
            var $dd = $('#particulars-dropdown');
            var ddScrollTop = $dd.scrollTop();
            var ddHeight = $dd.height();
            var pos = $cur.position();
            if (pos) {
                var itemTop = pos.top + ddScrollTop;
                var itemHeight = $cur.outerHeight();
                if (itemTop < ddScrollTop) {
                    $dd.scrollTop(itemTop);
                } else if (itemTop + itemHeight > ddScrollTop + ddHeight) {
                    $dd.scrollTop(itemTop + itemHeight - ddHeight);
                }
            }
        }
    }

    // Keyboard navigation in Particulars combobox
    $('#frm-particulars').on('keydown', function(e) {
        var $dd = $('#particulars-dropdown');
        var isDropdownOpen = !$dd.hasClass('hidden');
        var $items = $dd.find('.particulars-item');

        if (e.key === 'ArrowDown') {
            if (!isDropdownOpen) {
                fetchParticularSuggestions($(this).val());
                return;
            }
            e.preventDefault();
            if ($items.length > 0) {
                activeDropdownIndex = (activeDropdownIndex + 1) % $items.length;
                updateActiveDropdownItem($items);
            }
            return;
        }

        if (e.key === 'ArrowUp') {
            if (isDropdownOpen && $items.length > 0) {
                e.preventDefault();
                activeDropdownIndex = (activeDropdownIndex - 1 + $items.length) % $items.length;
                updateActiveDropdownItem($items);
            }
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            // 1. If dropdown is open with suggestions, select highlighted or first item
            if (isDropdownOpen && $items.length > 0) {
                var idx = (activeDropdownIndex >= 0 && activeDropdownIndex < $items.length) ? activeDropdownIndex : 0;
                selectParticularsItem($items.eq(idx));
                return;
            }

            // 2. Dropdown closed or no suggestions: immediately SAVE entry!
            $dd.addClass('hidden');
            if ($.trim($(this).val())) {
                saveOrUpdateEntry(!!$('#editing-tx-id').val());
            } else {
                showToast('Please type or select Particulars (Narration).', 'error');
            }
            return;
        }

        if (e.key === 'Tab') {
            if (isDropdownOpen && $items.length > 0) {
                var idx = (activeDropdownIndex >= 0 && activeDropdownIndex < $items.length) ? activeDropdownIndex : 0;
                selectParticularsItem($items.eq(idx));
            }
            $dd.addClass('hidden');
            if (e.shiftKey) {
                $('#frm-cr').focus().select();
            } else {
                $('#frm-ref-no').focus().select();
            }
            e.preventDefault();
            return;
        }

        if (e.key === 'Escape') {
            if (isDropdownOpen) {
                $dd.addClass('hidden');
                activeDropdownIndex = -1;
            } else if ($(this).val()) {
                $('#btn-clear-particulars').click();
            }
        }
    });

    // [↻] Regenerate variables (STAN, Reference, TXID, ATM ID) in-place
    function triggerRegenerateTokens() {
        var txt = $('#frm-particulars').val();
        if (!txt) {
            showToast('Type or select a narration first before regenerating.', 'error');
            return;
        }

        var res = NarrationEngine.regenerateAllInNarration(txt, lastSelectedTemplate, lastSelectedPayload);
        if (res && res.updatedNarration) {
            $('#frm-particulars').val(res.updatedNarration);
            toggleClearParticularsBtn();
            lastSelectedPayload = res.updatedPayload || lastSelectedPayload;
            renderQuickTemplateChips();

            // Update ref-no if it was filled with STAN, Reference, or TXID
            if (lastSelectedPayload && lastSelectedPayload.stan) {
                $('#frm-ref-no').val(lastSelectedPayload.stan);
            } else if (lastSelectedPayload && lastSelectedPayload.reference_no) {
                $('#frm-ref-no').val(lastSelectedPayload.reference_no);
            } else if (lastSelectedPayload && lastSelectedPayload.transaction_id) {
                $('#frm-ref-no').val(lastSelectedPayload.transaction_id);
            }

            var parts = [];
            if (lastSelectedPayload.stan) parts.push('STAN: ' + lastSelectedPayload.stan);
            if (lastSelectedPayload.atm_code) parts.push('ATM: ' + lastSelectedPayload.atm_code);
            if (lastSelectedPayload.transaction_id) parts.push('ID: ' + lastSelectedPayload.transaction_id);
            if (lastSelectedPayload.reference_no) parts.push('Ref: ' + lastSelectedPayload.reference_no);
            var detailMsg = parts.length > 0 ? (' (' + parts.join(', ') + ')') : '';
            showToast('Regenerated variable tokens' + detailMsg + ' in-place!', 'success');
        }
    }

    $('#btn-regen-tokens').on('click', function(e) {
        e.preventDefault();
        triggerRegenerateTokens();
    });

    // Load recent transactions for account
    function loadTransactions(accId) {
        if (!accId) return;
        var $tbody = $('#tx-table-body');
        $tbody.html('<tr><td colspan="9" class="text-center py-6 text-gray-400 italic">Loading transactions...</td></tr>');

        $.getJSON('/atm/api/accounts/' + accId + '/recent-transactions?limit=0', function(res) {
            if (res.success && res.data) {
                cachedTransactions = res.data.transactions || [];
                renderTable(cachedTransactions);
                if (res.data.account && res.data.account.current_balance) {
                    var b = parseFloat(res.data.account.current_balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    $('#balance-val').text(b);
                }
            } else {
                $tbody.html('<tr><td colspan="9" class="text-center py-4 text-red-500">Failed to load transactions.</td></tr>');
            }
        });
    }

    function renderTable(list) {
        var $tbody = $('#tx-table-body');
        $tbody.empty();

        if (!list || list.length === 0) {
            $tbody.html('<tr><td colspan="9" class="text-center py-6 text-gray-400 italic">No transactions found for this account. Enter Dr/Cr and Particulars above to save.</td></tr>');
            return;
        }

        list.forEach(function(tx) {
            var debitNum = parseFloat(String(tx.debit).replace(/,/g, ''));
            var creditNum = parseFloat(String(tx.credit).replace(/,/g, ''));
            var debitStr = debitNum > 0 ? formatNumber(tx.debit) : '0.0000';
            var creditStr = creditNum > 0 ? formatNumber(tx.credit) : '0.0000';

            // Format date string nicely e.g. 23/06/2025 11:24 pm
            var displayDate = tx.transaction_date;
/*            if (tx.created_at) {
                var d = new Date(tx.created_at);
                if (!isNaN(d.getTime())) {
                    var day = String(d.getDate()).padStart(2, '0');
                    var mon = String(d.getMonth() + 1).padStart(2, '0');
                    var yr = d.getFullYear();
                    var hrs = d.getHours();
                    var ampm = hrs >= 12 ? 'pm' : 'am';
                    hrs = hrs % 12 || 12;
                    var mins = String(d.getMinutes()).padStart(2, '0');
                    displayDate = day + '/' + mon + '/' + yr + ' ' + hrs + ':' + mins + ' ' + ampm;
                }
            }*/

            var $tr = $('<tr>').addClass('cursor-pointer');
            $tr.attr('id', 'row-tx-' + tx.id);
            $tr.append('<td class="font-mono">' + tx.id + '</td>');
            $tr.append('<td class="font-mono whitespace-nowrap">' + escapeHtml(displayDate) + '</td>');
            $tr.append('<td>' + escapeHtml(tx.bank_name || $('#frm-bank-name').val() || '-') + '</td>');
            $tr.append('<td>' + escapeHtml(tx.narration) + '</td>');
            $tr.append('<td class="text-right font-mono ' + (debitNum > 0 ? 'text-red-600 font-bold' : 'text-gray-600') + '">' + debitStr + '</td>');
            $tr.append('<td class="text-right font-mono ' + (creditNum > 0 ? 'text-green-600 font-bold' : 'text-gray-600') + '">' + creditStr + '</td>');
            $tr.append('<td class="text-center font-mono text-gray-500">' + escapeHtml(tx.reference_no || '-') + '</td>');

            // Edit Action
            var $editBtn = $('<button type="button" class="win-btn win-btn-sm text-blue-900">Edit</button>');
            $editBtn.on('click', function(e) {
                e.stopPropagation();
                loadRowForEdit(tx);
            });
            $tr.append($('<td class="text-center">').append($editBtn));

            // Delete Action
            var $delBtn = $('<button type="button" class="win-btn win-btn-sm text-red-700">Delete</button>');
            $delBtn.on('click', function(e) {
                e.stopPropagation();
                voidRow(tx.id);
            });
            $tr.append($('<td class="text-center">').append($delBtn));

            // Row selection highlight
            $tr.on('click', function() {
                $('#tx-table-body tr').removeClass('selected');
                $(this).addClass('selected');
            });

            $tbody.append($tr);
        });
    }

    function loadRowForEdit(tx) {
        currentEditingTx = tx;
        $('#editing-tx-id').val(tx.id);

        $('#frm-entry-date').val(tx.transaction_date);

        var d = parseFloat(String(tx.debit).replace(/,/g, ''));
        var c = parseFloat(String(tx.credit).replace(/,/g, ''));
        $('#frm-dr').val(d > 0 ? tx.debit : '0');
        $('#frm-cr').val(c > 0 ? tx.credit : '0');

        $('#frm-particulars').val(tx.narration);
        toggleClearParticularsBtn();
        $('#frm-ref-no').val(tx.reference_no || '');

        // Button state
        $('#btn-save').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        $('#btn-update').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');

        // Highlight selected row in blue
        $('#tx-table-body tr').removeClass('selected');
        $('#row-tx-' + tx.id).addClass('selected');

        renderQuickTemplateChips();

        showToast('Editing Transaction #' + tx.id + '. Click Update to save changes.', 'success');
        $('#frm-dr').focus().select();
    }

    function resetFormState() {
        currentEditingTx = null;
        $('#editing-tx-id').val('');
        $('#frm-dr').val('0');
        $('#frm-cr').val('0');
        $('#frm-particulars').val('');
        lastSelectedTemplate = null;
        lastSelectedPayload = {};
        toggleClearParticularsBtn();
        $('#frm-ref-no').val('');

        $('#btn-save').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
        $('#btn-update').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        $('#tx-table-body tr').removeClass('selected');
        renderQuickTemplateChips();
    }

    $('#btn-refresh').on('click', function() {
        resetFormState();
        var acc = currentAccount || resolveAccount();
        if (acc) {
            loadTransactions(acc.id);
        }
        showToast('Form refreshed.', 'success');
    });

    // Save New Entry
    $('#btn-save').on('click', function(e) {
        e.preventDefault();
        saveOrUpdateEntry(false);
    });

    // Update Existing Entry
    $('#btn-update').on('click', function(e) {
        e.preventDefault();
        saveOrUpdateEntry(true);
    });

    function saveOrUpdateEntry(isUpdate) {
        var acc = currentAccount || resolveAccount();
        if (!acc) {
            showToast('Please check an account number first.', 'error');
            $('#frm-account-number').focus();
            return;
        }

        var drVal = $.trim($('#frm-dr').val());
        var crVal = $.trim($('#frm-cr').val());
        var dNum = parseFloat(drVal) || 0;
        var cNum = parseFloat(crVal) || 0;

        if (dNum <= 0 && cNum <= 0) {
            showToast('Please enter an amount in Dr or Cr.', 'error');
            $('#frm-dr').focus().select();
            return;
        }
        if (dNum > 0 && cNum > 0) {
            showToast('Enter amount in either Dr or Cr (not both).', 'error');
            return;
        }

        var particulars = $.trim($('#frm-particulars').val());
        if (!particulars) {
            showToast('Particulars (Narration) is required.', 'error');
            $('#frm-particulars').focus();
            return;
        }

        var payload = {
            account_id: acc.id,
            transaction_date: $('#frm-entry-date').val(),
            debit: dNum > 0 ? drVal : '0.00',
            credit: cNum > 0 ? crVal : '0.00',
            narration: particulars,
            reference_no: $.trim($('#frm-ref-no').val())
        };

        var targetUrl = isUpdate 
            ? ('/atm/api/transactions/' + $('#editing-tx-id').val() + '/update')
            : '/atm/api/transactions/save';

        var $btn = isUpdate ? $('#btn-update') : $('#btn-save');
        $btn.prop('disabled', true);

        $.ajax({
            url: targetUrl,
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify(payload),
            success: function(res) {
                if (res && res.success) {
                    showToast(isUpdate ? 'Transaction updated successfully!' : 'Transaction saved successfully!', 'success');
                    resetFormState();
                    loadTransactions(acc.id);
                    // Focus Dr for rapid consecutive entry
                    $('#frm-dr').focus().select();
                } else {
                    showToast(res.message || 'Error processing transaction.', 'error');
                }
            },
            error: function(xhr) {
                var err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error processing transaction.';
                showToast(err, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    }

    function voidRow(txId) {
        if (!confirm('Are you sure you want to delete / void Transaction #' + txId + '?')) {
            return;
        }

        $.ajax({
            url: '/atm/api/transactions/' + txId + '/void',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify({ reason: 'Voided from TransactionFrm' }),
            success: function(res) {
                if (res && res.success) {
                    showToast('Transaction #' + txId + ' deleted.', 'success');
                    var acc = currentAccount || resolveAccount();
                    if (acc) loadTransactions(acc.id);
                } else {
                    showToast(res.message || 'Error voiding transaction.', 'error');
                }
            }
        });
    }

    // Keyboard Accelerators: Enter, F2, Ctrl+Enter, Alt+S, Alt+U, Alt+R, Alt+D, Alt+C, Alt+P
    $(document).on('keydown', function(e) {
        // F2 or Alt+G to regenerate STAN / Ref / TXID in-place
        if (e.key === 'F2' || (e.altKey && (e.key === 'g' || e.key === 'G'))) {
            e.preventDefault();
            triggerRegenerateTokens();
            return;
        }

        // Ctrl+Enter or Alt+S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            if ($('#editing-tx-id').val()) {
                $('#btn-update').click();
            } else {
                $('#btn-save').click();
            }
            return;
        }

        if (e.altKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            $('#btn-save').click();
            return;
        }

        if (e.altKey && (e.key === 'u' || e.key === 'U')) {
            e.preventDefault();
            $('#btn-update').click();
            return;
        }

        if (e.altKey && (e.key === 'r' || e.key === 'R')) {
            e.preventDefault();
            $('#btn-refresh').click();
            return;
        }

        // Quick field jumper keys
        if (e.altKey && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            $('#frm-dr').focus().select();
            return;
        }
        if (e.altKey && (e.key === 'c' || e.key === 'C')) {
            e.preventDefault();
            $('#frm-cr').focus().select();
            return;
        }
        if (e.altKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            $('#frm-particulars').focus();
            return;
        }
        if (e.altKey && (e.key === 'a' || e.key === 'A')) {
            e.preventDefault();
            $('#frm-account-number').focus().select();
            return;
        }
    });

    // Initial load
    if (currentAccount) {
        loadTransactions(currentAccount.id);
    }
    renderQuickTemplateChips();
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
