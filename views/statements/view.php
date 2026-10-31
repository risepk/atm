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
                            <label for="stmt-account-number-input" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Account Code</label>
                            <input type="text" id="stmt-account-number-input" list="statement-acc-datalist" placeholder="Select Account Code / No" class="win-input flex-1 font-mono font-bold text-blue-950 text-xs">
                            <datalist id="statement-acc-datalist">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= e($acc['account_identifier'] ?? $acc['id']) ?>"><?= e($acc['account_title']) ?> (<?= e($acc['bank_name']) ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Customer Title / Name -->
                        <div class="flex items-start">
                            <label for="stmt-customer-name" id="lbl-customer-name" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Account Title</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-customer-name" name="account_title" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-customer-name" class="text-[10px] text-gray-500 mt-0.5">e.g. Customer / Account Title</div>
                            </div>
                        </div>

                        <!-- Address / Street -->
                        <div id="row-address" class="flex items-start">
                            <label for="stmt-address" id="lbl-address" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Address</label>
                            <div class="flex-1">
                                <textarea id="stmt-address" name="address" rows="2" placeholder="" class="win-input w-full text-xs resize-none"></textarea>
                                <div id="desc-address" class="text-[10px] text-gray-500 mt-0.5">e.g. Street / Area Address</div>
                            </div>
                        </div>

                        <!-- City (ABL Specific / Viewable) -->
                        <div id="row-city" class="flex items-start">
                            <label for="stmt-city" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">City</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-city" name="city" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. PESHAWAR / LAHORE / KARACHI</div>
                            </div>
                        </div>

                        <!-- Postal Code (ABL Specific / Viewable) -->
                        <div id="row-postal-code" class="flex items-start">
                            <label for="stmt-postal-code" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Postal Code</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-postal-code" name="postal_code" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 25000 / 75500</div>
                            </div>
                        </div>

                        <!-- Province & Country (ABL Specific / Viewable) -->
                        <div id="row-province-country" class="flex items-start">
                            <label for="stmt-province-country" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Province/Country</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-province-country" name="province_country" placeholder="" class="win-input w-full text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. KHYBER PAKHTUNKHWA PAKISTAN</div>
                            </div>
                        </div>

                        <!-- Mobile Number 1 (ABL Specific / Viewable) -->
                        <div id="row-phone1" class="flex items-start">
                            <label for="stmt-phone1" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Mobile 1</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-phone1" name="phone1" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 03001234567</div>
                            </div>
                        </div>

                        <!-- Mobile Number 2 (ABL Specific / Viewable) -->
                        <div id="row-phone2" class="flex items-start">
                            <label for="stmt-phone2" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Mobile 2</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-phone2" name="phone2" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">e.g. 03219876543</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-2">
                        <!-- Branch Name -->
                        <div id="row-branch-name" class="flex items-start">
                            <label for="stmt-branch-name" id="lbl-branch-name" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Branch Name</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-branch-name" name="branch_name" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-branch-name" class="text-[10px] text-gray-500 mt-0.5">e.g. MAIN BRANCH</div>
                            </div>
                        </div>

                        <!-- Branch Address (Meezan Specific / Viewable) -->
                        <div id="row-branch-address" class="hidden flex items-start">
                            <label for="stmt-branch-address" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Branch Address</label>
                            <div class="flex-1">
                                <textarea id="stmt-branch-address" name="branch_address" rows="2" placeholder="" class="win-input w-full text-xs resize-none"></textarea>
                                <div id="desc-branch-address" class="text-[10px] text-gray-500 mt-0.5">e.g. PLOT NO Q-13 COLLAGE ROAD F-7 ISLAMABAD</div>
                            </div>
                        </div>

                        <!-- Account No (Actual bank account number) -->
                        <div class="flex items-start">
                            <label for="stmt-actual-acc-no" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Account No</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-actual-acc-no" name="account_number" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-actual-acc-no" class="text-[10px] text-gray-500 mt-0.5">e.g. 01234567890123</div>
                            </div>
                        </div>

                        <!-- Old Account No (Meezan Specific / Viewable) -->
                        <div id="row-old-account-no" class="hidden flex items-start">
                            <label for="stmt-old-account-no" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Old Account No</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-old-account-no" name="old_account_no" placeholder="" class="win-input w-full font-mono text-xs">
                                <div class="text-[10px] text-gray-500 mt-0.5">Optional Old Account No</div>
                            </div>
                        </div>

                        <!-- Account Status / Deposit Type / Product -->
                        <div id="row-account-status" class="flex items-start">
                            <label for="stmt-account-status" id="lbl-account-status" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Account Status</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-account-status" name="account_status" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-account-status" class="text-[10px] text-gray-500 mt-0.5">e.g. Regular / Current</div>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div id="row-currency" class="flex items-start">
                            <label for="stmt-currency" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Currency</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-currency" name="currency" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-currency" class="text-[10px] text-gray-500 mt-0.5">e.g. Pakistan Rupee or PKR</div>
                            </div>
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

                        <!-- Opening Balance / Balance at Period Start -->
                        <div class="flex items-center">
                            <label for="stmt-opening-balance" id="lbl-opening-balance" class="font-bold text-[#1a385f] w-32 whitespace-nowrap">Opening Balance</label>
                            <input type="text" inputmode="decimal" id="stmt-opening-balance" name="opening_balance" placeholder="0.00" class="win-input flex-1 font-mono font-bold text-xs">
                        </div>

                        <!-- IBAN -->
                        <div id="row-iban" class="flex items-start">
                            <label for="stmt-iban" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">IBAN</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-iban" name="iban" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-iban" class="text-[10px] text-gray-500 mt-0.5">e.g. PK36ALFH0000000000000000</div>
                            </div>
                        </div>

                        <!-- Generated By (Meezan Specific / Viewable) -->
                        <div id="row-generated-by" class="hidden flex items-start">
                            <label for="stmt-generated-by" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Generated By</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-generated-by" name="generated_by" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-generated-by" class="text-[10px] text-gray-500 mt-0.5">e.g. MOIN.31188</div>
                            </div>
                        </div>

                        <!-- Print Date (Meezan Specific / Viewable) -->
                        <div id="row-print-date" class="hidden flex items-start">
                            <label for="stmt-print-date" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Print Date</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-print-date" name="print_date" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-print-date" class="text-[10px] text-gray-500 mt-0.5">e.g. <?= date('d M Y H:i:s') ?></div>
                            </div>
                        </div>

                        <!-- Frequency (HBL Specific / Viewable) -->
                        <div id="row-hbl-frequency" class="hidden flex items-start">
                            <label for="stmt-hbl-frequency" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Frequency</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-hbl-frequency" name="frequency" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-hbl-frequency" class="text-[10px] text-gray-500 mt-0.5">e.g. INTERIM / DUPLICATE</div>
                            </div>
                        </div>

                        <!-- Printing Date (HBL Specific / Viewable) -->
                        <div id="row-hbl-printing-date" class="hidden flex items-start">
                            <label for="stmt-hbl-printing-date" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Printing Date</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-hbl-printing-date" name="printing_date" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-hbl-printing-date" class="text-[10px] text-gray-500 mt-0.5">e.g. <?= date('d-m-y') ?></div>
                            </div>
                        </div>

                        <!-- User / Operator Code (HBL / BankIslami Specific / Viewable) -->
                        <div id="row-hbl-user-code" class="hidden flex items-start">
                            <label for="stmt-hbl-user-code" id="lbl-hbl-user-code" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">User Code</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-hbl-user-code" name="user_code" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-hbl-user-code" class="text-[10px] text-gray-500 mt-0.5">e.g. APR801</div>
                            </div>
                        </div>

                        <!-- Issuing Branch (BankIslami Specific / Viewable) -->
                        <div id="row-issuing-branch" class="hidden flex items-start">
                            <label for="stmt-issuing-branch" id="lbl-issuing-branch" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Issuing Branch</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-issuing-branch" name="issuing_branch" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-issuing-branch" class="text-[10px] text-gray-500 mt-0.5">e.g. KUCHLAK</div>
                            </div>
                        </div>

                        <!-- Issue Date (BankIslami Specific / Viewable) -->
                        <div id="row-issue-date" class="hidden flex items-start">
                            <label for="stmt-issue-date" id="lbl-issue-date" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Issue Date</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-issue-date" name="issue_date" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-issue-date" class="text-[10px] text-gray-500 mt-0.5">e.g. <?= date('d-M-Y') ?></div>
                            </div>
                        </div>

                        <!-- Statement Title (BankIslami Specific / Viewable) -->
                        <div id="row-statement-title" class="hidden flex items-start">
                            <label for="stmt-statement-title" id="lbl-statement-title" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Doc Title</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-statement-title" name="statement_title" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-statement-title" class="text-[10px] text-gray-500 mt-0.5">e.g. Duplicate Statement Of Account</div>
                            </div>
                        </div>

                        <!-- Branch Code (UBL Specific / Viewable) -->
                        <div id="row-branch-code" class="hidden flex items-start">
                            <label for="stmt-branch-code" id="lbl-branch-code" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Branch Code</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-branch-code" name="branch_code" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-branch-code" class="text-[10px] text-gray-500 mt-0.5">e.g. 0053</div>
                            </div>
                        </div>

                        <!-- Product Type (UBL Specific / Viewable) -->
                        <div id="row-product-type" class="hidden flex items-start">
                            <label for="stmt-product-type" id="lbl-product-type" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Product Type</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-product-type" name="product_type" placeholder="" class="win-input w-full text-xs">
                                <div id="desc-product-type" class="text-[10px] text-gray-500 mt-0.5">e.g. AMEEN MUKAMMAL CURRENT ACCOUNT</div>
                            </div>
                        </div>

                        <!-- Reg Cell No (UBL Specific / Viewable) -->
                        <div id="row-reg-cell-no" class="hidden flex items-start">
                            <label for="stmt-reg-cell-no" id="lbl-reg-cell-no" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">Reg Cell No</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-reg-cell-no" name="reg_cell_no" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-reg-cell-no" class="text-[10px] text-gray-500 mt-0.5">e.g. 009203089470136</div>
                            </div>
                        </div>

                        <!-- CIF# (UBL Specific / Viewable) -->
                        <div id="row-cif-no" class="hidden flex items-start">
                            <label for="stmt-cif-no" id="lbl-cif-no" class="font-bold text-[#1a385f] w-32 whitespace-nowrap pt-1">CIF#</label>
                            <div class="flex-1">
                                <input type="text" id="stmt-cif-no" name="cif_no" placeholder="" class="win-input w-full font-mono text-xs">
                                <div id="desc-cif-no" class="text-[10px] text-gray-500 mt-0.5">e.g. 47187224</div>
                            </div>
                        </div>

                        <!-- Show Online Checkbox -->
                        <div class="flex items-center pt-1">
                            <span class="w-32"></span>
                            <label class="inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="stmt-show-online" name="show_online" value="1" class="h-4 w-4 text-blue-600 rounded border-gray-400">
                                <span class="ml-2 font-bold text-[#1a385f] text-xs">Show Online (E-Statement Format)</span>
                            </label>
                        </div>

                        <!-- Hidden format select for resolution -->
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

