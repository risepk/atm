<!-- views/auth/login.php -->
<?php $pageTitle = 'Sign In'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-screen flex items-center justify-center bg-[#dbe4ee] py-8 px-4 sm:px-6 lg:px-8">
    <!-- Desktop Login Dialog Window -->
    <div class="win-window max-w-sm w-full shadow-2xl">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Account MGR &mdash; System Authentication</span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <div class="p-5 bg-[#f2f5fa] space-y-4">
            <div class="text-center pb-2 border-b border-[#cbd9e8]">
                <h1 class="text-lg font-black text-[#173252] tracking-tight">ATM LEDGER</h1>
                <p class="text-[11px] text-[#2d507b] font-medium">Desktop Financial Management System</p>
            </div>

            <?php if (Session::hasFlash('error')): ?>
                <div class="p-2 bg-red-100 border border-red-300 text-red-800 text-xs rounded-xs">
                    <?= e(Session::getFlash('error')) ?>
                </div>
            <?php endif; ?>

            <?php if (Session::hasFlash('success')): ?>
                <div class="p-2 bg-green-100 border border-green-300 text-green-800 text-xs rounded-xs">
                    <?= e(Session::getFlash('success')) ?>
                </div>
            <?php endif; ?>

            <form class="space-y-3" action="/atm/login" method="POST">
                <?= CSRF::getField() ?>
                <div>
                    <label for="login-input" class="block text-xs font-bold text-[#1a385f] uppercase mb-1">Username / Operator ID</label>
                    <input id="login-input" name="login" type="text" required class="win-input w-full text-xs font-medium" placeholder="Username or Email" value="admin">
                </div>
                <div>
                    <label for="password-input" class="block text-xs font-bold text-[#1a385f] uppercase mb-1">Security Password</label>
                    <input id="password-input" name="password" type="password" required class="win-input w-full text-xs font-mono font-medium" placeholder="Password" value="Admin@123456">
                </div>

                <div class="pt-2">
                    <button type="submit" class="win-btn w-full py-1.5 text-xs font-black text-blue-950">
                        Sign In to System
                    </button>
                </div>

                <div class="p-2.5 bg-[#e4edf7] rounded-xs border border-[#b8cee6] text-[11px] text-[#1a385f] space-y-1 mt-3">
                    <div class="font-bold uppercase tracking-wider text-[10px] text-[#0f2747] border-b border-[#cbd9e8] pb-1 mb-1">Default Login Credentials:</div>
                    <div class="flex justify-between items-center">
                        <span><strong>Administrator:</strong> admin</span>
                        <span class="font-mono text-gray-700">Admin@123456</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span><strong>Operator 1:</strong> user1</span>
                        <span class="font-mono text-gray-700">User@123456</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span><strong>Operator 2:</strong> user2</span>
                        <span class="font-mono text-gray-700">User@123456</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
