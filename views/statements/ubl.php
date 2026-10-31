<!-- 
================================================================================
File: views/statements/ubl.php
Browser URL: http://localhost/atm/statements/ubl.php (or /atm/ubl.php, /atm/ublameen.php)
Description: Dedicated UBL Ameen Islamic Banking Statement Generator (یو بی ایل امین اسلامک بینکنگ مخصوص اسٹیٹمنٹ جنریٹر)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. یہ فائل یو بی ایل امین اسلامک بینکنگ (United Bank Limited / UBL Ameen) کی اسٹیٹمنٹ کا مخصوص اور خود مختار پیج ہے۔
2. یہ بالکل الگ (Isolated) پیج ہے تاکہ دیگر بینکس کے فارمیٹس کو کوئی اثر نہ پہنچے۔
3. اس پیج میں UBL Ameen کے تمام ضروری اور مخصوص فیلڈز شامل ہیں:
   - ٹاپ بینر برانڈنگ (UBL Ameen Islamic Banking)
   - برانچ کوڈ (0053) اور برانچ نام (0053-UBL AMEEN CITY MAIN BR, PWR)
   - پروڈکٹ ٹائپ (AMEEN MUKAMMAL CURRENT ACCOUNT)
   - رجسٹرڈ سیل نمبر (Reg Cell No: 009203089470136)
   - آئی بین اور سی آئی ایف نمبر (IBAN No & CIF#)
   - دستاویز کا عنوان اور برانچ-اکاؤنٹ نمبر (Account Statement 0053-000344327894)
   - انسٹرومنٹ نمبر کالم کے ساتھ مکمل ٹرانزیکشن ٹیبل (Inst No.)
   - رننگ بیلنس کے ساتھ Cr لاحقہ
4. اگر آپ پرنٹ ڈیزائن میں ترمیم کرنا چاہیں تو فائل:
   app/Services/Statement/UBLStatementRenderer.php
   کو ایڈٹ کریں۔
================================================================================
-->
<?php 
$pageTitle = 'UBL Ameen Islamic Banking — Statement Generator'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 

$activeBankName = 'United Bank Limited';
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-3">
    <!-- Desktop Dialog Frame matching Application Windows Theme -->
    <div class="win-window mb-4">
        <!-- Titlebar -->
        <div class="win-titlebar bg-gradient-to-r from-[#003b70] to-[#025a9e] text-white flex items-center justify-between px-3 py-1.5 border-b border-[#00274d]">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-bold tracking-wide text-xs">United Bank Limited (UBL Ameen) — Statement Generator</span>
            </div>
            <div class="flex items-center space-x-1">
                <a href="/atm/statements.php" class="text-[11px] bg-sky-950/80 hover:bg-sky-900 text-sky-100 px-2 py-0.5 rounded border border-sky-400/30">
                    ← All Statements
                </a>
            </div>
        </div>

        <!-- UBL Header Banner -->
        <div class="bg-[#f8fafc] border-b border-[#cbd5e1] px-4 py-2 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded bg-[#004b87] flex items-center justify-center text-white font-bold text-base shadow-xs">
                    UBL
                </div>
                <div>
                    <h2 class="font-bold text-[#003b70] text-sm leading-tight">UBL Ameen Islamic Banking Statement Portal</h2>
                    <p class="text-[11px] text-gray-500">Dedicated statement generation for UBL Ameen accounts with authentic top banner, Inst No. column, customer metadata grid and running balance Cr suffix.</p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="inline-block bg-sky-50 text-[#003b70] text-[10px] font-bold px-2 py-0.5 rounded border border-sky-200 font-mono">
                    FORMAT: ubl (A4 Institutional)
                </span>
            </div>
        </div>

        <!-- Form Body -->
        <div class="p-3 sm:p-4 bg-[#f1f5f9]">
            <form id="ubl-statement-form" class="space-y-3">
                <input type="hidden" id="ubl-account-id" name="account_id" value="<?= e($selectedAccountId ?? '') ?>">
                <input type="hidden" name="format" value="ubl">

                <!-- Primary Account Selector Row -->
                <div class="bg-white p-2.5 rounded border border-[#cbd5e1] shadow-xs flex flex-wrap items-center gap-3">
                    <label for="ubl-account-code-input" class="font-black text-[#003b70] text-xs whitespace-nowrap">
                        Account Code / Number :
                    </label>
                    <div class="flex-1 min-w-[200px] relative">
                        <input type="text" id="ubl-account-code-input" list="ubl-account-options" placeholder="Search account number or title..." class="win-input w-full font-mono font-bold text-xs" autocomplete="off">
                        <datalist id="ubl-account-options">
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= e($acc['account_identifier'] ?: $acc['id']) ?>" 
                                        data-id="<?= e($acc['id']) ?>"
                                        data-acc-no="<?= e($acc['account_number'] ?? '') ?>"
                                        data-bank="<?= e($acc['bank_name'] ?? '') ?>"
                                        data-title="<?= e($acc['account_title'] ?? '') ?>">
                                    <?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?> - <?= e($acc['account_number'] ?: 'No Acc#') ?>)
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <!-- 2-Column Institutional Customization Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-white p-3 rounded border border-[#cbd5e1] shadow-xs">
                    <!-- Left Column: Customer & Contact Details -->
                    <div class="space-y-2">
                        <div class="font-bold text-[#003b70] border-b border-gray-200 pb-1 flex items-center justify-between">
                            <span>Customer & Identification Details</span>
                            <span class="text-[10px] text-gray-500 font-normal">Editable</span>
                        </div>

                        <!-- Customer Title -->
                        <div class="flex items-start">
                            <label for="ubl-customer-title" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Account Title</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-customer-title" name="account_title" placeholder="" class="win-input w-full font-bold text-xs uppercase">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. FARHAN KHAN</div>
                            </div>
                        </div>

                        <!-- Customer Street Address -->
                        <div class="flex items-start">
                            <label for="ubl-customer-address" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Street Address</label>
                            <div class="flex-1">
                                <textarea id="ubl-customer-address" name="address" rows="2" placeholder="" class="win-input w-full text-xs resize-none uppercase"></textarea>
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. CHARSADA ROAD MOH DIN BAHAR COLONY PESHAWAR</div>
                            </div>
                        </div>

                        <!-- Reg Cell No -->
                        <div class="flex items-start">
                            <label for="ubl-reg-cell-no" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Reg Cell No</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-reg-cell-no" name="reg_cell_no" value="009203089470136" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 009203089470136</div>
                            </div>
                        </div>

                        <!-- IBAN No -->
                        <div class="flex items-start">
                            <label for="ubl-iban" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">IBAN No</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-iban" name="iban" value="PK81 UNIL 0109 0003 4432 7894" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. PK81 UNIL 0109 0003 4432 7894</div>
                            </div>
                        </div>

                        <!-- CIF# -->
                        <div class="flex items-start">
                            <label for="ubl-cif-no" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">CIF#</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-cif-no" name="cif_no" value="47187224" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 47187224</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Account & Branch Specifics -->
                    <div class="space-y-2">
                        <div class="font-bold text-[#003b70] border-b border-gray-200 pb-1 flex items-center justify-between">
                            <span>Account Details & Date Range</span>
                            <span class="text-[10px] text-gray-500 font-normal">Editable</span>
                        </div>

                        <!-- Account Number -->
                        <div class="flex items-start">
                            <label for="ubl-account-number" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Account No</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-account-number" name="account_number" placeholder="" class="win-input w-full font-mono font-bold text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 000344327894</div>
                            </div>
                        </div>

                        <!-- Branch Code -->
                        <div class="flex items-start">
                            <label for="ubl-branch-code" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Branch Code</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-branch-code" name="branch_code" value="0053" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 0053</div>
                            </div>
                        </div>

                        <!-- Branch Name -->
                        <div class="flex items-start">
                            <label for="ubl-branch-name" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Branch Name</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-branch-name" name="branch_name" value="0053-UBL AMEEN CITY MAIN BR, PWR" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 0053-UBL AMEEN CITY MAIN BR, PWR</div>
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div class="flex items-start">
                            <label for="ubl-account-type" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Account Type</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-account-type" name="account_type" value="CURRENT" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. CURRENT</div>
                            </div>
                        </div>

                        <!-- Product Type -->
                        <div class="flex items-start">
                            <label for="ubl-product-type" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Product Type</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-product-type" name="product_type" value="AMEEN MUKAMMAL CURRENT ACCOUNT" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. AMEEN MUKAMMAL CURRENT ACCOUNT</div>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div class="flex items-start">
                            <label for="ubl-currency" class="font-bold text-[#1e293b] w-36 whitespace-nowrap pt-1">Currency</label>
                            <div class="flex-1">
                                <input type="text" id="ubl-currency" name="currency" value="PAKISTANI RUPEE" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. PAKISTANI RUPEE</div>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="flex items-center">
                            <label for="ubl-start-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Date From</label>
                            <input type="date" id="ubl-start-date" name="start_date" value="<?= e($startDate ?? date('Y-01-01')) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- End Date -->
                        <div class="flex items-center">
                            <label for="ubl-end-date" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Date To</label>
                            <input type="date" id="ubl-end-date" name="end_date" value="<?= e($endDate ?? date('Y-m-d')) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Opening Balance -->
                        <div class="flex items-center">
                            <label for="ubl-opening-balance" class="font-bold text-[#1e293b] w-36 whitespace-nowrap">Opening Balance</label>
                            <input type="text" inputmode="decimal" id="ubl-opening-balance" name="opening_balance" value="0.00" class="win-input flex-1 font-mono font-bold text-xs">
                        </div>
                    </div>
                </div>

                <!-- Submit / Print Toolbar -->
                <div class="flex items-center justify-between pt-2">
                    <div class="text-[11px] text-gray-500">
                        * All institutional metadata will be rendered strictly matching UBL Ameen sample format.
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" id="btn-ubl-preview" class="win-btn font-bold text-blue-900 px-6 py-1 text-xs">
                            Generate Screen Preview
                        </button>
                        <button type="button" id="btn-ubl-print" class="win-btn font-bold text-emerald-800 px-6 py-1 text-xs">
                            🖨 Print / PDF View
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Preview Container -->
    <div class="win-window">
        <div class="win-titlebar bg-slate-700 text-white flex items-center justify-between px-3 py-1 text-xs">
            <span class="font-bold">Live Statement Output Preview (A4 View)</span>
            <span class="text-[10px] text-slate-300">Exact layout representation</span>
        </div>
        <div class="p-4 bg-slate-200 overflow-x-auto min-h-[300px] flex justify-center">
            <div id="ubl-preview-placeholder" class="text-gray-500 font-mono py-12 text-center text-xs">
                Select an account and click "Generate Screen Preview" above to preview statement.
            </div>
            <div id="ubl-preview-content" class="w-full hidden"></div>
        </div>
    </div>
</main>

<script>
var accountsList = <?= json_encode($accounts ?? []) ?>;

function resolveUblAccount() {
    var raw = $('#ubl-account-code-input').val().trim();
    if (!raw) return null;
    return accountsList.find(function(a) {
        return String(a.account_identifier).toLowerCase() === raw.toLowerCase() ||
               String(a.id) === raw ||
               String(a.account_number).toLowerCase() === raw.toLowerCase();
    }) || null;
}

function populateUblFields(acc) {
    if (!acc) return;
    $('#ubl-account-id').val(acc.id);
    $('#ubl-customer-title').val(acc.account_title || '');
    $('#ubl-customer-address').val(acc.address || '');
    $('#ubl-account-number').val(acc.account_number || '');
    $('#ubl-iban').val(acc.iban || 'PK81 UNIL 0109 0003 4432 7894');
    $('#ubl-branch-name').val(acc.branch_name || '0053-UBL AMEEN CITY MAIN BR, PWR');
    $('#ubl-branch-code').val(acc.branch_code || '0053');
    $('#ubl-account-type').val(acc.account_type || 'CURRENT');
    $('#ubl-product-type').val(acc.product || 'AMEEN MUKAMMAL CURRENT ACCOUNT');
    $('#ubl-currency').val(acc.currency || 'PAKISTANI RUPEE');
    $('#ubl-reg-cell-no').val(acc.phone1 || '009203089470136');
    $('#ubl-cif-no').val('47187224');
    
    if (acc.max_tx_date && (!$('#ubl-end-date').val() || acc.max_tx_date > $('#ubl-end-date').val())) {
        $('#ubl-end-date').val(acc.max_tx_date);
    }
    
    var initBal = parseFloat(acc.initial_balance || 0);
    $('#ubl-opening-balance').val(initBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}

$('#ubl-account-code-input').on('input change', function() {
    var acc = resolveUblAccount();
    if (acc) {
        populateUblFields(acc);
    }
});

function getUblFormData() {
    var acc = resolveUblAccount();
    var accId = acc ? acc.id : $('#ubl-account-id').val();
    if (!accId) {
        alert('Please select or enter an Account Number first.');
        $('#ubl-account-code-input').focus();
        return null;
    }

    return {
        account_id: accId,
        start_date: $('#ubl-start-date').val(),
        end_date: $('#ubl-end-date').val(),
        format: 'ubl',
        bank_name: 'United Bank Limited',
        account_title: $('#ubl-customer-title').val(),
        address: $('#ubl-customer-address').val(),
        account_number: $('#ubl-account-number').val(),
        iban: $('#ubl-iban').val(),
        branch_name: $('#ubl-branch-name').val() || '0053-UBL AMEEN CITY MAIN BR, PWR',
        branch_code: $('#ubl-branch-code').val() || '0053',
        account_type: $('#ubl-account-type').val() || 'CURRENT',
        account_status: $('#ubl-account-type').val() || 'CURRENT',
        product: $('#ubl-product-type').val() || 'AMEEN MUKAMMAL CURRENT ACCOUNT',
        product_type: $('#ubl-product-type').val() || 'AMEEN MUKAMMAL CURRENT ACCOUNT',
        currency: $('#ubl-currency').val() || 'PAKISTANI RUPEE',
        reg_cell_no: $('#ubl-reg-cell-no').val() || '009203089470136',
        cif_no: $('#ubl-cif-no').val() || '47187224',
        opening_balance: $('#ubl-opening-balance').val() || '0.00'
    };
}

// Screen Preview
$('#ubl-statement-form').on('submit', function(e) {
    e.preventDefault();
    var data = getUblFormData();
    if (!data) return;

    $('#ubl-preview-placeholder').html('<div class="py-8 font-bold text-[#003b70]">Generating UBL Ameen Statement...</div>').show();
    $('#ubl-preview-content').addClass('hidden');

    $.getJSON('/atm/api/statements/generate', data, function(res) {
        $('#ubl-preview-placeholder').hide();
        if (res.success && res.data && res.data.rendered_html) {
            $('#ubl-preview-content').html(res.data.rendered_html).removeClass('hidden');
        } else {
            $('#ubl-preview-placeholder').html('<div class="text-red-600 font-bold py-8">' + (res.message || 'Error generating statement.') + '</div>').show();
        }
    }).fail(function() {
        $('#ubl-preview-placeholder').html('<div class="text-red-600 font-bold py-8">Failed to generate report from server.</div>').show();
    });
});

// Print View
$('#btn-ubl-print').on('click', function() {
    var data = getUblFormData();
    if (!data) return;
    var params = new URLSearchParams(data);
    window.open('/atm/statements/print.php?' + params.toString(), '_blank');
});

// Auto-select initial account if available
$(document).ready(function() {
    var initAccId = $('#ubl-account-id').val();
    if (initAccId) {
        var match = accountsList.find(function(a) { return String(a.id) === String(initAccId); });
        if (match) {
            $('#ubl-account-code-input').val(match.account_identifier || match.id);
            populateUblFields(match);
        }
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
