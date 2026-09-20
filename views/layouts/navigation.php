<!-- 
================================================================================
File: views/layouts/navigation.php
Description: Top Application Header, User Menu, Navigation Tabs, and Ribbon Toolbar.
(اوپری ونڈوز ٹائٹل بار، مینیو، ربن ٹیبز اور تمام بینکوں کے رپورٹس بٹن)
--------------------------------------------------------------------------------
URDU NOTE FOR DEVELOPER (رہنمائی):
1. اگر آپ نے نیوی گیشن مینیو یا ربن بار کے بٹنز میں تبدیلی کرنی ہے، تو یہ وہی فائل ہے۔
2. تمام لنکس اب '.php' کے ساتھ ہیں (جیسے /atm/transactions/rapid.php, /atm/statements.php).
3. نیچے $ribbonBanks ارے کے اندر تمام وہ بینکس ہیں جو اوپر ربن پٹی پر نظر آتے ہیں۔
================================================================================
-->
<?php
$user = Session::get('user_id') ? [
    'id' => Session::get('user_id'),
    'username' => Session::get('username'),
    'full_name' => Session::get('full_name'),
    'role' => Session::get('role')
] : null;
$isAdmin = ($user && $user['role'] === 'admin');
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Master banks for ribbon display (ربن پر نظر آنے والے بینک لوگوز اور بٹن)
$ribbonBanks = [
    ['name' => 'Allied Bank', 'code' => 'ABL', 'color' => '#005a9c'],
    ['name' => 'Faysal Bank', 'code' => 'FBL', 'color' => '#003366'],
    ['name' => 'NIB Bank', 'code' => 'NIB', 'color' => '#cc0000'],
    ['name' => 'Standard Chartered', 'code' => 'SCB', 'color' => '#00853e'],
    ['name' => 'Askari Bank', 'code' => 'AKBL', 'color' => '#006633'],
    ['name' => 'Meezan Bank', 'code' => 'Meezan', 'color' => '#660066'],
    ['name' => 'HBL Bank', 'code' => 'HBL', 'color' => '#00796b'],
    ['name' => 'Alfalah Bank', 'code' => 'Alfalah', 'color' => '#b71c1c'],
    ['name' => 'Dubai Islamic Bank', 'code' => 'DIB', 'color' => '#1b5e20'],
    ['name' => 'UBL Bank', 'code' => 'UBL', 'color' => '#01579b'],
    ['name' => 'MCB', 'code' => 'MCB', 'color' => '#e65100'],
    ['name' => 'Al Barka Bank', 'code' => 'Al Barka', 'color' => '#4a148c']
];

// Active Tab Detection for Ribbon Tabs (فعال ٹیب کی شناخت)
$isRapidActive = (strpos($currentUri, 'rapid') !== false || strpos($currentUri, 'transaction.php') !== false || $currentUri === '/atm/' || $currentUri === '/atm');
$isAccountsActive = (strpos($currentUri, 'account') !== false);
$isLedgerActive = (strpos($currentUri, 'transactions.php') !== false || strpos($currentUri, 'ledger') !== false) && !$isRapidActive;
$isStatementsActive = (strpos($currentUri, 'statement') !== false);
?>

