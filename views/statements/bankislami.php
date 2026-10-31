<!-- 
================================================================================
File: views/statements/bankislami.php
Browser URL: http://localhost/atm/statements/bankislami.php (or /atm/bankislami.php, /atm/bipl.php)
Description: Dedicated BankIslami Pakistan Statement Generator (بینک اسلامی پاکستان مخصوص اسٹیٹمنٹ جنریٹر)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. یہ فائل بینک اسلامی پاکستان لمیٹڈ (BankIslami Pakistan Limited) کی اسٹیٹمنٹ کا مخصوص اور خود مختار پیج ہے۔
2. یہ بالکل الگ (Isolated) پیج ہے تاکہ دیگر بینکس (فیصل، الائیڈ، میزان، ایچ بی ایل اور حبیب میٹرو) کے فارمیٹس کو کوئی اثر نہ پہنچے۔
3. اس پیج میں BankIslami کے تمام ضروری اور مخصوص فیلڈز شامل ہیں:
   - ٹاپ ڈارک بینر برانڈنگ (BankIslami)
   - دستاویز کا عنوان (Duplicate Statement Of Account)
   - ایشوئنگ برانچ اور ایشو ڈیٹ (Issuing Branch & Issue Date)
   - کسٹمر باکس (Customer Title & Multi-line Address Box)
   - اکاؤنٹ میٹا ڈیٹا (Account Branch, Account Type, Currency, Account No, IBAN)
   - سمری بلاکس (Debit/Credit Counts & Amounts, Closing Balance, Available Balance)
4. اگر آپ پرنٹ ڈیزائن میں ترمیم کرنا چاہیں تو فائل:
   app/Services/Statement/BankIslamiStatementRenderer.php
   کو ایڈٹ کریں۔
================================================================================
-->
<?php 
$pageTitle = 'BankIslami Pakistan Ltd. — Statement Generator'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 

