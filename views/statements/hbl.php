<!-- 
================================================================================
File: views/statements/hbl.php
Browser URL: http://localhost/atm/statements/hbl.php (or /atm/hbl.php)
Description: Dedicated Habib Bank Limited (HBL) Statement Generator (حبیب بینک لمیٹڈ مخصوص اسٹیٹمنٹ جنریٹر)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. یہ فائل حبیب بینک لمیٹڈ (HBL) کی اسٹیٹمنٹ ("YOUR ACCOUNT STATEMENT") کا مخصوص پیج ہے۔
2. یہ بالکل الگ (Isolated) پیج ہے تاکہ فیصل بینک، الائیڈ بینک اور میزان بینک کے فارمیٹس کو کوئی اثر نہ پہنچے۔
3. اس پیج میں HBL کے تمام مخصوص فیلڈز شامل ہیں:
   - برانچ کا نام (Branch Name e.g. PESHAWAR-CITY BRANCH)
   - پرنٹنگ ڈیٹ (Printing Date e.g. 10-08-26)
   - فریکوئنسی (Frequency e.g. INTERIM / DUPLICATE)
   - یوزر / آپریٹر کوڈ (User Code e.g. APR801)
   - اکاؤنٹ ٹائپ (Account Type e.g. HBL FreedomAccount)
4. اگر آپ پرنٹ ڈیزائن میں ترمیم کرنا چاہیں تو فائل:
   app/Services/Statement/HBLStatementRenderer.php
   کو ایڈٹ کریں۔
================================================================================
-->
<?php 
$pageTitle = 'Habib Bank Limited - YOUR ACCOUNT STATEMENT'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 