<!-- Desktop Software Top Window Titlebar (اوپری ٹائٹل بار) -->
<header class="bg-gray-900 text-gray-200 border-b border-gray-800 no-print select-none">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 flex items-center justify-between h-8 text-xs">
        <div class="flex items-center space-x-2">
            <!-- App Icon -->
            <div class="w-4 h-4 rounded bg-blue-500 flex items-center justify-center font-black text-[9px] text-white shadow-xs">
                A
            </div>
            <span class="font-bold tracking-wide text-white">Account MGR &mdash; ATM Ledger</span>
        </div>

        <div class="flex items-center space-x-4">
            <?php if ($user): ?>
                <!-- Desktop user profile dropdown (یوزر پروفائل ڈراپ ڈاؤن) -->
                <div class="relative hidden sm:block">
                    <button id="user-menu-button" type="button" onclick="document.getElementById('user-menu-dropdown').classList.toggle('hidden')" class="flex items-center space-x-1 text-gray-300 hover:text-white text-[11px] focus:outline-none cursor-pointer">
                        <span>User: <strong class="text-white"><?= e($user['full_name'] ?: $user['username']) ?></strong> (<?= e($user['role']) ?>)</span>
                        <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="user-menu-dropdown" class="hidden absolute right-0 mt-1.5 w-48 bg-white text-gray-800 rounded shadow-xl py-1 z-50 border border-gray-200 text-xs">
                        <div class="px-3 py-1.5 border-b border-gray-100 font-semibold text-gray-700 bg-gray-50">
                            Signed in as <?= e($user['username']) ?>
                        </div>
                        <?php if ($isAdmin): ?>
                            <a href="/atm/templates.php" class="block px-3 py-1.5 hover:bg-blue-50 text-gray-700 hover:text-blue-700">Templates</a>
                            <a href="/atm/banks.php" class="block px-3 py-1.5 hover:bg-blue-50 text-gray-700 hover:text-blue-700">Banks</a>
                            <a href="/atm/users.php" class="block px-3 py-1.5 hover:bg-blue-50 text-gray-700 hover:text-blue-700">Users</a>
                            <div class="border-t border-gray-100 my-1"></div>
                        <?php endif; ?>
                        <form action="/atm/logout" method="POST" class="m-0 p-0">
                            <?= CSRF::getField() ?>
                            <button type="submit" class="w-full text-left px-3 py-1.5 hover:bg-red-50 text-red-600 font-semibold cursor-pointer">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Admin quick links in titlebar for quick access (ایڈمن کے فوری لنکس) -->
                <?php if ($isAdmin): ?>
                    <a href="/atm/templates.php" class="text-gray-300 hover:text-white text-[11px] hover:underline hidden md:inline">Templates</a>
                    <a href="/atm/banks.php" class="text-gray-300 hover:text-white text-[11px] hover:underline hidden md:inline">Banks</a>
                    <a href="/atm/users.php" class="text-gray-300 hover:text-white text-[11px] hover:underline hidden md:inline">Users</a>
                <?php endif; ?>

                <!-- Mobile Hamburger toggle button -->
                <button id="mobile-menu-btn" type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="sm:hidden p-1 text-gray-300 hover:text-white focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Mobile Navigation Drawer (موبائل ڈراور مینیو) -->
<div id="mobile-menu" class="hidden sm:hidden bg-gray-900 text-gray-200 px-4 py-2 border-b border-gray-800 text-xs">
    <a href="/atm/transactions/rapid.php" class="block py-1.5 text-gray-200 hover:text-white font-medium">Transaction</a>
    <a href="/atm/accounts.php" class="block py-1.5 text-gray-200 hover:text-white font-medium">Account Detail</a>
    <a href="/atm/transactions.php" class="block py-1.5 text-gray-200 hover:text-white font-medium">Ledger</a>
    <a href="/atm/statements.php" class="block py-1.5 text-gray-200 hover:text-white font-medium">Statements</a>
    <?php if ($isAdmin): ?>
        <div class="border-t border-gray-800 my-1"></div>
        <a href="/atm/templates.php" class="block py-1.5 text-gray-300 hover:text-white">Templates</a>
        <a href="/atm/banks.php" class="block py-1.5 text-gray-300 hover:text-white">Banks</a>
        <a href="/atm/users.php" class="block py-1.5 text-gray-300 hover:text-white">Users</a>
    <?php endif; ?>
    <?php if ($user): ?>
        <div class="border-t border-gray-800 my-1"></div>
        <form action="/atm/logout" method="POST" class="py-1">
            <?= CSRF::getField() ?>
            <button type="submit" class="text-red-400 hover:text-red-300 font-semibold cursor-pointer">Logout</button>
        </form>
    <?php endif; ?>
</div>

