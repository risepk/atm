<!-- 
================================================================================
File: views/statements/view.php
Browser URL: http://localhost/atm/statements.php (or /atm/statements)
Description: Bank Statements Generator Screen (بینک اسٹیٹمنٹس جنریٹر اور فارم)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. یہ فائل بینک اسٹیٹمنٹ جنریشن کی مرکزی ونڈو (Dialog) ہے۔
2. یہاں سے صارف اکاؤنٹ منتخب کرتا ہے، تاریخیں درج کرتا ہے اور "Generate Statement" پر کلک کرتا ہے۔
3. "Print View" بٹن کے ذریعے نیا ٹیب کھلتا ہے جو /atm/statements/print.php پر پرنٹ فارمیٹ کھولتا ہے۔
4. اگر آپ نے فارم کے فیلڈز (جیسے Account No, IBAN, Address وغیرہ) کا ڈیزائن بدلنا ہو تو
   اسی فائل کے اندر HTML اور نیچے دیے گئے jQuery اسکرپٹ کو ایڈٹ کریں۔
================================================================================
-->
<?php 
$pageTitle = 'Bank Statements & Reports'; 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/navigation.php'; 

$masterBankNames = !empty($masterBanks) ? array_column($masterBanks, 'bank_name') : [];
$accountBankNames = !empty($accounts) ? array_column($accounts, 'bank_name') : [];
$uniqueBanks = array_values(array_unique(array_filter(array_merge($masterBankNames, $accountBankNames))));
sort($uniqueBanks);

