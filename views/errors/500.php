<!-- views/errors/500.php -->
<!DOCTYPE html>
<html lang="en" class="h-full bg-[#dbe4ee]">
<head>
    <meta charset="UTF-8">
    <title>500 Internal Error - ATM Ledger</title>
    <link rel="stylesheet" href="/atm/public/assets/css/tailwind.min.css">
    <style>
        .win-window {
            background-color: #f2f5fa;
            border: 1px solid #8caecf;
            box-shadow: 0 4px 12px rgba(0, 30, 70, 0.12), 0 1px 3px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            overflow: hidden;
        }
        .win-titlebar {
            background: linear-gradient(180deg, #b8d2ee 0%, #a2c2e5 45%, #8cb1dc 50%, #abc8ea 100%);
            border-bottom: 1px solid #7196bf;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #173252;
            font-size: 11px;
            font-weight: 700;
        }
        .win-btn {
            background: linear-gradient(180deg, #f9fbfe 0%, #e2eef9 48%, #cce1f4 52%, #deecf8 100%);
            border: 1px solid #7ea3cc;
            color: #1a385f;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 3px;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 1px 2px rgba(0, 0, 0, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .win-btn:hover {
            background: linear-gradient(180deg, #ffffff 0%, #eaf3fb 48%, #d8e8f7 52%, #e6f1fb 100%);
            border-color: #5c8fc7;
            color: #0d2647;
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="win-window max-w-md w-full shadow-2xl">
        <div class="win-titlebar">
            <span>System Error &mdash; Internal Server Exception (500)</span>
            <span class="w-3.5 h-3.5 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>
        <div class="p-6 bg-[#f2f5fa] text-center space-y-3">
            <div class="text-3xl font-black text-red-600">500</div>
            <h1 class="text-sm font-bold text-gray-900">System Error</h1>
            <p class="text-xs text-gray-600">An unexpected condition prevented the server from completing your request.</p>
            <?php if (!empty($error)): ?>
                <div class="p-2.5 bg-red-50 border border-red-200 text-red-800 text-xs text-left font-mono rounded overflow-auto max-h-32">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            <div class="pt-2 border-t border-[#cbd9e8]">
                <a href="/atm/dashboard" class="win-btn font-bold text-blue-950 px-4 py-1.5">
                    Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

