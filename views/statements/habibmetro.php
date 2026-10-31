<!-- 
================================================================================
File: views/statements/habibmetro.php
Browser URL: http://localhost/atm/statements/habibmetro.php (or /atm/habibmetro.php, /atm/hmb.php)
Description: Dedicated Habib Metropolitan Bank Ltd. (HMB) Statement Generator (حبیب میٹرو پولیٹن بینک مخصوص اسٹیٹمنٹ جنریٹر)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. یہ فائل حبیب میٹرو پولیٹن بینک لمیٹڈ (Habib Metropolitan Bank Ltd.) کی اسٹیٹمنٹ کا مخصوص اور خود مختار پیج ہے۔
2. یہ بالکل الگ (Isolated) پیج ہے تاکہ دیگر بینکس (فیصل، الائیڈ، میزان اور ایچ بی ایل) کے فارمیٹس کو کوئی اثر نہ پہنچے۔
3. اس پیج میں Habib Metro کے تمام ضروری اور مخصوص فیلڈز شامل ہیں:
   - برانچ کا نام (Branch Name e.g. IBB - Tarnol - Islamabad)
   - اکاؤنٹ ٹائپ (A/C Type e.g. SIRAT Current Plus Account)
   - پرنٹڈ آن (Printed On e.g. 23-Sep-2026)
   - آئی بین (IBAN e.g. PK13MPBL9883727140109975)
   - اوپننگ بیلنس اور کسٹمر ایڈریس
4. اگر آپ پرنٹ ڈیزائن میں ترمیم کرنا چاہیں تو فائل:
   app/Services/Statement/HabibMetroStatementRenderer.php
   کو ایڈٹ کریں۔
================================================================================
-->
<?php 
$pageTitle = 'Habib Metropolitan Bank Ltd. — Statement Generator'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 