$activeBankName = 'Habib Bank Limited';
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-3">
    <!-- Desktop Dialog Frame matching Application Windows Theme -->
    <div class="win-window mb-4">
        <!-- Titlebar -->
        <div class="win-titlebar bg-gradient-to-r from-[#064e3b] to-[#047857] text-white flex items-center justify-between px-3 py-1.5 border-b border-[#022c22]">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-bold tracking-wide text-xs">Habib Bank Limited (HBL) — YOUR ACCOUNT STATEMENT Generator</span>
            </div>
            <div class="flex items-center space-x-1">
                <a href="/atm/statements.php" class="text-[11px] bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 px-2 py-0.5 rounded border border-emerald-400/40">
                    ← All Statements
                </a>
            </div>
        </div>

        <!-- HBL Quick Form Header Banner -->
        <div class="bg-[#f0fdf4] border-b border-[#bbf7d0] px-4 py-2 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded bg-[#065f46] flex items-center justify-center text-emerald-200 font-bold text-lg shadow-xs">
                    H
                </div>
                <div>
                    <h2 class="font-bold text-[#065f46] text-sm leading-tight">HBL Statement Portal</h2>
                    <p class="text-[11px] text-gray-500">Dedicated statement generation for Habib Bank Limited accounts with authentic monospaced 111-column terminal layout.</p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="inline-block bg-emerald-100 text-[#065f46] text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-300">
                    Format: Terminal Dot-Matrix (111 Cols)
                </span>
            </div>
        </div>

        <!-- Parameter Form -->
        <div class="p-4 bg-[#f8fafc] text-xs text-[#111827]">
            <form id="hbl-statement-form" class="space-y-3">
                <input type="hidden" id="hbl-account-id" name="account_id" value="<?= e($selectedAccountId) ?>">
                <input type="hidden" id="hbl-bank-name" name="bank_name" value="Habib Bank Limited">
                <input type="hidden" id="hbl-format" name="format" value="hbl">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                    <!-- Left Column -->
                    <div class="space-y-2">
                        <!-- Account Code Selector -->
                        <div class="flex items-center">
                            <label for="hbl-account-code-input" class="font-bold text-[#065f46] w-36 whitespace-nowrap">Account Code / No</label>
                            <input type="text" id="hbl-account-code-input" list="hbl-acc-datalist" placeholder="Select Account Code or Title" class="win-input flex-1 font-mono font-bold text-emerald-950 text-xs">
                            <datalist id="hbl-acc-datalist">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= e($acc['account_identifier'] ?? $acc['id']) ?>"><?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Customer Title / Name -->
                        <div class="flex items-start">
                            <label for="hbl-customer-title" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Account Title</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-customer-title" name="account_title" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. SS RAHMAN TRADERS</div>
                            </div>
                        </div>

                        <!-- Customer Address -->
                        <div class="flex items-start">
                            <label for="hbl-customer-address" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Address Lines</label>
                            <div class="flex-1">
                                <textarea id="hbl-customer-address" name="address" rows="3" placeholder="" class="win-input w-full text-xs resize-none"></textarea>
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 3RD FLOOR WALI YAR PLAZA, NEAR QAZAFI HOTEL, PESHAWAR PK</div>
                            </div>
                        </div>

                        <!-- Date From -->
                        <div class="flex items-center">
                            <label for="hbl-start-date" class="font-bold text-[#065f46] w-36 whitespace-nowrap">Date From :</label>
                            <input type="date" id="hbl-start-date" name="start_date" value="<?= e($startDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Date To -->
                        <div class="flex items-center">
                            <label for="hbl-end-date" class="font-bold text-[#065f46] w-36 whitespace-nowrap">Date To :</label>
                            <input type="date" id="hbl-end-date" name="end_date" value="<?= e($endDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Opening Balance -->
                        <div class="flex items-center">
                            <label for="hbl-opening-balance" class="font-bold text-[#065f46] w-36 whitespace-nowrap">Opening Balance</label>
                            <input type="text" inputmode="decimal" id="hbl-opening-balance" name="opening_balance" placeholder="0.00" class="win-input flex-1 font-mono font-bold text-xs">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-2">
                        <!-- Account Number (Actual bank account number) -->
                        <div class="flex items-start">
                            <label for="hbl-account-number" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Account No</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-account-number" name="account_number" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 0417-79011691-03</div>
                            </div>
                        </div>

                        <!-- Branch Name -->
                        <div class="flex items-start">
                            <label for="hbl-branch-name" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Branch Name</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-branch-name" name="branch_name" value="" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. PESHAWAR-CITY BRANCH</div>
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div class="flex items-start">
                            <label for="hbl-account-type" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Account Type</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-account-type" name="account_type" value="" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. HBL FreedomAccount</div>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div class="flex items-start">
                            <label for="hbl-currency" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Currency</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-currency" name="currency" value="Pakistan Rupee" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">Pakistan Rupee</div>
                            </div>
                        </div>

                        <!-- Printing Date -->
                        <div class="flex items-start">
                            <label for="hbl-printing-date" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Printing Date</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-printing-date" name="printing_date" value="<?= date('d-m-y') ?>" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. <?= date('d-m-y') ?></div>
                            </div>
                        </div>

                        <!-- Frequency -->
                        <div class="flex items-start">
                            <label for="hbl-frequency" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">Frequency</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-frequency" name="frequency" value="" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. INTERIM / DUPLICATE</div>
                            </div>
                        </div>

                        <!-- User Code -->
                        <div class="flex items-start">
                            <label for="hbl-user-code" class="font-bold text-[#065f46] w-36 whitespace-nowrap pt-1">User Code</label>
                            <div class="flex-1">
                                <input type="text" id="hbl-user-code" name="user_code" value="" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. APR801</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-center space-x-3 pt-3 border-t border-[#cbd9e8]">
                    <button type="submit" id="btn-hbl-generate" class="win-btn font-black text-emerald-950 px-8 py-1.5 text-xs bg-emerald-100 hover:bg-emerald-200">
                        ⚡ Generate HBL Statement
                    </button>
                    <button type="button" id="btn-hbl-print" class="win-btn font-bold text-[#065f46] px-6 py-1.5 text-xs">
                        🖨 Print / PDF View
                    </button>
                    <a href="/atm/statements.php" class="win-btn font-normal text-gray-700 px-4 py-1.5 text-xs">
                        Cancel / Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Statement Render Output Window -->
    <div id="hbl-statement-output-panel" class="win-window bg-white p-4 sm:p-6 min-h-[400px]">
        <div id="hbl-output-placeholder" class="text-center py-20 text-gray-400 font-mono text-xs">
            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-lg font-bold">
                H
            </div>
            <p class="font-bold text-gray-700 text-sm mb-1">HBL Statement Preview Window</p>
            <p>Select an Account above and click "Generate HBL Statement" to preview the authentic monospaced dot-matrix layout.</p>
        </div>
        <div id="hbl-statement-rendered-content" class="hidden"></div>
    </div>
</main>

<script>
var accountsList = <?= json_encode(array_values($accounts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var accountsByIdentifier = {};
var accountsById = {};

accountsList.forEach(function(a) {
    var ident = String(a.account_identifier || a.id).trim().toLowerCase();
    accountsByIdentifier[ident] = a;
    accountsById[a.id] = a;
});

function resolveHBLAccount() {
    var q = $.trim($('#hbl-account-code-input').val()).toLowerCase();
    if (!q) return null;
    if (accountsByIdentifier[q]) return accountsByIdentifier[q];
    var match = accountsList.find(function(a) {
        return String(a.account_title).toLowerCase() === q || String(a.account_number).toLowerCase() === q;
    });
    return match || null;
}

function populateHBLFields(acc) {
    if (!acc) return;
    $('#hbl-account-id').val(acc.id);
    $('#hbl-customer-title').val(acc.account_title || '');
    $('#hbl-account-number').val(acc.account_number || '');
    $('#hbl-customer-address').val(acc.address || '');
    $('#hbl-opening-balance').val(parseFloat(acc.initial_balance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#hbl-branch-name').val(acc.branch_name || '');
    $('#hbl-account-type').val(acc.account_type || '');
    $('#hbl-currency').val(acc.currency || 'Pakistan Rupee');
}

$('#hbl-account-code-input').on('input change', function() {
    var acc = resolveHBLAccount();
    if (acc) {
        populateHBLFields(acc);
    }
});

$('#hbl-statement-form').on('submit', function(e) {
    e.preventDefault();
    executeHBLGeneration();
});

function executeHBLGeneration() {
    var acc = resolveHBLAccount();
    var accId = acc ? acc.id : $('#hbl-account-id').val();

    if (!accId) {
        alert('Please enter or select a valid Account Code / Number.');
        $('#hbl-account-code-input').focus();
        return;
    }

    var payload = {
        account_id: accId,
        start_date: $('#hbl-start-date').val(),
        end_date: $('#hbl-end-date').val(),
        format: 'hbl',
        bank_name: 'Habib Bank Limited',
        account_title: $('#hbl-customer-title').val(),
        account_number: $('#hbl-account-number').val(),
        address: $('#hbl-customer-address').val(),
        branch_name: $('#hbl-branch-name').val() || '',
        account_type: $('#hbl-account-type').val() || '',
        currency: $('#hbl-currency').val() || 'Pakistan Rupee',
        opening_balance: $('#hbl-opening-balance').val(),
        printing_date: $('#hbl-printing-date').val() || '<?= date('d-m-y') ?>',
        frequency: $('#hbl-frequency').val() || '',
        user_code: $('#hbl-user-code').val() || ''
    };

    $('#hbl-output-placeholder').html('<div class="py-12 font-mono text-[#065f46] font-bold">Generating HBL Account Statement...</div>').show();
    $('#hbl-statement-rendered-content').addClass('hidden');

    $.getJSON('/atm/api/statements/generate', payload, function(res) {
        $('#hbl-output-placeholder').hide();
        if (res.success && res.data && res.data.rendered_html) {
            $('#hbl-statement-rendered-content').html(res.data.rendered_html).removeClass('hidden');
        } else {
            $('#hbl-output-placeholder').html('<div class="text-red-600 font-bold py-12">' + (res.message || 'Error generating statement.') + '</div>').show();
        }
    }).fail(function() {
        $('#hbl-output-placeholder').html('<div class="text-red-600 font-bold py-12">Failed to generate HBL statement from server.</div>').show();
    });
}

$('#btn-hbl-print').on('click', function() {
    var acc = resolveHBLAccount();
    var accId = acc ? acc.id : $('#hbl-account-id').val();
    if (!accId) {
        alert('Please select an Account Number first.');
        return;
    }

    var params = new URLSearchParams({
        account_id: accId,
        start_date: $('#hbl-start-date').val(),
        end_date: $('#hbl-end-date').val(),
        format: 'hbl',
        bank_name: 'Habib Bank Limited',
        account_title: $('#hbl-customer-title').val() || '',
        account_number: $('#hbl-account-number').val() || '',
        address: $('#hbl-customer-address').val() || '',
        branch_name: $('#hbl-branch-name').val() || '',
        account_type: $('#hbl-account-type').val() || '',
        currency: $('#hbl-currency').val() || 'Pakistan Rupee',
        opening_balance: $('#hbl-opening-balance').val() || '',
        printing_date: $('#hbl-printing-date').val() || '<?= date('d-m-y') ?>',
        frequency: $('#hbl-frequency').val() || '',
        user_code: $('#hbl-user-code').val() || ''
    });

    window.open('/atm/statements/print.php?' + params.toString(), '_blank');
});

// Auto-select initial account on page load if provided
$(function() {
    var initId = <?= (int)($selectedAccountId ?: 0) ?>;
    if (initId > 0 && accountsById[initId]) {
        var acc = accountsById[initId];
        $('#hbl-account-code-input').val(acc.account_identifier || acc.id);
        populateHBLFields(acc);
        executeHBLGeneration();
    } else if (accountsList.length > 0) {
        var hblAcc = accountsList.find(function(a) {
            return String(a.bank_name).toLowerCase().indexOf('hbl') !== -1 ||
                   String(a.bank_name).toLowerCase().indexOf('habib bank') !== -1;
        });
        if (hblAcc) {
            $('#hbl-account-code-input').val(hblAcc.account_identifier || hblAcc.id);
            populateHBLFields(hblAcc);
            executeHBLGeneration();
        } else {
            $('#hbl-account-code-input').val(accountsList[0].account_identifier || accountsList[0].id);
            populateHBLFields(accountsList[0]);
            executeHBLGeneration();
        }
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
