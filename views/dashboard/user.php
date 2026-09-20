<!-- views/dashboard/user.php -->
<?php $pageTitle = 'My Dashboard'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Dashboard -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Operator Dashboard &mdash; Portfolio &amp; Activity Overview</span>
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
                Welcome back, <strong><?= e($currentUser['full_name']) ?></strong>.
            </div>
            <div class="flex items-center space-x-2">
                <a href="/atm/transactions/rapid" class="win-btn font-bold text-emerald-900">
                    ⚡ Transaction (Rapid Entry)
                </a>
                <a href="/atm/accounts" class="win-btn font-bold text-blue-900">
                    📁 Manage Accounts
                </a>
                <a href="/atm/statements" class="win-btn text-gray-800">
                    📊 Statement Generator
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards (Desktop Styled) -->
        <div class="bg-[#f7fafc] border-b border-[#cbd9e8] p-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-gray-600 block uppercase text-[10px] font-bold">Active Accounts</span>
                <span class="text-xl font-black text-gray-900"><?= number_format($account_count) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-blue-900 block uppercase text-[10px] font-bold">Total Net Balance</span>
                <span class="text-xl font-black text-blue-950 font-mono">PKR <?= CurrencyHelper::format($total_balance) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-green-800 block uppercase text-[10px] font-bold">Total Inflow (Credits)</span>
                <span class="text-xl font-black text-green-700 font-mono">+ PKR <?= CurrencyHelper::format($total_credit) ?></span>
            </div>
            <div class="p-2.5 bg-white rounded border border-[#cbd9e8] shadow-2xs">
                <span class="text-red-800 block uppercase text-[10px] font-bold">Total Outflow (Debits)</span>
                <span class="text-xl font-black text-red-700 font-mono">- PKR <?= CurrencyHelper::format($total_debit) ?></span>
            </div>
        </div>

        <!-- Two Column Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-[#cbd9e8] bg-white">
            <!-- Left: Accounts List (1 col) -->
            <div class="p-3">
                <div class="flex items-center justify-between pb-2 border-b border-[#e2ecf7] mb-2">
                    <span class="font-bold text-xs text-[#1a385f] uppercase tracking-wide">My Accounts</span>
                    <a href="/atm/accounts" class="win-btn win-btn-sm text-blue-900">View All</a>
                </div>
                <div class="space-y-1.5 overflow-y-auto max-h-96">
                    <?php if (empty($accounts)): ?>
                        <p class="text-xs text-gray-400 italic py-4 text-center">No accounts configured yet.</p>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): ?>
                            <?php $ident = !empty($acc['account_identifier']) ? $acc['account_identifier'] : $acc['id']; ?>
                            <div class="p-2 bg-[#f6f9fc] hover:bg-[#e9f2fd] rounded border border-[#d6e3f2] flex items-center justify-between transition cursor-pointer">
                                <div>
                                    <div class="text-xs font-bold text-gray-900">
                                        <span class="font-mono text-blue-900 mr-1">[<?= e($ident) ?>]</span><?= e($acc['account_title']) ?>
                                    </div>
                                    <div class="text-[10px] text-gray-500 font-medium"><?= e($acc['bank_name'] ?: 'No Bank') ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-bold font-mono <?= (float)$acc['current_balance'] >= 0 ? 'text-gray-900' : 'text-red-600' ?>">
                                        <?= CurrencyHelper::format($acc['current_balance']) ?>
                                    </div>
                                    <a href="/atm/transactions/rapid?account_id=<?= $acc['id'] ?>" class="text-[10px] font-bold text-emerald-800 hover:underline">Entry &rarr;</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Recent Transactions (2 cols) -->
            <div class="lg:col-span-2 p-3">
                <div class="flex items-center justify-between pb-2 border-b border-[#e2ecf7] mb-2">
                    <span class="font-bold text-xs text-[#1a385f] uppercase tracking-wide">Recent Transactions</span>
                    <a href="/atm/transactions" class="win-btn win-btn-sm text-blue-900">Full Journal</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full win-table border-collapse">
                        <thead>
                            <tr>
                                <th style="width: 85px;">Date</th>
                                <th style="width: 120px;">Account</th>
                                <th>Particulars</th>
                                <th style="width: 95px; text-align: right;">Debit</th>
                                <th style="width: 95px; text-align: right;">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-gray-400 italic">No transactions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $tx): ?>
                                    <tr class="hover:bg-gray-50 transition cursor-pointer">
                                        <td class="font-mono text-[11px] text-gray-700 whitespace-nowrap"><?= e($tx['transaction_date']) ?></td>
                                        <td class="font-bold text-xs text-gray-900 whitespace-nowrap">
                                            <span class="font-mono text-blue-900 text-[11px] mr-0.5">#<?= $tx['account_id'] ?></span>
                                            <?= e($tx['account_title']) ?>
                                        </td>
                                        <td class="text-xs text-gray-800 max-w-xs truncate" title="<?= e($tx['narration']) ?>">
                                            <?= e($tx['narration']) ?>
                                        </td>
                                        <td class="text-right font-mono font-bold text-red-700 text-xs whitespace-nowrap">
                                            <?= (float)$tx['debit'] > 0 ? CurrencyHelper::format($tx['debit']) : '-' ?>
                                        </td>
                                        <td class="text-right font-mono font-bold text-green-700 text-xs whitespace-nowrap">
                                            <?= (float)$tx['credit'] > 0 ? CurrencyHelper::format($tx['credit']) : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