$activeBankName = $selectedBank ?: 'All Reports';
if ($selectedBank) {
    foreach ($uniqueBanks as $ub) {
        if (strcasecmp($ub, $selectedBank) === 0 || stripos($ub, $selectedBank) !== false) {
            $activeBankName = $ub;
            break;
        }
    }
} elseif (!empty($accounts)) {
    // If an account is selected, use its bank
    foreach ($accounts as $a) {
        if ((int)$a['id'] === (int)$selectedAccountId) {
            $activeBankName = $a['bank_name'] ?: 'All Reports';
            break;
        }
    }
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-3">
    <!-- Desktop Dialog Frame matching Screenshot 3 (All Reports / Bank Dialog) -->
    <div class="win-window mb-4">
        <!-- Titlebar with Active Bank Name -->
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span id="dialog-bank-title"><?= e($activeBankName) ?></span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <!-- Dialog Tabs: [ Search ] | [ Report ] -->
        <div class="bg-[#d9e6f5] border-b border-[#aec4de] px-3 pt-1.5 flex items-center space-x-1 text-xs font-bold select-none">
            <button type="button" id="tab-btn-report" onclick="switchTab('report')" class="px-4 py-1 rounded-t border-t border-l border-r border-[#aec4de] bg-[#f2f6fb] text-[#1c3a63] shadow-xs">
                Report
            </button>
            <button type="button" id="tab-btn-search" onclick="switchTab('search')" class="px-4 py-1 rounded-t hover:bg-[#e7eff9] text-gray-700">
                Search
            </button>
        </div>

        <!-- Tab 1: Report Parameter Form (Screenshot 3 Exact 2-Column Layout) -->
        <div id="tab-content-report" class="p-4 bg-[#eceff4] text-xs text-[#111827]">
            <form id="statement-filter-form" class="space-y-3">
                <input type="hidden" id="stmt-account-id" name="account_id" value="<?= e($selectedAccountId) ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                    <!-- Left Column -->
                    <div class="space-y-2">
                        <!-- Bank Name -->
                        <div class="flex items-center">
                            <label for="stmt-bank-name" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Bank Name</label>
                            <select id="stmt-bank-name" class="win-input flex-1 bg-white font-medium text-xs">
                                <option value="">-- All Banks --</option>
                                <?php foreach ($uniqueBanks as $bName): ?>
                                    <option value="<?= e($bName) ?>" <?= (strcasecmp($activeBankName, $bName) === 0) ? 'selected' : '' ?>>
                                        <?= e($bName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Account Number (Identifier, e.g. 57, 13, 12) -->
                        <div class="flex items-center">
                            <label for="stmt-account-number-input" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Account Number</label>
                            <input type="text" id="stmt-account-number-input" list="statement-acc-datalist" placeholder="Enter Account Code / No (e.g. 57, 12, 13)" class="win-input flex-1 font-mono font-bold text-blue-950 text-xs">
                            <datalist id="statement-acc-datalist">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= e($acc['account_identifier'] ?? $acc['id']) ?>"><?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Name / F.Name -->
                        <div class="flex items-center">
                            <label for="stmt-customer-name" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Name/F.Name</label>
                            <input type="text" id="stmt-customer-name" name="account_title" placeholder="Customer / Father Name" class="win-input flex-1 text-xs">
                        </div>

                        <!-- Account No (Actual bank account number) -->
                        <div class="flex items-center">
                            <label for="stmt-actual-acc-no" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Account No</label>
                            <input type="text" id="stmt-actual-acc-no" name="account_number" placeholder="e.g. 0417-79011691-01" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Date From -->
                        <div class="flex items-center">
                            <label for="stmt-start" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Date From :</label>
                            <input type="date" id="stmt-start" name="start_date" value="<?= e($startDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Date To -->
                        <div class="flex items-center">
                            <label for="stmt-end" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Date To :</label>
                            <input type="date" id="stmt-end" name="end_date" value="<?= e($endDate) ?>" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Show Online Checkbox (Key Client Requirement) -->
                        <div class="flex items-center pt-1">
                            <span class="w-32"></span>
                            <label class="inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="stmt-show-online" name="show_online" value="1" class="h-4 w-4 text-blue-600 rounded border-gray-400">
                                <span class="ml-2 font-bold text-[#1a385f] text-xs">Show Online (E-Statement Format)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-2">
                        <!-- Address -->
                        <div class="flex items-start">
                            <label for="stmt-address" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Address</label>
                            <textarea id="stmt-address" name="address" rows="2" placeholder="Customer address or branch details" class="win-input flex-1 text-xs resize-none"></textarea>
                        </div>

                        <!-- Account Type -->
                        <div class="flex items-center">
                            <label for="stmt-account-type" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Account Type</label>
                            <input type="text" id="stmt-account-type" name="account_type" placeholder="e.g. Current Account" class="win-input flex-1 text-xs">
                        </div>

                        <!-- Opening Balance -->
                        <div class="flex items-center">
                            <label for="stmt-opening-balance" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Opening Balance</label>
                            <input type="text" inputmode="decimal" id="stmt-opening-balance" name="opening_balance" placeholder="0.00" class="win-input flex-1 font-mono font-bold text-xs">
                        </div>

                        <!-- IBAN -->
                        <div class="flex items-center">
                            <label for="stmt-iban" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">IBAN</label>
                            <input type="text" id="stmt-iban" name="iban" placeholder="e.g. PK36ALFH0000000000000000" class="win-input flex-1 font-mono text-xs">
                        </div>

                        <!-- Hidden format select for standard/hbl resolution -->
                        <input type="hidden" id="stmt-format" name="format" value="<?= e($formatCode) ?>">
                    </div>
                </div>

                <!-- Centered [ Generate Report ] Button (Screenshot 3 Reference) -->
                <div class="flex items-center justify-center space-x-3 pt-3 border-t border-[#cbd9e8]">
                    <button type="submit" id="btn-generate-report" class="win-btn font-black text-blue-950 px-8 py-1 text-xs">
                        Generate Report
                    </button>
                    <button type="button" id="btn-print-view" class="win-btn font-bold text-[#1a385f] px-4 py-1 text-xs">
                        🖨 Print / PDF View
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab 2: Search (Quick Transaction Search) -->
        <div id="tab-content-search" class="hidden p-4 bg-[#eceff4] text-xs text-[#111827] space-y-3">
            <div class="flex items-center space-x-2">
                <input type="text" id="search-keyword" placeholder="Search transactions by narration, cheque #, or amount..." class="win-input flex-1 text-xs">
                <button type="button" id="btn-search-exec" class="win-btn font-bold text-blue-950 px-4">
                    Search Ledger
                </button>
            </div>
            <div id="search-results-box" class="bg-white border border-[#8caecf] p-3 max-h-60 overflow-y-auto font-mono text-xs">
                <span class="text-gray-400 italic">Enter a keyword and click Search Ledger.</span>
            </div>
        </div>
    </div>

    <!-- Statement Render Preview Container -->
    <div id="statement-output-panel" class="win-window bg-white p-4 sm:p-6 min-h-[350px]">
        <div id="output-placeholder" class="text-center py-16 text-gray-400 font-mono text-xs">
            <p class="font-bold text-gray-600 text-sm mb-1">Statement Preview Window</p>
            <p>Select an Account Number above and click "Generate Report".</p>
        </div>
        <div id="statement-rendered-content" class="hidden"></div>
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

function switchTab(tab) {
    if (tab === 'report') {
        $('#tab-btn-report').removeClass('hover:bg-[#e7eff9] text-gray-700').addClass('bg-[#f2f6fb] text-[#1c3a63] border-t border-l border-r border-[#aec4de] shadow-xs');
        $('#tab-btn-search').removeClass('bg-[#f2f6fb] text-[#1c3a63] border-t border-l border-r border-[#aec4de] shadow-xs').addClass('hover:bg-[#e7eff9] text-gray-700');
        $('#tab-content-report').removeClass('hidden');
        $('#tab-content-search').addClass('hidden');
    } else {
        $('#tab-btn-search').removeClass('hover:bg-[#e7eff9] text-gray-700').addClass('bg-[#f2f6fb] text-[#1c3a63] border-t border-l border-r border-[#aec4de] shadow-xs');
        $('#tab-btn-report').removeClass('bg-[#f2f6fb] text-[#1c3a63] border-t border-l border-r border-[#aec4de] shadow-xs').addClass('hover:bg-[#e7eff9] text-gray-700');
        $('#tab-content-search').removeClass('hidden');
        $('#tab-content-report').addClass('hidden');
    }
}

function resolveAccountFromInput() {
    var q = $.trim($('#stmt-account-number-input').val()).toLowerCase();
    if (!q) return null;
    if (accountsByIdentifier[q]) return accountsByIdentifier[q];
    var match = accountsList.find(function(a) {
        return String(a.account_title).toLowerCase() === q || String(a.account_number).toLowerCase() === q;
    });
    return match || null;
}

var manualBankSelected = !!$('#stmt-bank-name').val();

function populateAccountFields(acc) {
    if (!acc) return;
    $('#stmt-account-id').val(acc.id);
    $('#stmt-customer-name').val(acc.account_title || '');
    $('#stmt-actual-acc-no').val(acc.account_number || '');
    $('#stmt-address').val(acc.address || '');
    $('#stmt-account-type').val(acc.account_type || 'Current Account');
    $('#stmt-opening-balance').val(parseFloat(acc.initial_balance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#stmt-iban').val(acc.iban || '');
    if (acc.bank_name && (!manualBankSelected || !$('#stmt-bank-name').val())) {
        $('#stmt-bank-name').val(acc.bank_name);
        $('#dialog-bank-title').text(acc.bank_name);
    }
}

$('#stmt-account-number-input').on('input change', function() {
    var acc = resolveAccountFromInput();
    if (acc) {
        populateAccountFields(acc);
    }
});

$('#stmt-bank-name').on('change', function() {
    var bName = $(this).val();
    $('#dialog-bank-title').text(bName || 'All Reports');
    manualBankSelected = !!bName;
    // If no account is selected, try finding first account with this bank
    var currentAcc = resolveAccountFromInput();
    if (!currentAcc && bName) {
        var match = accountsList.find(function(a) { 
            return String(a.bank_name).toLowerCase() === bName.toLowerCase() ||
                   bName.toLowerCase().indexOf(String(a.bank_name).toLowerCase()) !== -1;
        });
        if (match) {
            $('#stmt-account-number-input').val(match.account_identifier || match.id);
            populateAccountFields(match);
        }
    }
});

$('#statement-filter-form').on('submit', function(e) {
    e.preventDefault();
    generateStatementReport();
});

function generateStatementReport() {
    var acc = resolveAccountFromInput();
    var accId = acc ? acc.id : $('#stmt-account-id').val();

    if (!accId) {
        alert('Please enter a valid Account Number / Code.');
        $('#stmt-account-number-input').focus();
        return;
    }

    var showOnline = $('#stmt-show-online').is(':checked') ? '1' : '0';
    var selectedBank = $('#stmt-bank-name').val() || (acc ? acc.bank_name : '') || '';
    var bankLower = selectedBank.toLowerCase();
    var format = 'standard';
    if (showOnline === '1') {
        format = 'online';
    } else if (bankLower.indexOf('faysal') !== -1 || bankLower.indexOf('faisal') !== -1 || bankLower.indexOf('fbl') !== -1) {
        format = 'faysal';
    } else if (bankLower.indexOf('hbl') !== -1 || bankLower.indexOf('habib bank') !== -1) {
        format = 'hbl';
    }

    var payload = {
        account_id: accId,
        start_date: $('#stmt-start').val(),
        end_date: $('#stmt-end').val(),
        format: format,
        bank_name: selectedBank,
        show_online: showOnline,
        account_title: $('#stmt-customer-name').val(),
        father_name: '',
        account_number: $('#stmt-actual-acc-no').val(),
        address: $('#stmt-address').val(),
        account_type: $('#stmt-account-type').val(),
        iban: $('#stmt-iban').val(),
        opening_balance: $('#stmt-opening-balance').val()
    };

    $('#output-placeholder').html('<div class="py-8 font-mono text-blue-900 font-bold">Generating ' + (showOnline === '1' ? 'Online E-Statement' : 'Bank Statement') + '...</div>').show();
    $('#statement-rendered-content').addClass('hidden');

    $.getJSON('/atm/api/statements/generate', payload, function(res) {
        $('#output-placeholder').hide();
        if (res.success && res.data && res.data.rendered_html) {
            $('#statement-rendered-content').html(res.data.rendered_html).removeClass('hidden');
        } else {
            $('#output-placeholder').html('<div class="text-red-600 font-bold py-8">' + (res.message || 'Error generating statement.') + '</div>').show();
        }
    }).fail(function() {
        $('#output-placeholder').html('<div class="text-red-600 font-bold py-8">Failed to generate report from server.</div>').show();
    });
}

// Print / PDF view
$('#btn-print-view').on('click', function() {
    var acc = resolveAccountFromInput();
    var accId = acc ? acc.id : $('#stmt-account-id').val();
    if (!accId) {
        alert('Please enter or select an Account Number first.');
        return;
    }

    var showOnline = $('#stmt-show-online').is(':checked') ? '1' : '0';
    var selectedBank = $('#stmt-bank-name').val() || (acc ? acc.bank_name : '') || '';
    var bankLower = selectedBank.toLowerCase();
    var format = 'standard';
    if (showOnline === '1') {
        format = 'online';
    } else if (bankLower.indexOf('faysal') !== -1 || bankLower.indexOf('faisal') !== -1 || bankLower.indexOf('fbl') !== -1) {
        format = 'faysal';
    } else if (bankLower.indexOf('hbl') !== -1 || bankLower.indexOf('habib bank') !== -1) {
        format = 'hbl';
    }

    var params = new URLSearchParams({
        account_id: accId,
        start_date: $('#stmt-start').val(),
        end_date: $('#stmt-end').val(),
        format: format,
        bank_name: selectedBank,
        show_online: showOnline,
        account_title: $('#stmt-customer-name').val() || '',
        account_number: $('#stmt-actual-acc-no').val() || '',
        address: $('#stmt-address').val() || '',
        account_type: $('#stmt-account-type').val() || '',
        iban: $('#stmt-iban').val() || '',
        opening_balance: $('#stmt-opening-balance').val() || ''
    });

    window.open('/atm/statements/print.php?' + params.toString(), '_blank');
});

// Search Tab Ledger Query
$('#btn-search-exec').on('click', function() {
    var kw = $.trim($('#search-keyword').val());
    if (!kw) return;
    $('#search-results-box').html('Searching...');
    $.getJSON('/atm/api/transactions/filter', { search: kw, page: 1 }, function(res) {
        if (res.success && res.data) {
            var rows = res.data.data || [];
            if (rows.length === 0) {
                $('#search-results-box').html('<span class="text-gray-500 italic">No matching transactions found for "' + escapeHtml(kw) + '".</span>');
                return;
            }
            var html = '<table class="w-full win-table"><thead><tr><th>ID</th><th>Date</th><th>Particulars</th><th>Debit</th><th>Credit</th></tr></thead><tbody>';
            rows.forEach(function(r) {
                html += '<tr><td>' + r.id + '</td><td>' + r.transaction_date + '</td><td>' + escapeHtml(r.narration) + '</td><td>' + r.debit + '</td><td>' + r.credit + '</td></tr>';
            });
            html += '</tbody></table>';
            $('#search-results-box').html(html);
        }
    });
});

$(function() {
    var initId = <?= (int)($selectedAccountId ?: 0) ?>;
    if (initId > 0 && accountsById[initId]) {
        var acc = accountsById[initId];
        $('#stmt-account-number-input').val(acc.account_identifier || acc.id);
        populateAccountFields(acc);
        generateStatementReport();
    } else {
        var initBank = '<?= addslashes($selectedBank) ?>';
        if (initBank) {
            var match = accountsList.find(function(a) { 
                return String(a.bank_name).toLowerCase() === initBank.toLowerCase() ||
                       initBank.toLowerCase().indexOf(String(a.bank_name).toLowerCase()) !== -1 ||
                       String(a.bank_name).toLowerCase().indexOf(initBank.toLowerCase()) !== -1;
            });
            if (match) {
                $('#stmt-account-number-input').val(match.account_identifier || match.id);
                populateAccountFields(match);
                generateStatementReport();
            }
        }
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