/**
 * =========================================================================
 * BANK-SPECIFIC STATEMENT FORM REGISTRY (بینک فارمیٹ کنفیگریشن رجسٹری)
 * =========================================================================
 * Har bank statement layout ka apna independent form configuration block hai.
 * Naye formats add karte waqt sirf naya key add hoga; Faysal Bank aur Allied Bank
 * samet tamam mojuda formats 100% mehfooz aur isolated rahenge.
 */
var BankFormRegistry = {
    // 1. ALLIED BANK LIMITED (ABL) FORMAT CONFIGURATION
    'allied': {
        formatCode: 'allied',
        formatTitle: 'Allied Bank Limited (ABL) Statement',
        titleLabel: 'Account Title',
        titleHint: 'e.g. MUHAMMAD ALI',
        addressLabel: 'Street / Area',
        addressHint: 'e.g. SUITE 101 COMMERCIAL AREA',
        addressRows: 1,
        showDiscreteAddress: true, // City, Postal Code, Province/Country, Mobile 1, Mobile 2
        showBranch: true,
        branchLabel: 'Branch Name',
        branchHint: 'e.g. MAIN BRANCH',
        defaultBranch: '',
        showIban: false,
        statusLabel: 'Account Status',
        statusHint: 'e.g. Regular / Current',
        defaultStatus: '',
        defaultCurrency: 'Pakistan Rupee',
        balanceLabel: 'Start Balance',
        parseAddress: function(rawAddr) {
            var lines = (rawAddr || '').split(/\r?\n/).map(function(s){ return s.trim(); }).filter(Boolean);
            var phones = [];
            var regularLines = [];
            lines.forEach(function(l) {
                if (/^03\d{9}$/.test(l.replace(/\s+/g, ''))) {
                    phones.push(l);
                } else {
                    regularLines.push(l);
                }
            });
            return {
                street: regularLines[0] || '',
                city: regularLines[1] || '',
                postalCode: regularLines[2] || '',
                provinceCountry: regularLines[3] || '',
                phone1: phones[0] || '',
                phone2: phones[1] || ''
            };
        },
        matches: function(bLower) {
            return bLower.indexOf('allied') !== -1 || bLower.indexOf('abl') !== -1;
        }
    },

    // 2. FAYSAL BANK LIMITED (FBL) FORMAT CONFIGURATION
    'faysal': {
        formatCode: 'faysal',
        formatTitle: 'Faysal Bank Limited (FBL) Statement',
        titleLabel: 'Title Of Account',
        titleHint: 'e.g. AHMED KHAN',
        addressLabel: 'Address',
        addressHint: 'Full Customer Address',
        addressRows: 2,
        showDiscreteAddress: false,
        showBranch: false,
        branchHint: '',
        defaultBranch: '',
        showIban: true,
        statusLabel: 'Deposit Type',
        statusHint: 'e.g. CURRENT / SAVING',
        defaultStatus: '',
        defaultCurrency: 'PKR',
        balanceLabel: 'Opening Balance',
        parseAddress: null,
        matches: function(bLower) {
            return bLower.indexOf('faysal') !== -1 || bLower.indexOf('faisal') !== -1 || bLower.indexOf('fbl') !== -1;
        }
    },

    // 3. MEEZAN BANK LIMITED (MEEZAN) FORMAT CONFIGURATION
    'meezan': {
        formatCode: 'meezan',
        formatTitle: 'Meezan Bank Limited Statement',
        titleLabel: 'Customer Title',
        titleHint: 'e.g. ENTERPRISE TRADERS',
        addressLabel: 'Address',
        addressHint: 'e.g. PLOT NO 12, SECTOR I-9, ISLAMABAD',
        addressRows: 2,
        showDiscreteAddress: false,
        showBranch: true,
        branchLabel: 'Branch Code & Name',
        branchHint: 'e.g. 0317-JINNAH SUPER BR-ISD',
        defaultBranch: '',
        showBranchAddress: true,
        branchAddressHint: 'e.g. PLOT NO Q-13 COLLAGE ROAD F-7 ISLAMABAD',
        defaultBranchAddress: '',
        showOldAccount: true,
        showGeneratedBy: true,
        generatedByHint: 'e.g. MOIN.31188',
        defaultGeneratedBy: '',
        showPrintDate: true,
        printDateLabel: 'Print Date',
        printDateHint: 'e.g. <?= date('d M Y H:i:s') ?>',
        showIban: true,
        statusLabel: 'Product',
        statusHint: 'e.g. Meezan Rupee Current A/c',
        defaultStatus: '',
        defaultCurrency: 'Pakistan Rupee',
        balanceLabel: '<= OPENING =>',
        parseAddress: null,
        matches: function(bLower) {
            return bLower.indexOf('meezan') !== -1 || bLower.indexOf('mezn') !== -1;
        }
    },

    // 4. HABIB BANK LIMITED (HBL) FORMAT CONFIGURATION
    'hbl': {
        formatCode: 'hbl',
        formatTitle: 'Habib Bank Limited (HBL) Statement',
        titleLabel: 'Account Title',
        titleHint: 'e.g. SS RAHMAN TRADERS',
        addressLabel: 'Address',
        addressHint: 'e.g. 3RD FLOOR WALI YAR PLAZA, NEAR QAZAFI HOTEL, PESHAWAR PK',
        addressRows: 3,
        showDiscreteAddress: false,
        showBranch: true,
        branchLabel: 'Branch Name',
        branchHint: 'e.g. PESHAWAR-CITY BRANCH',
        defaultBranch: '',
        showBranchAddress: false,
        showOldAccount: false,
        showGeneratedBy: false,
        showPrintDate: false,
        showHblPrintingDate: true,
        printingDateHint: 'e.g. <?= date('d-m-y') ?>',
        showHblFrequency: true,
        frequencyHint: 'e.g. INTERIM / DUPLICATE',
        defaultFrequency: '',
        showHblUserCode: true,
        userCodeHint: 'e.g. APR801',
        defaultUserCode: '',
        showIban: false,
        statusLabel: 'Account Type',
        statusHint: 'e.g. HBL FreedomAccount',
        defaultStatus: '',
        defaultCurrency: 'Pakistan Rupee',
        balanceLabel: 'Opening Balance',
        parseAddress: null,
        matches: function(bLower) {
            return bLower.indexOf('hbl') !== -1 || bLower.indexOf('habib bank') !== -1;
        }
    },

    // 5. HABIB METROPOLITAN BANK (HMB) FORMAT CONFIGURATION
    'habibmetro': {
        formatCode: 'habibmetro',
        formatTitle: 'Habib Metropolitan Bank Statement',
        titleLabel: 'Account Title',
        titleHint: 'e.g. IRFAN ULLAH',
        addressLabel: 'Address',
        addressHint: 'e.g. MAIN GT ROAD BESIDE BANK ISLAMI, TARNOL, ISLAMABAD',
        addressRows: 2,
        showDiscreteAddress: false,
        showBranch: true,
        branchLabel: 'Branch Name',
        branchHint: 'e.g. IBB - Tarnol - Islamabad',
        defaultBranch: '',
        showBranchAddress: false,
        showOldAccount: false,
        showGeneratedBy: false,
        showPrintDate: true,
        printDateLabel: 'Printed On',
        printDateHint: 'e.g. <?= date('d-M-Y') ?>',
        showHblPrintingDate: false,
        showHblFrequency: false,
        showHblUserCode: false,
        showIban: true,
        statusLabel: 'A/C Type',
        statusHint: 'e.g. SIRAT Current Plus Account',
        defaultStatus: '',
        defaultCurrency: 'PKR',
        balanceLabel: 'Opening Balance',
        parseAddress: null,
        matches: function(bLower) {
            return bLower.indexOf('metro') !== -1 || bLower.indexOf('hmb') !== -1;
        }
    },

    // 6. BANKISLAMI PAKISTAN LIMITED FORMAT CONFIGURATION
    'bankislami': {
        formatCode: 'bankislami',
        formatTitle: 'BankIslami Pakistan Limited Statement',
        titleLabel: 'Account Title',
        titleHint: 'e.g. REHMAT OBAID SEAMLESS STEEL PIPE & WIRE',
        addressLabel: 'Address',
        addressHint: 'e.g. STREET / AREA / CITY / PHONES',
        addressRows: 3,
        showDiscreteAddress: false,
        showBranch: true,
        branchLabel: 'Account Branch',
        branchHint: 'e.g. KUCHLAK',
        defaultBranch: 'KUCHLAK',
        showIssuingBranch: true,
        issuingBranchHint: 'e.g. KUCHLAK',
        showIssueDate: true,
        issueDateHint: 'e.g. <?= date('d-M-Y') ?>',
        showStatementTitle: true,
        statementTitleHint: 'e.g. Duplicate Statement Of Account',
        showBranchAddress: false,
        showOldAccount: false,
        showGeneratedBy: false,
        showPrintDate: true,
        printDateLabel: 'Printed On',
        printDateHint: 'e.g. <?= date('n/j/Y g:i A') ?>',
        showHblPrintingDate: false,
        showHblFrequency: false,
        showHblUserCode: true,
        userCodeLabel: 'Printed By',
        userCodeHint: 'e.g. UM12926',
        showIban: true,
        statusLabel: 'Account Type',
        statusHint: 'e.g. Current Account',
        defaultStatus: 'Current Account',
        defaultCurrency: 'Pakistani Rupee',
        balanceLabel: 'Opening Balance',
        parseAddress: null,
        matches: function(bLower) {
            return bLower.indexOf('islami') !== -1 || bLower.indexOf('bipl') !== -1 || bLower.indexOf('bankislami') !== -1;
        }
    },

    // 7. UBL / UBL AMEEN ISLAMIC BANKING FORMAT CONFIGURATION
    'ubl': {
        formatCode: 'ubl',
        formatTitle: 'UBL Ameen Islamic Banking Statement',
        titleLabel: 'Account Title',
        titleHint: 'e.g. FARHAN KHAN',
        addressLabel: 'Address',
        addressHint: 'e.g. CHARSADA ROAD MOH DIN BAHAR COLONY PESHAWAR',
        addressRows: 2,
        showDiscreteAddress: false,
        showBranch: true,
        branchLabel: 'Branch Name',
        branchHint: 'e.g. 0053-UBL AMEEN CITY MAIN BR, PWR',
        defaultBranch: '0053-UBL AMEEN CITY MAIN BR, PWR',
        showBranchCode: true,
        branchCodeHint: 'e.g. 0053',
        defaultBranchCode: '0053',
        showProductType: true,
        productTypeHint: 'e.g. AMEEN MUKAMMAL CURRENT ACCOUNT',
        defaultProductType: 'AMEEN MUKAMMAL CURRENT ACCOUNT',
        showRegCellNo: true,
        regCellNoHint: 'e.g. 009203089470136',
        showCifNo: true,
        cifNoHint: 'e.g. 47187224',
        defaultCifNo: '47187224',
        showIban: true,
        defaultIban: 'PK81 UNIL 0109 0003 4432 7894',
        statusLabel: 'Account Type',
        statusHint: 'e.g. CURRENT',
        defaultStatus: 'CURRENT',
        defaultCurrency: 'PAKISTANI RUPEE',
        balanceLabel: 'Opening Balance',
        showIssuingBranch: false,
        showIssueDate: false,
        showStatementTitle: false,
        showBranchAddress: false,
        showOldAccount: false,
        showGeneratedBy: false,
        showPrintDate: false,
        showHblPrintingDate: false,
        showHblFrequency: false,
        showHblUserCode: false,
        parseAddress: null,
        matches: function(bLower) {
            return bLower.indexOf('ubl') !== -1 || bLower.indexOf('united') !== -1 || bLower.indexOf('ameen') !== -1;
        }
    },

    // 8. DEFAULT / STANDARD / UPCOMING BANKS FALLBACK
    'default': {
        formatCode: 'standard',
        formatTitle: 'Standard Bank Statement',
        titleLabel: 'Name/F.Name',
        titleHint: 'Customer / Father Name',
        addressLabel: 'Address',
        addressHint: 'Customer address or branch details',
        addressRows: 2,
        showDiscreteAddress: false,
        showBranch: true,
        branchLabel: 'Branch Name',
        branchHint: 'e.g. MAIN BRANCH',
        defaultBranch: '',
        showBranchAddress: false,
        showOldAccount: false,
        showGeneratedBy: false,
        showPrintDate: false,
        showIban: true,
        statusLabel: 'Account Type',
        statusHint: 'e.g. Current Account',
        defaultStatus: '',
        defaultCurrency: 'PKR',
        balanceLabel: 'Opening Balance',
        parseAddress: null,
        matches: function() { return true; }
    }
};

