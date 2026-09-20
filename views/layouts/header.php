<!-- views/layouts/header.php -->
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRF::getToken() ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>ATM Ledger</title>
    <link rel="stylesheet" href="/atm/public/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="/atm/public/assets/css/select2.min.css">
    <link rel="stylesheet" href="/atm/public/assets/css/select2-tailwind.css">
    <script src="/atm/public/assets/js/jquery.min.js"></script>
    <script src="/atm/public/assets/js/select2.min.js"></script>
    <style>
        /* Desktop Theme & Windows Software Aesthetics */
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
            user-select: none;
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
            transition: all 0.1s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .win-btn:hover {
            background: linear-gradient(180deg, #ffffff 0%, #eaf3fb 48%, #d8e8f7 52%, #e6f1fb 100%);
            border-color: #5c8fc7;
            color: #0d2647;
            box-shadow: inset 0 1px 0 #ffffff, 0 1px 3px rgba(30, 80, 150, 0.2);
        }
        .win-btn:active {
            background: linear-gradient(180deg, #c7dcf1 0%, #b8d2eb 50%, #cfe0f3 100%);
            box-shadow: inset 0 1px 2px rgba(0, 30, 70, 0.25);
        }
        .win-btn-sm {
            padding: 2px 8px;
            font-size: 10px;
        }
        .win-input {
            background-color: #ffffff;
            border: 1px solid #7ea3cc;
            border-radius: 2px;
            padding: 3px 6px;
            font-size: 12px;
            color: #111827;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
        }
        .win-input:focus {
            outline: none;
            border-color: #2b70bf;
            box-shadow: 0 0 0 1px #2b70bf, inset 0 1px 2px rgba(0,0,0,0.06);
        }
        .win-table th {
            background: linear-gradient(180deg, #f6f9fc 0%, #e6eff8 100%);
            border-right: 1px solid #cbd7e6;
            border-bottom: 1px solid #a8bed8;
            color: #1e395b;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 6px;
            text-align: left;
            user-select: none;
        }
        .win-table td {
            border-right: 1px solid #e2ebf4;
            border-bottom: 1px solid #e2ebf4;
            padding: 3px 6px;
            font-size: 11px;
        }
        .win-table tr:hover td {
            background-color: #eaf2fb;
        }
        .win-table tr.selected td {
            background-color: #2e72cb !important;
            color: #ffffff !important;
        }
        .win-table tr.selected td .text-red-600 {
            color: #ffd2d2 !important;
        }
        .win-table tr.selected td .text-green-600 {
            color: #d4ffd4 !important;
        }
        .win-table tr.selected td button {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .ribbon-bar {
            background: linear-gradient(180deg, #f5f8fc 0%, #e6eff8 45%, #d8e5f4 50%, #e4edf7 100%);
            border-bottom: 1px solid #a3bedc;
            box-shadow: 0 2px 4px rgba(0, 20, 50, 0.05);
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .win-window { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="h-full flex flex-col font-sans text-gray-800 antialiased">