<!-- Ribbon Bar Tabs (ربن مینیو ٹیبز) -->
<div class="bg-[#d9e6f5] border-b border-[#aec4de] no-print select-none">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 flex items-center space-x-1 pt-1 text-xs font-bold text-gray-700">
        <a href="/atm/transactions/rapid.php" class="px-3 py-1.5 rounded-t border-t border-l border-r border-[#aec4de] <?= $isRapidActive ? 'bg-[#f2f6fb] text-[#1c3a63] shadow-xs' : 'hover:bg-[#e7eff9] text-gray-700' ?>">
            Home
        </a>
        <a href="/atm/accounts.php" class="px-3 py-1.5 rounded-t border-t border-l border-r border-[#aec4de] <?= $isAccountsActive ? 'bg-[#f2f6fb] text-[#1c3a63] shadow-xs' : 'hover:bg-[#e7eff9] text-gray-700' ?>">
            Accounts
        </a>
        <a href="/atm/transactions.php" class="px-3 py-1.5 rounded-t border-t border-l border-r border-[#aec4de] <?= $isLedgerActive ? 'bg-[#f2f6fb] text-[#1c3a63] shadow-xs' : 'hover:bg-[#e7eff9] text-gray-700' ?>">
            Ledger
        </a>
        <a href="/atm/statements.php" class="px-3 py-1.5 rounded-t border-t border-l border-r border-[#aec4de] <?= $isStatementsActive ? 'bg-[#f2f6fb] text-[#1c3a63] shadow-xs' : 'hover:bg-[#e7eff9] text-gray-700' ?>">
            Statements
        </a>
    </div>
</div>

<!-- Ribbon Command Bar (ربن کمانڈ بار اور بینک گیلری) -->
<div class="ribbon-bar bg-gradient-to-b from-[#f5f8fc] via-[#e6eff8] to-[#d8e5f4] border-b border-[#a3bedc] p-2 no-print shadow-xs select-none">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center gap-2 sm:gap-4 overflow-x-auto">
        <!-- Group 1: Transaction (ٹرانزیکشن اینٹری بٹن) -->
        <a href="/atm/transactions/rapid.php" class="flex flex-col items-center justify-center px-3 py-1.5 rounded hover:bg-[#d5e4f5] border border-transparent hover:border-[#9cbddf] transition group cursor-pointer text-center min-w-[70px]">
            <div class="w-8 h-8 rounded bg-gradient-to-b from-blue-500 to-blue-700 flex items-center justify-center text-white shadow-xs mb-1 group-hover:scale-105 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-[#1a385f]">Transaction</span>
        </a>

        <!-- Group 2: Account Detail (اکاؤنٹ تفصیلات بٹن) -->
        <a href="/atm/accounts.php" class="flex flex-col items-center justify-center px-3 py-1.5 rounded hover:bg-[#d5e4f5] border border-transparent hover:border-[#9cbddf] transition group cursor-pointer text-center min-w-[75px]">
            <div class="w-8 h-8 rounded bg-gradient-to-b from-teal-500 to-teal-700 flex items-center justify-center text-white shadow-xs mb-1 group-hover:scale-105 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-[11px] font-bold text-[#1a385f]">Account Detail</span>
        </a>

        <!-- Vertical Divider -->
        <div class="h-10 w-px bg-[#b8cfe8] my-auto hidden sm:block"></div>

        <!-- Group 3: All Reports & Bank Gallery (تمام بینکس کی رپورٹس بٹنز) -->
        <div class="flex-1 flex flex-col justify-center min-w-[320px]">
            <!-- Bank Buttons Row -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 scrollbar-thin">
                <?php foreach ($ribbonBanks as $rb): ?>
                    <a href="/atm/statements.php?bank=<?= urlencode($rb['name']) ?>" 
                       title="Generate <?= e($rb['name']) ?> Report"
                       class="flex flex-col items-center p-1 rounded bg-white hover:bg-[#e4effb] border border-[#b4cce6] hover:border-[#6b9ad0] shadow-2xs transition flex-shrink-0 cursor-pointer text-center w-16">
                        <span class="text-[9px] font-black uppercase tracking-tight px-1 py-0.5 rounded text-white" style="background-color: <?= $rb['color'] ?>;">
                            <?= e($rb['code']) ?>
                        </span>
                        <span class="text-[9px] text-[#1c3a63] font-bold mt-0.5 truncate w-full">
                            <?= e($rb['name']) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- "All Reports" Center Label -->
            <div class="text-center pt-0.5 border-t border-[#b8cfe8]">
                <a href="/atm/statements.php" class="text-[10px] font-black uppercase tracking-wider text-[#1e4474] hover:underline">
                    All Reports (Click Bank Logo or Here)
                </a>
            </div>
        </div>
    </div>
</div>