function getBankFormConfig(bankName) {
    var bLower = (bankName || '').toLowerCase();
    if (BankFormRegistry.allied.matches(bLower)) return BankFormRegistry.allied;
    if (BankFormRegistry.faysal.matches(bLower)) return BankFormRegistry.faysal;
    if (BankFormRegistry.meezan.matches(bLower)) return BankFormRegistry.meezan;
    if (BankFormRegistry.habibmetro.matches(bLower)) return BankFormRegistry.habibmetro;
    if (BankFormRegistry.bankislami && BankFormRegistry.bankislami.matches(bLower)) return BankFormRegistry.bankislami;
    if (BankFormRegistry.ubl && BankFormRegistry.ubl.matches(bLower)) return BankFormRegistry.ubl;
    if (BankFormRegistry.hbl.matches(bLower)) return BankFormRegistry.hbl;
    return BankFormRegistry.default;
}

function updateFormFieldsForBank(bankName) {
    bankName = bankName || $('#stmt-bank-name').val() || '';
    var config = getBankFormConfig(bankName);

    // Update labels and descriptions
    $('#lbl-customer-name').text(config.titleLabel);
    $('#desc-customer-name').text(config.titleHint || '');
    $('#lbl-address').text(config.addressLabel);
    $('#desc-address').text(config.addressHint || '');
    $('#stmt-address').attr('rows', config.addressRows);

    // Discrete address fields visibility (ABL specific)
    if (config.showDiscreteAddress) {
        $('#row-city, #row-postal-code, #row-province-country, #row-phone1, #row-phone2').removeClass('hidden');
    } else {
        $('#row-city, #row-postal-code, #row-province-country, #row-phone1, #row-phone2').addClass('hidden');
    }

    // Branch field visibility and hint
    $('#lbl-branch-name').text(config.branchLabel || 'Branch Name');
    $('#desc-branch-name').text(config.branchHint || '');
    if (config.showBranch) {
        $('#row-branch-name').removeClass('hidden');
    } else {
        $('#row-branch-name').addClass('hidden');
    }

    // Issuing branch (BankIslami specific)
    if (config.showIssuingBranch) {
        $('#row-issuing-branch').removeClass('hidden');
        $('#desc-issuing-branch').text(config.issuingBranchHint || '');
    } else {
        $('#row-issuing-branch').addClass('hidden');
    }

    // Issue date (BankIslami specific)
    if (config.showIssueDate) {
        $('#row-issue-date').removeClass('hidden');
        $('#desc-issue-date').text(config.issueDateHint || '');
    } else {
        $('#row-issue-date').addClass('hidden');
    }

    // Statement doc title (BankIslami specific)
    if (config.showStatementTitle) {
        $('#row-statement-title').removeClass('hidden');
        $('#desc-statement-title').text(config.statementTitleHint || '');
    } else {
        $('#row-statement-title').addClass('hidden');
    }

    // Branch address (Meezan specific)
    if (config.showBranchAddress) {
        $('#row-branch-address').removeClass('hidden');
        $('#desc-branch-address').text(config.branchAddressHint || '');
    } else {
        $('#row-branch-address').addClass('hidden');
    }

    // Old account no (Meezan specific)
    if (config.showOldAccount) {
        $('#row-old-account-no').removeClass('hidden');
    } else {
        $('#row-old-account-no').addClass('hidden');
    }

    // Generated by (Meezan specific)
    if (config.showGeneratedBy) {
        $('#row-generated-by').removeClass('hidden');
        $('#desc-generated-by').text(config.generatedByHint || '');
    } else {
        $('#row-generated-by').addClass('hidden');
    }

    // Print date (Meezan, Habib Metro & BankIslami specific)
    if (config.showPrintDate) {
        $('#row-print-date').removeClass('hidden');
        $('#lbl-print-date').text(config.printDateLabel || 'Print Date');
        $('#desc-print-date').text(config.printDateHint || '');
    } else {
        $('#row-print-date').addClass('hidden');
    }

    // HBL / BankIslami Specific Fields: Frequency, Printing Date, User Code
    if (config.showHblFrequency) {
        $('#row-hbl-frequency').removeClass('hidden');
        $('#desc-hbl-frequency').text(config.frequencyHint || '');
    } else {
        $('#row-hbl-frequency').addClass('hidden');
    }

    if (config.showHblPrintingDate) {
        $('#row-hbl-printing-date').removeClass('hidden');
        $('#desc-hbl-printing-date').text(config.printingDateHint || '');
    } else {
        $('#row-hbl-printing-date').addClass('hidden');
    }

    if (config.showHblUserCode) {
        $('#row-hbl-user-code').removeClass('hidden');
        $('#lbl-hbl-user-code').text(config.userCodeLabel || 'User Code');
        $('#desc-hbl-user-code').text(config.userCodeHint || '');
    } else {
        $('#row-hbl-user-code').addClass('hidden');
    }

    // UBL Specific Fields: Branch Code, Product Type, Reg Cell No, CIF#
    if (config.showBranchCode) {
        $('#row-branch-code').removeClass('hidden');
        $('#desc-branch-code').text(config.branchCodeHint || '');
    } else {
        $('#row-branch-code').addClass('hidden');
    }

    if (config.showProductType) {
        $('#row-product-type').removeClass('hidden');
        $('#desc-product-type').text(config.productTypeHint || '');
    } else {
        $('#row-product-type').addClass('hidden');
    }

    if (config.showRegCellNo) {
        $('#row-reg-cell-no').removeClass('hidden');
        $('#desc-reg-cell-no').text(config.regCellNoHint || '');
    } else {
        $('#row-reg-cell-no').addClass('hidden');
    }

    if (config.showCifNo) {
        $('#row-cif-no').removeClass('hidden');
        $('#desc-cif-no').text(config.cifNoHint || '');
    } else {
        $('#row-cif-no').addClass('hidden');
    }

    // IBAN field visibility
    if (config.showIban) {
        $('#row-iban').removeClass('hidden');
    } else {
        $('#row-iban').addClass('hidden');
    }

    // Account status / deposit type / product label and hint
    $('#lbl-account-status').text(config.statusLabel);
    $('#desc-account-status').text(config.statusHint || '');

    // Currency
    $('#row-currency').removeClass('hidden');

    // Balance label
    $('#lbl-opening-balance').text(config.balanceLabel);

    // Hidden format code
    $('#stmt-format').val(config.formatCode);
}