$activeBankName = 'BankIslami Pakistan Limited';
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-3">
    <!-- Desktop Dialog Frame matching Application Windows Theme -->
    <div class="win-window mb-4">
        <!-- Titlebar -->
        <div class="win-titlebar bg-gradient-to-r from-[#1e293b] to-[#334155] text-white flex items-center justify-between px-3 py-1.5 border-b border-[#0f172a]">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-bold tracking-wide text-xs">BankIslami Pakistan Limited (BIPL) — Statement Generator</span>
            </div>
            <div class="flex items-center space-x-1">
                <a href="/atm/statements.php" class="text-[11px] bg-slate-800/80 hover:bg-slate-700 text-slate-100 px-2 py-0.5 rounded border border-slate-500/40">
                    ← All Statements
                </a>
            </div>
        </div>

        <!-- BankIslami Header Banner -->
        <div class="bg-[#f8fafc] border-b border-[#cbd5e1] px-4 py-2 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded bg-[#2d3748] flex items-center justify-center text-white font-serif font-bold text-base shadow-xs">
                    BI
                </div>
                <div>
                    <h2 class="font-bold text-[#1e293b] text-sm leading-tight">BankIslami Statement Portal</h2>
                    <p class="text-[11px] text-gray-500">Dedicated statement generation for BankIslami Pakistan accounts with authentic top dark banner, customer info box and dual balance summary.</p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="inline-block bg-slate-100 text-[#1e293b] text-[10px] font-bold px-2 py-0.5 rounded border border-slate-300 font-mono">
                    Format: BankIslami Layout (A4)
                </span>
            </div>
        </div>

        <!-- Parameter Form -->
        <div class="p-4 bg-[#f8fafc] text-xs text-[#111827]">
            <form id="bi-statement-form" class="space-y-3">
                <input type="hidden" id="bi-account-id" name="account_id" value="<?= e($selectedAccountId) ?>">
                <input type="hidden" id="bi-bank-name" name="bank_name" value="BankIslami Pakistan Limited">
                <input type="hidden" id="bi-format" name="format" value="bankislami">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                    <!-- Left Column -->
                    <div class="space-y-2">
                        <!-- Account Code Selector -->
                        <div class="flex items-center">
                            <label for="bi-account-code-input" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Account Code / No</label>
                            <input type="text" id="bi-account-code-input" list="bi-acc-datalist" placeholder="Select Account Code or Title" class="win-input flex-1 font-mono font-bold text-slate-950 text-xs">
                            <datalist id="bi-acc-datalist">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= e($acc['account_identifier'] ?? $acc['id']) ?>"><?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Customer Title / Name -->
                        <div class="flex items-start">
                            <label for="bi-customer-title" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Customer Title</label>
                            <div class="flex-1">
                                <input type="text" id="bi-customer-title" name="account_title" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. REHMAT OBAID SEAMLESS STEEL PIPE & WIRE</div>
                            </div>
                        </div>

                        <!-- Customer Address -->
                        <div class="flex items-start">
                            <label for="bi-customer-address" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Address Lines</label>
                            <div class="flex-1">
                                <textarea id="bi-customer-address" name="address" rows="3" placeholder="" class="win-input w-full text-xs resize-none"></textarea>
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. REHMAT OBAID SEAMLESS&#10;STEEL PIPE & WIRE ROPE&#10;AIR PORT ROAD&#10;QUETTA&#10;03073832580</div>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="flex items-center">
                            <label for="bi-start-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">From Date :</label>
                            <input type="date" id="bi-start-date" name="start_date" value="<?= e($startDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>
                        <div class="flex items-center">
                            <label for="bi-end-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">To Date :</label>
                            <input type="date" id="bi-end-date" name="end_date" value="<?= e($endDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Opening Balance -->
                        <div class="flex items-center">
                            <label for="bi-opening-balance" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Opening Balance</label>
                            <input type="text" inputmode="decimal" id="bi-opening-balance" name="opening_balance" placeholder="0.00" class="win-input flex-1 font-mono font-bold text-xs">
                        </div>

                        <!-- Statement Title -->
                        <div class="flex items-start">
                            <label for="bi-statement-title" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Statement Title</label>
                            <div class="flex-1">
                                <input type="text" id="bi-statement-title" name="statement_title" value="Duplicate Statement Of Account" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. Duplicate Statement Of Account</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (BankIslami Metadata Box fields) -->
                    <div class="space-y-2">
                        <!-- Account Branch -->
                        <div class="flex items-start">
                            <label for="bi-branch-name" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Account Branch</label>
                            <div class="flex-1">
                                <input type="text" id="bi-branch-name" name="branch_name" value="KUCHLAK" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. KUCHLAK</div>
                            </div>
                        </div>

                        <!-- Issuing Branch -->
                        <div class="flex items-start">
                            <label for="bipl-issuing-branch" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Issuing Branch</label>
                            <div class="flex-1">
                                <input type="text" id="bipl-issuing-branch" name="issuing_branch" value="KUCHLAK" placeholder="" class="win-input bi-issuing-branch w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. KUCHLAK</div>
                            </div>
                        </div>

                        <!-- Issue Date -->
                        <div class="flex items-start">
                            <label for="bi-issue-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Issue Date</label>
                            <div class="flex-1">
                                <input type="text" id="bi-issue-date" name="issue_date" value="<?= date('d-M-Y') ?>" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 29-Sep-2026</div>
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div class="flex items-start">
                            <label for="bi-account-type" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Account Type</label>
                            <div class="flex-1">
                                <input type="text" id="bi-account-type" name="account_type" value="Current Account" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. Current Account</div>
                            </div>
                        </div>

                        <!-- Account Number -->
                        <div class="flex items-start">
                            <label for="bi-account-number" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Account No</label>
                            <div class="flex-1">
                                <input type="text" id="bi-account-number" name="account_number" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 104700495490001</div>
                            </div>
                        </div>

                        <!-- IBAN -->
                        <div class="flex items-start">
                            <label for="bi-iban" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">IBAN</label>
                            <div class="flex-1">
                                <input type="text" id="bi-iban" name="iban" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. PK41BKIP0104700495490001</div>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div class="flex items-start">
                            <label for="bi-currency" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Currency</label>
                            <div class="flex-1">
                                <input type="text" id="bi-currency" name="currency" value="Pakistani Rupee" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">Pakistani Rupee</div>
                            </div>
                        </div>

                        <!-- Printed By / User Code -->
                        <div class="flex items-start">
                            <label for="bi-user-code" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Printed By</label>
                            <div class="flex-1">
                                <input type="text" id="bi-user-code" name="user_code" value="UM12926" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. UM12926</div>
                            </div>
                        </div>

                        <!-- Printed On -->
                        <div class="flex items-start">
                            <label for="bi-printed-on" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Printed On</label>
                            <div class="flex-1">
                                <input type="text" id="bi-printed-on" name="printed_on" value="<?= date('n/j/Y g:i A') ?>" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 9/29/2026 5:06 PM</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-between pt-3 border-t border-[#cbd5e1] mt-3">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center text-xs text-gray-600 font-mono">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            Layout Calibrated to Authentic Scanned Statement
                        </span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button type="submit" id="btn-bi-preview" class="win-btn font-bold text-blue-950 px-6 py-1.5 text-xs shadow-xs">
                            🔍 Screen Preview
                        </button>
                        <button type="button" id="btn-bi-print" class="win-btn font-bold text-[#1e293b] px-6 py-1.5 text-xs shadow-xs bg-[#f1f5f9]">
                            🖨 Print / PDF View
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Rendered Statement Output Preview Section -->
        <div class="bg-[#e2e8f0] p-4 border-t border-[#cbd5e1] min-h-[400px]">
            <div id="bi-loading-spinner" class="hidden text-center py-16">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-600 border-t-transparent mb-2"></div>
                <div class="font-mono text-xs font-bold text-slate-800">Generating BankIslami Pakistan Statement...</div>
            </div>

            <div id="bi-empty-notice" class="text-center py-16 text-gray-500 italic text-xs font-mono">
                Select an account above and click <span class="font-bold text-slate-700">"Screen Preview"</span> or <span class="font-bold text-slate-700">"Print / PDF View"</span> to generate the official BankIslami statement.
            </div>

            <div id="bi-rendered-wrapper" class="hidden shadow-lg mx-auto bg-white" style="max-width: 820px;">
                <!-- HTML rendered output loaded dynamically via API -->
            </div>
        </div>
    </div>
</main>

<script>
var biAccountsList = <?= json_encode($accounts) ?>;
var biAccountsByIdentifier = {};
var biAccountsById = {};

biAccountsList.forEach(function(a) {
    var ident = String(a.account_identifier || a.id).trim().toLowerCase();
    biAccountsByIdentifier[ident] = a;
    biAccountsById[a.id] = a;
});

function resolveBiAccount() {
    var q = $.trim($('#bi-account-code-input').val()).toLowerCase();
    if (!q) return null;
    if (biAccountsByIdentifier[q]) return biAccountsByIdentifier[q];
    var match = biAccountsList.find(function(a) {
        return String(a.account_title).toLowerCase() === q || String(a.account_number).toLowerCase() === q;
    });
    return match || null;
}

function populateBiFields(acc) {
    if (!acc) return;
    $('#bi-account-id').val(acc.id);
    $('#bi-customer-title').val(acc.account_title || '');
    $('#bi-customer-address').val(acc.address || '');
    $('#bi-account-number').val(acc.account_number || '');
    $('#bi-iban').val(acc.iban || '');
    $('#bi-branch-name').val(acc.branch_name || 'KUCHLAK');
    $('#bipl-issuing-branch, #bi-issuing-branch').val(acc.branch_name || 'KUCHLAK');
    $('#bi-account-type').val(acc.account_type || 'Current Account');
    $('#bi-currency').val(acc.currency || 'Pakistani Rupee');
    
    if (acc.max_tx_date && (!$('#bi-end-date').val() || acc.max_tx_date > $('#bi-end-date').val())) {
        $('#bi-end-date').val(acc.max_tx_date);
    }
    
    var initBal = parseFloat(acc.initial_balance || 0);
    $('#bi-opening-balance').val(initBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}

$('#bi-account-code-input').on('input change', function() {
    var acc = resolveBiAccount();
    if (acc) {
        populateBiFields(acc);
    }
});

function getBiFormData() {
    var acc = resolveBiAccount();
    var accId = acc ? acc.id : $('#bi-account-id').val();
    if (!accId) {
        alert('Please select or enter an Account Number first.');
        $('#bi-account-code-input').focus();
        return null;
    }

    return {
        account_id: accId,
        start_date: $('#bi-start-date').val(),
        end_date: $('#bi-end-date').val(),
        format: 'bankislami',
        bank_name: 'BankIslami Pakistan Limited',
        account_title: $('#bi-customer-title').val(),
        address: $('#bi-customer-address').val(),
        account_number: $('#bi-account-number').val(),
        iban: $('#bi-iban').val(),
        branch_name: $('#bi-branch-name').val() || 'KUCHLAK',
        issuing_branch: $('#bipl-issuing-branch, #bi-issuing-branch').val() || 'KUCHLAK',
        issue_date: $('#bi-issue-date').val() || '',
        statement_title: $('#bi-statement-title').val() || 'Duplicate Statement Of Account',
        account_type: $('#bi-account-type').val() || 'Current Account',
        account_status: $('#bi-account-type').val() || 'Current Account',
        currency: $('#bi-currency').val() || 'Pakistani Rupee',
        opening_balance: $('#bi-opening-balance').val() || '0.00',
        user_code: $('#bi-user-code').val() || 'UM12926',
        printed_on: $('#bi-printed-on').val() || ''
    };
}

// Screen Preview
$('#bi-statement-form').on('submit', function(e) {
    e.preventDefault();
    var data = getBiFormData();
    if (!data) return;

    $('#bi-empty-notice').hide();
    $('#bi-rendered-wrapper').addClass('hidden').html('');
    $('#bi-loading-spinner').removeClass('hidden');

    $.getJSON('/atm/api/statements/generate', data, function(res) {
        $('#bi-loading-spinner').addClass('hidden');
        if (res.success && res.data && res.data.rendered_html) {
            $('#bi-rendered-wrapper').html(res.data.rendered_html).removeClass('hidden');
        } else {
            $('#bi-empty-notice').html('<span class="text-red-600 font-bold">' + (res.message || 'Error generating statement.') + '</span>').show();
        }
    }).fail(function() {
        $('#bi-loading-spinner').addClass('hidden');
        $('#bi-empty-notice').html('<span class="text-red-600 font-bold">Failed to communicate with server.</span>').show();
    });
});

// Print View
$('#btn-bi-print').on('click', function() {
    var data = getBiFormData();
    if (!data) return;

    var params = new URLSearchParams(data);
    window.open('/atm/statements/print.php?' + params.toString(), '_blank');
});

// Auto-select initial account on page load if provided
$(document).ready(function() {
    var initId = $('#bi-account-id').val();
    if (initId && biAccountsById[initId]) {
        $('#bi-account-code-input').val(biAccountsById[initId].account_identifier || initId);
        populateBiFields(biAccountsById[initId]);
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