$activeBankName = 'Habib Metropolitan Bank';
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-3">
    <!-- Desktop Dialog Frame matching Application Windows Theme -->
    <div class="win-window mb-4">
        <!-- Titlebar -->
        <div class="win-titlebar bg-gradient-to-r from-[#1e293b] to-[#334155] text-white flex items-center justify-between px-3 py-1.5 border-b border-[#0f172a]">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-bold tracking-wide text-xs">Habib Metropolitan Bank Ltd. (HMB) — Statement Generator</span>
            </div>
            <div class="flex items-center space-x-1">
                <a href="/atm/statements.php" class="text-[11px] bg-slate-800/80 hover:bg-slate-700 text-slate-100 px-2 py-0.5 rounded border border-slate-500/40">
                    ← All Statements
                </a>
            </div>
        </div>

        <!-- HMB Quick Form Header Banner -->
        <div class="bg-[#f8fafc] border-b border-[#cbd5e1] px-4 py-2 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded bg-[#1e293b] flex items-center justify-center text-slate-100 font-serif font-bold text-base shadow-xs">
                    HM
                </div>
                <div>
                    <h2 class="font-bold text-[#1e293b] text-sm leading-tight">Habib Metropolitan Bank Portal</h2>
                    <p class="text-[11px] text-gray-500">Dedicated statement generation for Habib Metro accounts with authentic vector QR code and boxed metadata grid.</p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="inline-block bg-slate-100 text-[#1e293b] text-[10px] font-bold px-2 py-0.5 rounded border border-slate-300 font-mono">
                    Format: Habib Metro Layout (A4)
                </span>
            </div>
        </div>

        <!-- Parameter Form -->
        <div class="p-4 bg-[#f8fafc] text-xs text-[#111827]">
            <form id="hmb-statement-form" class="space-y-3">
                <input type="hidden" id="hmb-account-id" name="account_id" value="<?= e($selectedAccountId) ?>">
                <input type="hidden" id="hmb-bank-name" name="bank_name" value="Habib Metropolitan Bank">
                <input type="hidden" id="hmb-format" name="format" value="habibmetro">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                    <!-- Left Column -->
                    <div class="space-y-2">
                        <!-- Account Code Selector -->
                        <div class="flex items-center">
                            <label for="hmb-account-code-input" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Account Code / No</label>
                            <input type="text" id="hmb-account-code-input" list="hmb-acc-datalist" placeholder="Select Account Code or Title" class="win-input flex-1 font-mono font-bold text-slate-950 text-xs">
                            <datalist id="hmb-acc-datalist">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= e($acc['account_identifier'] ?? $acc['id']) ?>"><?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Customer Title / Name -->
                        <div class="flex items-start">
                            <label for="hmb-customer-title" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Customer Title</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-customer-title" name="account_title" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. IRFAN ULLAH</div>
                            </div>
                        </div>

                        <!-- Customer Address -->
                        <div class="flex items-start">
                            <label for="hmb-customer-address" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Address Lines</label>
                            <div class="flex-1">
                                <textarea id="hmb-customer-address" name="address" rows="2" placeholder="" class="win-input w-full text-xs resize-none"></textarea>
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. MAIN GT ROAD BESIDE BANK ISLAMI, TARNOL, Islamabad</div>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="flex items-center">
                            <label for="hmb-start-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Period From :</label>
                            <input type="date" id="hmb-start-date" name="start_date" value="<?= e($startDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>
                        <div class="flex items-center">
                            <label for="hmb-end-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Period To :</label>
                            <input type="date" id="hmb-end-date" name="end_date" value="<?= e($endDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Opening Balance -->
                        <div class="flex items-center">
                            <label for="hmb-opening-balance" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Opening Balance</label>
                            <input type="text" inputmode="decimal" id="hmb-opening-balance" name="opening_balance" placeholder="0.00" class="win-input flex-1 font-mono font-bold text-xs">
                        </div>
                    </div>

                    <!-- Right Column (HMB Metadata Box fields) -->
                    <div class="space-y-2">
                        <!-- Branch Name -->
                        <div class="flex items-start">
                            <label for="hmb-branch-name" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Branch Name</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-branch-name" name="branch_name" value="" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. IBB - Tarnol - Islamabad</div>
                            </div>
                        </div>

                        <!-- A/C Type -->
                        <div class="flex items-start">
                            <label for="hmb-account-type" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">A/C Type</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-account-type" name="account_type" value="" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. SIRAT Current Plus Account</div>
                            </div>
                        </div>

                        <!-- A/C Number -->
                        <div class="flex items-start">
                            <label for="hmb-account-number" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">A/C Number</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-account-number" name="account_number" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 6-98-83-29310-714-109975</div>
                            </div>
                        </div>

                        <!-- IBAN -->
                        <div class="flex items-start">
                            <label for="hmb-iban" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">IBAN</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-iban" name="iban" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. PK13MPBL9883727140109975</div>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div class="flex items-start">
                            <label for="hmb-currency" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Currency</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-currency" name="currency" value="PKR" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">PKR</div>
                            </div>
                        </div>

                        <!-- Printed On -->
                        <div class="flex items-start">
                            <label for="hmb-printed-on" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Printed On</label>
                            <div class="flex-1">
                                <input type="text" id="hmb-printed-on" name="printed_on" value="<?= date('d-M-Y') ?>" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. <?= date('d-M-Y') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-between pt-3 border-t border-[#cbd5e1] mt-3">
                    <div class="text-[11px] text-gray-500 italic">
                        Tip: You can override any customer or bank metadata before printing. Digital stamps and signatures are omitted for client stamping.
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" id="btn-hmb-generate" class="win-btn font-bold text-slate-900 px-4 py-1.5 flex items-center space-x-1 shadow-xs">
                            <svg class="w-3.5 h-3.5 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
                            </svg>
                            <span>Generate Habib Metro Statement</span>
                        </button>
                        <button type="button" id="btn-hmb-print" class="win-btn font-bold text-blue-900 px-4 py-1.5 flex items-center space-x-1 shadow-xs">
                            <svg class="w-3.5 h-3.5 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Print / Save as PDF</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Output Display Area -->
    <div id="hmb-output-wrapper" class="win-window p-4 min-h-[300px] bg-white">
        <div id="hmb-placeholder" class="text-center py-16 text-gray-400 font-mono">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Select an account code and click &ldquo;Generate Habib Metro Statement&rdquo; to preview.
        </div>
        <div id="hmb-rendered-content" class="hidden"></div>
    </div>
</main>

<script>
var clientAccounts = <?= json_encode($accounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function resolveAccount(query) {
    if (!query) return null;
    query = String(query).trim().toLowerCase();
    for (var i = 0; i < clientAccounts.length; i++) {
        var a = clientAccounts[i];
        if (String(a.account_identifier || '').toLowerCase() === query) return a;
        if (String(a.id) === query) return a;
        if (String(a.account_title || '').toLowerCase() === query) return a;
        if (String(a.account_number || '').toLowerCase() === query) return a;
    }
    return null;
}

function populateAccountFields(acc) {
    if (!acc) return;
    $('#hmb-account-id').val(acc.id);
    $('#hmb-account-code-input').val(acc.account_identifier || acc.id);
    $('#hmb-customer-title').val(acc.account_title || '');
    $('#hmb-customer-address').val(acc.address || '');
    $('#hmb-account-number').val(acc.account_number || '');
    $('#hmb-iban').val(acc.iban || '');
    $('#hmb-branch-name').val(acc.branch_name || '');
    $('#hmb-account-type').val(acc.account_type || acc.account_status || '');
    $('#hmb-currency').val(acc.currency || 'PKR');
    $('#hmb-printed-on').val('<?= date('d-M-Y') ?>');
}

$('#hmb-account-code-input').on('change blur', function() {
    var acc = resolveAccount($(this).val());
    if (acc) {
        populateAccountFields(acc);
    }
});

$('#hmb-statement-form').on('submit', function(e) {
    e.preventDefault();
    var accId = $('#hmb-account-id').val();
    if (!accId) {
        var acc = resolveAccount($('#hmb-account-code-input').val());
        if (acc) {
            accId = acc.id;
            $('#hmb-account-id').val(acc.id);
        }
    }

    if (!accId) {
        alert('Please enter or select a valid Account Number / Code.');
        $('#hmb-account-code-input').focus();
        return;
    }

    var payload = {
        account_id: accId,
        start_date: $('#hmb-start-date').val(),
        end_date: $('#hmb-end-date').val(),
        format: 'habibmetro',
        bank_name: 'Habib Metropolitan Bank',
        account_title: $('#hmb-customer-title').val(),
        address: $('#hmb-customer-address').val(),
        branch_name: $('#hmb-branch-name').val(),
        account_type: $('#hmb-account-type').val(),
        account_status: $('#hmb-account-type').val(),
        account_number: $('#hmb-account-number').val(),
        iban: $('#hmb-iban').val(),
        currency: $('#hmb-currency').val(),
        printed_on: $('#hmb-printed-on').val(),
        opening_balance: $('#hmb-opening-balance').val()
    };

    $('#hmb-placeholder').html('<div class="py-12 font-mono text-slate-800 font-bold">Generating Habib Metro Statement...</div>').show();
    $('#hmb-rendered-content').addClass('hidden');

    $.getJSON('/atm/api/statements/generate', payload, function(res) {
        $('#hmb-placeholder').hide();
        if (res.success && res.data && res.data.rendered_html) {
            $('#hmb-rendered-content').html(res.data.rendered_html).removeClass('hidden');
        } else {
            $('#hmb-placeholder').html('<div class="text-red-600 font-bold py-12">' + (res.message || 'Error generating statement.') + '</div>').show();
        }
    }).fail(function() {
        $('#hmb-placeholder').html('<div class="text-red-600 font-bold py-12">Failed to generate report from server.</div>').show();
    });
});

$('#btn-hmb-print').on('click', function() {
    var accId = $('#hmb-account-id').val();
    if (!accId) {
        var acc = resolveAccount($('#hmb-account-code-input').val());
        if (acc) {
            accId = acc.id;
            $('#hmb-account-id').val(acc.id);
        }
    }

    if (!accId) {
        alert('Please enter or select an Account Number / Code.');
        $('#hmb-account-code-input').focus();
        return;
    }

    var params = new URLSearchParams({
        account_id: accId,
        start_date: $('#hmb-start-date').val(),
        end_date: $('#hmb-end-date').val(),
        format: 'habibmetro',
        bank_name: 'Habib Metropolitan Bank',
        account_title: $('#hmb-customer-title').val() || '',
        address: $('#hmb-customer-address').val() || '',
        branch_name: $('#hmb-branch-name').val() || '',
        account_type: $('#hmb-account-type').val() || '',
        account_status: $('#hmb-account-type').val() || '',
        account_number: $('#hmb-account-number').val() || '',
        iban: $('#hmb-iban').val() || '',
        currency: $('#hmb-currency').val() || '',
        printed_on: $('#hmb-printed-on').val() || '',
        opening_balance: $('#hmb-opening-balance').val() || ''
    });

    window.open('/atm/statements/print.php?' + params.toString(), '_blank');
});

// Auto-select on initial load if an account is already selected
$(document).ready(function() {
    var initId = $('#hmb-account-id').val();
    if (initId) {
        var acc = resolveAccount(initId);
        if (acc) {
            populateAccountFields(acc);
            $('#hmb-statement-form').trigger('submit');
        }
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