function populateAccountFields(acc) {
    if (!acc) return;
    $('#stmt-account-id').val(acc.id);
    $('#stmt-customer-name').val(acc.account_title || '');
    $('#stmt-actual-acc-no').val(acc.account_number || '');
    $('#stmt-opening-balance').val(parseFloat(acc.initial_balance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#stmt-iban').val(acc.iban || '');

    if (acc.max_tx_date && (!$('#stmt-end').val() || acc.max_tx_date > $('#stmt-end').val())) {
        $('#stmt-end').val(acc.max_tx_date);
    }
    
    var bName = acc.bank_name || '';
    if (bName && (!manualBankSelected || !$('#stmt-bank-name').val())) {
        $('#stmt-bank-name').val(bName);
        $('#dialog-bank-title').text(bName);
    }

    var currentBank = $('#stmt-bank-name').val() || bName;
    var config = getBankFormConfig(currentBank);
    updateFormFieldsForBank(currentBank);

    if (config.parseAddress && typeof config.parseAddress === 'function') {
        var parsed = config.parseAddress(acc.address);
        $('#stmt-address').val(parsed.street || '');
        $('#stmt-city').val(parsed.city || '');
        $('#stmt-postal-code').val(parsed.postalCode || '');
        $('#stmt-province-country').val(parsed.provinceCountry || '');
        $('#stmt-phone1').val(parsed.phone1 || '');
        $('#stmt-phone2').val(parsed.phone2 || '');
        $('#stmt-branch-name').val(acc.branch_name || '');
        $('#stmt-account-status').val(acc.account_type || '');
        $('#stmt-currency').val(acc.currency || config.defaultCurrency || 'PKR');
    } else {
        $('#stmt-address').val(acc.address || '');
        $('#stmt-account-status').val(acc.account_type || config.defaultStatus || '');
        $('#stmt-branch-name').val(acc.branch_name || config.defaultBranch || '');
        $('#stmt-currency').val(acc.currency || config.defaultCurrency || 'PKR');
    }

    if (config.formatCode === 'bankislami') {
        if (!$('#stmt-statement-title').val()) {
            $('#stmt-statement-title').val('Duplicate Statement Of Account');
        }
        if (!$('#stmt-issuing-branch').val()) {
            $('#stmt-issuing-branch').val(acc.branch_name || 'KUCHLAK');
        }
        if (!$('#stmt-hbl-user-code').val()) {
            $('#stmt-hbl-user-code').val('UM12926');
        }
        if (!$('#stmt-account-status').val()) {
            $('#stmt-account-status').val('Current Account');
        }
        if (!$('#stmt-currency').val()) {
            $('#stmt-currency').val('Pakistani Rupee');
        }
    }

    if (config.formatCode === 'ubl') {
        if (!$('#stmt-branch-code').val()) {
            $('#stmt-branch-code').val('0053');
        }
        if (!$('#stmt-product-type').val()) {
            $('#stmt-product-type').val('AMEEN MUKAMMAL CURRENT ACCOUNT');
        }
        if (!$('#stmt-reg-cell-no').val()) {
            $('#stmt-reg-cell-no').val(acc.phone1 || '009203089470136');
        }
        if (!$('#stmt-cif-no').val()) {
            $('#stmt-cif-no').val('47187224');
        }
        if (!$('#stmt-branch-name').val()) {
            $('#stmt-branch-name').val('0053-UBL AMEEN CITY MAIN BR, PWR');
        }
        if (!$('#stmt-account-status').val()) {
            $('#stmt-account-status').val('CURRENT');
        }
        if (!$('#stmt-currency').val()) {
            $('#stmt-currency').val('PAKISTANI RUPEE');
        }
        if (!$('#stmt-iban').val()) {
            $('#stmt-iban').val(acc.iban || 'PK81 UNIL 0109 0003 4432 7894');
        }
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
    updateFormFieldsForBank(bName);
    
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

// Initialize form layout on page load
$(document).ready(function() {
    updateFormFieldsForBank($('#stmt-bank-name').val());
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
    } else if (bankLower.indexOf('allied') !== -1 || bankLower.indexOf('abl') !== -1) {
        format = 'allied';
    } else if (bankLower.indexOf('meezan') !== -1 || bankLower.indexOf('mezn') !== -1) {
        format = 'meezan';
    } else if (bankLower.indexOf('metro') !== -1 || bankLower.indexOf('hmb') !== -1) {
        format = 'habibmetro';
    } else if (bankLower.indexOf('islami') !== -1 || bankLower.indexOf('bipl') !== -1 || bankLower.indexOf('bankislami') !== -1) {
        format = 'bankislami';
    } else if (bankLower.indexOf('ubl') !== -1 || bankLower.indexOf('united') !== -1 || bankLower.indexOf('ameen') !== -1) {
        format = 'ubl';
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
        city: $('#stmt-city').val() || '',
        postal_code: $('#stmt-postal-code').val() || '',
        province_country: $('#stmt-province-country').val() || '',
        phone1: $('#stmt-phone1').val() || '',
        phone2: $('#stmt-phone2').val() || '',
        branch_name: $('#stmt-branch-name').val() || '',
        branch_address: $('#stmt-branch-address').val() || '',
        old_account_no: $('#stmt-old-account-no').val() || '',
        product: $('#stmt-product-type').val() || $('#stmt-account-status').val() || '',
        product_type: $('#stmt-product-type').val() || '',
        branch_code: $('#stmt-branch-code').val() || '',
        reg_cell_no: $('#stmt-reg-cell-no').val() || '',
        cif_no: $('#stmt-cif-no').val() || '',
        generated_by: $('#stmt-generated-by').val() || '',
        print_date: $('#stmt-print-date').val() || '',
        account_status: $('#stmt-account-status').val() || '',
        account_type: $('#stmt-account-status').val() || '',
        currency: $('#stmt-currency').val() || '',
        iban: $('#stmt-iban').val() || '',
        opening_balance: $('#stmt-opening-balance').val(),
        frequency: $('#stmt-hbl-frequency').val() || '',
        printing_date: $('#stmt-hbl-printing-date').val() || '',
        user_code: $('#stmt-hbl-user-code').val() || '',
        printed_on: $('#stmt-print-date').val() || '',
        issuing_branch: $('#stmt-issuing-branch').val() || '',
        issue_date: $('#stmt-issue-date').val() || '',
        statement_title: $('#stmt-statement-title').val() || ''
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
    } else if (bankLower.indexOf('allied') !== -1 || bankLower.indexOf('abl') !== -1) {
        format = 'allied';
    } else if (bankLower.indexOf('meezan') !== -1 || bankLower.indexOf('mezn') !== -1) {
        format = 'meezan';
    } else if (bankLower.indexOf('metro') !== -1 || bankLower.indexOf('hmb') !== -1) {
        format = 'habibmetro';
    } else if (bankLower.indexOf('islami') !== -1 || bankLower.indexOf('bipl') !== -1 || bankLower.indexOf('bankislami') !== -1) {
        format = 'bankislami';
    } else if (bankLower.indexOf('ubl') !== -1 || bankLower.indexOf('united') !== -1 || bankLower.indexOf('ameen') !== -1) {
        format = 'ubl';
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
        city: $('#stmt-city').val() || '',
        postal_code: $('#stmt-postal-code').val() || '',
        province_country: $('#stmt-province-country').val() || '',
        phone1: $('#stmt-phone1').val() || '',
        phone2: $('#stmt-phone2').val() || '',
        branch_name: $('#stmt-branch-name').val() || '',
        branch_address: $('#stmt-branch-address').val() || '',
        old_account_no: $('#stmt-old-account-no').val() || '',
        product: $('#stmt-product-type').val() || $('#stmt-account-status').val() || '',
        product_type: $('#stmt-product-type').val() || '',
        branch_code: $('#stmt-branch-code').val() || '',
        reg_cell_no: $('#stmt-reg-cell-no').val() || '',
        cif_no: $('#stmt-cif-no').val() || '',
        generated_by: $('#stmt-generated-by').val() || '',
        print_date: $('#stmt-print-date').val() || '',
        printed_on: $('#stmt-print-date').val() || '',
        account_status: $('#stmt-account-status').val() || '',
        account_type: $('#stmt-account-status').val() || '',
        currency: $('#stmt-currency').val() || '',
        iban: $('#stmt-iban').val() || '',
        opening_balance: $('#stmt-opening-balance').val() || '',
        frequency: $('#stmt-hbl-frequency').val() || '',
        printing_date: $('#stmt-hbl-printing-date').val() || '',
        user_code: $('#stmt-hbl-user-code').val() || '',
        issuing_branch: $('#stmt-issuing-branch').val() || '',
        issue_date: $('#stmt-issue-date').val() || '',
        statement_title: $('#stmt-statement-title').val() || ''
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
