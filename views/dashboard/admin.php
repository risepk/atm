<!-- views/dashboard/admin.php -->
<?php $pageTitle = 'Admin Dashboard'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Admin Dashboard -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>Executive Dashboard &mdash; System-Wide Administration</span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <!-- Window Action Bar -->
        <div class="bg-[#f0f4f9] border-b border-[#cbd9e8] p-2 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-[#1e395b] font-semibold">
                Comprehensive overview of users, financial balances, transactions, and security audit trails.
            </div>
            <div class="flex items-center space-x-2">
                <a href="/atm/transactions/rapid" class="win-btn font-bold text-emerald-900">
                    ⚡ Transaction (Rapid Entry)
                </a>
                <a href="/atm/templates" class="win-btn font-bold text-purple-900">
                    Templates
                </a>
                <a href="/atm/banks" class="win-btn text-gray-800">
                    Banks
                </a>
                <a href="/atm/users" class="win-btn text-gray-800">
                    Users
                </a>
            </div>
        </div>

        <!-- Admin KPI Bar (Desktop Styled) -->
        <div class="bg-[#f7fafc] border-b border-[#cbd9e8] p-3 grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-gray-600 block uppercase text-[10px] font-bold">Total Users</span>
                <span class="text-xl font-black text-gray-900"><?= number_format($user_count) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-gray-600 block uppercase text-[10px] font-bold">Total Accounts</span>
                <span class="text-xl font-black text-gray-900"><?= number_format($account_count) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-blue-900 block uppercase text-[10px] font-bold">Transactions</span>
                <span class="text-xl font-black text-blue-950 font-mono"><?= number_format($total_transactions) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-green-800 block uppercase text-[10px] font-bold">Total Credits</span>
                <span class="text-xl font-black text-green-700 font-mono">PKR <?= CurrencyHelper::format($total_credit) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-red-800 block uppercase text-[10px] font-bold">Total Debits</span>
                <span class="text-xl font-black text-red-700 font-mono">PKR <?= CurrencyHelper::format($total_debit) ?></span>
            </div>
        </div>

        <!-- Two Column Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-[#cbd9e8] bg-white">
            <!-- Left: All Accounts -->
            <div class="p-3">
                <div class="flex items-center justify-between pb-2 border-b border-[#e2ecf7] mb-2">
                    <span class="font-bold text-xs text-[#1a385f] uppercase tracking-wide">All Accounts</span>
                    <a href="/atm/accounts" class="win-btn win-btn-sm text-blue-900">Manage Accounts</a>
                </div>
                <div class="overflow-x-auto max-h-80">
                    <table class="w-full win-table border-collapse">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Account</th>
                                <th style="width: 90px;">Owner</th>
                                <th style="width: 110px; text-align: right;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $acc): ?>
                                <tr class="hover:bg-gray-50 transition cursor-pointer">
                                    <td class="font-mono text-blue-900 text-xs font-bold">#<?= $acc['id'] ?></td>
                                    <td class="font-bold text-gray-900 text-xs"><?= e($acc['account_title']) ?></td>
                                    <td class="text-gray-600 text-xs"><?= e($acc['owner_name'] ?? $acc['username'] ?? 'User') ?></td>
                                    <td class="text-right font-mono font-bold text-gray-900 text-xs">
                                        <?= CurrencyHelper::format($acc['current_balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Audit Trail -->
            <div class="p-3">
                <div class="flex items-center justify-between pb-2 border-b border-[#e2ecf7] mb-2">
                    <span class="font-bold text-xs text-[#1a385f] uppercase tracking-wide">Security &amp; Audit Trail</span>
                    <span class="text-[10px] text-gray-500 font-mono">Immutable</span>
                </div>
                <div class="overflow-x-auto max-h-80">
                    <table class="w-full win-table border-collapse">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Timestamp</th>
                                <th style="width: 70px;">Actor</th>
                                <th style="width: 100px;">Action</th>
                                <th>Entity</th>
                            </tr>
                        </thead>
                        <tbody class="font-mono text-xs">
                            <?php foreach ($recent_audits as $log): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="text-gray-600 whitespace-nowrap text-[11px]"><?= e($log['created_at']) ?></td>
                                    <td class="text-blue-900 font-bold"><?= e($log['username'] ?: 'System') ?></td>
                                    <td class="font-bold text-gray-800"><?= e($log['action']) ?></td>
                                    <td class="text-gray-600"><?= e($log['entity_type']) ?> #<?= $log['entity_id'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
