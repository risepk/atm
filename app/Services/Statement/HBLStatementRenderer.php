<?php
// app/Services/Statement/HBLStatementRenderer.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

class HBLStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'hbl';
    protected string $formatName = 'HBL Statement Layout';

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    /**
     * Render statement HTML for interactive screen preview.
     */
    public function renderHtml(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        $startDateFormatted = $this->formatPeriodDate($statementData['start_date'] ?? date('Y-m-01'));
        $endDateFormatted = $this->formatPeriodDate($statementData['end_date'] ?? date('Y-m-d'));
        $periodString = "{$startDateFormatted} TO {$endDateFormatted}";

        $bankLogo = $account['bank_logo'] ?? null;
        $bankName = htmlspecialchars($account['bank_name'] ?? 'Habib Bank Limited', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars($account['account_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $currencyRaw = $account['currency'] ?? 'PKR';
        $currencyDisplay = ($currencyRaw === 'PKR') ? 'Pakistani Rupee' : htmlspecialchars($currencyRaw, ENT_QUOTES, 'UTF-8');
        
        $printingDate = date('d-m-y');
        $frequency = 'INTERIM / DUPLICATE';
        $userIdentifier = htmlspecialchars(Session::get('username') ?: 'USER', ENT_QUOTES, 'UTF-8');
        $accountType = 'HBL FreedomAccount';

        // Parse optional branch / location info if present
        $branchInfo = '';
        if (!empty($account['bank_code'])) {
            $branchInfo = 'OF ' . htmlspecialchars($account['bank_code'], ENT_QUOTES, 'UTF-8');
        }

        $addressHtml = $this->renderAddressBlock($account);

        // Logo markup
        $logoHtml = '';
        if ($bankLogo) {
            $logoHtml = '<img src="/atm/public/' . htmlspecialchars($bankLogo, ENT_QUOTES, 'UTF-8') . '" alt="' . $bankName . '" class="h-10 max-w-[160px] object-contain mb-3">';
        } else {
            $logoHtml = '<div class="text-xl font-black tracking-wider text-emerald-800 uppercase mb-2">' . $bankName . '</div>';
        }

        // Build transaction rows
        $rowsHtml = '';
        // 1. Brought Forward row
        $rowsHtml .= '<tr class="bg-gray-50/70 border-b border-gray-200 font-semibold">';
        $rowsHtml .= '<td class="px-3 py-2 text-center text-gray-500 font-mono text-xs">-</td>';
        $rowsHtml .= '<td class="px-3 py-2 text-center text-gray-500 font-mono text-xs">-</td>';
        $rowsHtml .= '<td class="px-3 py-2 text-gray-900 font-mono text-xs uppercase tracking-wide">BROUGHT FORWARD</td>';
        $rowsHtml .= '<td class="px-3 py-2 text-right text-gray-400 font-mono text-xs">-</td>';
        $rowsHtml .= '<td class="px-3 py-2 text-right text-gray-400 font-mono text-xs">-</td>';
        $rowsHtml .= '<td class="px-3 py-2 text-right text-gray-900 font-mono text-xs font-bold">' . $opening . '</td>';
        $rowsHtml .= '</tr>';

        // 2. Transaction rows
        foreach ($transactions as $tx) {
            $date = htmlspecialchars($this->formatDateColumn($tx['date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $valueDate = $date; // In ATM Ledger, transaction date serves as value date
            $narration = nl2br(htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8'));
            $debit = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '-';
            $credit = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '-';
            $balance = CurrencyHelper::format($tx['balance'] ?? '0.00');

            $rowsHtml .= '<tr class="border-b border-gray-100 hover:bg-gray-50/60 transition-colors">';
            $rowsHtml .= '<td class="px-3 py-2 text-gray-800 font-mono text-xs whitespace-nowrap align-top">' . $date . '</td>';
            $rowsHtml .= '<td class="px-3 py-2 text-gray-700 font-mono text-xs whitespace-nowrap align-top">' . $valueDate . '</td>';
            $rowsHtml .= '<td class="px-3 py-2 text-gray-900 font-mono text-xs break-words leading-relaxed align-top max-w-md">' . $narration . '</td>';
            $rowsHtml .= '<td class="px-3 py-2 text-right text-red-600 font-mono text-xs whitespace-nowrap align-top">' . $debit . '</td>';
            $rowsHtml .= '<td class="px-3 py-2 text-right text-green-600 font-mono text-xs whitespace-nowrap align-top">' . $credit . '</td>';
            $rowsHtml .= '<td class="px-3 py-2 text-right text-gray-900 font-mono text-xs font-bold whitespace-nowrap align-top">' . $balance . '</td>';
            $rowsHtml .= '</tr>';
        }

        return <<<HTML
<div class="hbl-statement-preview bg-white text-gray-900 font-sans border border-gray-300 rounded-lg p-6 sm:p-8 shadow-sm space-y-6">
    <!-- Format Badge -->
    <div class="flex items-center justify-between pb-3 border-b border-gray-200">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                {$this->formatName}
            </span>
            <span class="text-xs text-gray-500 font-mono">Dense Institutional Layout</span>
        </div>
        <div class="text-xs text-gray-500 font-mono">
            Generated: {$printingDate}
        </div>
    </div>

    <!-- Header Section (Sample Reference Structure) -->
    <div class="pt-2">
        <div class="flex flex-col md:flex-row md:justify-between items-start gap-6">
            <!-- Left Header: Document Title & Customer Information -->
            <div class="w-full md:w-1/2 space-y-3">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight uppercase">YOUR ACCOUNT STATEMENT</h2>
                    <div class="text-xs font-bold text-gray-700 font-mono uppercase mt-1">
                        FOR THE PERIOD ENDING: <span class="text-gray-900 font-black">{$periodString}</span>
                    </div>
                </div>

                <div class="pt-2 text-xs font-mono text-gray-800 uppercase leading-relaxed">
                    <div class="font-extrabold text-sm text-gray-900">{$accountTitle}</div>
                    {$addressHtml}
                </div>
            </div>

            <!-- Right Header: Bank Logo & Dense Account Metadata Table -->
            <div class="w-full md:w-1/2 flex flex-col items-start md:items-end">
                {$logoHtml}
                <div class="w-full max-w-sm bg-gray-50/80 border border-gray-200 rounded p-3 text-xs font-mono">
                    <table class="w-full text-xs leading-snug">
                        <tbody>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5 w-32">ACCOUNT NO</td>
                                <td class="text-gray-900 font-extrabold py-0.5">: {$accountNumber} {$branchInfo}</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5">ACCOUNT TYPE</td>
                                <td class="text-gray-800 py-0.5">: {$accountType}</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5">CURRENCY</td>
                                <td class="text-gray-800 py-0.5">: {$currencyDisplay}</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5">PRINTING DATE</td>
                                <td class="text-gray-800 py-0.5">: {$printingDate}</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5">FREQUENCY</td>
                                <td class="text-gray-800 py-0.5">: {$frequency}</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5">PAGE NO</td>
                                <td class="text-gray-800 py-0.5">: 1</td>
                            </tr>
                            <tr>
                                <td class="font-bold text-gray-600 uppercase py-0.5">USER</td>
                                <td class="text-gray-800 py-0.5">: {$userIdentifier}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Quick Summary Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-gray-50 border-t border-b border-gray-200 text-xs font-mono">
        <div>
            <div class="text-gray-500 uppercase font-semibold text-[10px]">Opening Balance</div>
            <div class="font-bold text-gray-900 text-xs mt-0.5">{$opening}</div>
        </div>
        <div>
            <div class="text-red-600 uppercase font-semibold text-[10px]">Total Debits</div>
            <div class="font-bold text-red-600 text-xs mt-0.5">{$totalDebit}</div>
        </div>
        <div>
            <div class="text-green-600 uppercase font-semibold text-[10px]">Total Credits</div>
            <div class="font-bold text-green-600 text-xs mt-0.5">{$totalCredit}</div>
        </div>
        <div>
            <div class="text-indigo-900 uppercase font-semibold text-[10px]">Closing Balance</div>
            <div class="font-bold text-indigo-900 text-xs mt-0.5">{$closing}</div>
        </div>
    </div>

    <!-- HBL Dense Transaction Table -->
    <div class="overflow-x-auto border border-gray-300 rounded">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-gray-100 border-b border-gray-300 text-gray-800 font-mono uppercase text-[11px]">
                <tr>
                    <th class="px-3 py-2.5 font-extrabold w-24">DATE</th>
                    <th class="px-3 py-2.5 font-extrabold w-24">VALUE</th>
                    <th class="px-3 py-2.5 font-extrabold">PARTICULARS</th>
                    <th class="px-3 py-2.5 font-extrabold text-right w-28">DEBIT</th>
                    <th class="px-3 py-2.5 font-extrabold text-right w-28">CREDIT</th>
                    <th class="px-3 py-2.5 font-extrabold text-right w-32">BALANCE</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                {$rowsHtml}
            </tbody>
        </table>
    </div>

    <!-- Footer Disclaimers -->
    <div class="pt-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs font-mono text-gray-500 gap-2">
        <div>Continuation on next page</div>
        <div class="italic">This is a system generated account statement and does not require a signature.</div>
    </div>
</div>
HTML;
    }

    /**
     * Render standalone printable A4 HTML document for browser print / Save as PDF.
     */
    public function renderPrint(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        $startDateFormatted = $this->formatPeriodDate($statementData['start_date'] ?? date('Y-m-01'));
        $endDateFormatted = $this->formatPeriodDate($statementData['end_date'] ?? date('Y-m-d'));
        $periodString = "{$startDateFormatted} TO {$endDateFormatted}";

        $bankLogo = $account['bank_logo'] ?? null;
        $bankName = htmlspecialchars($account['bank_name'] ?? 'Habib Bank Limited', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars($account['account_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $currencyRaw = $account['currency'] ?? 'PKR';
        $currencyDisplay = ($currencyRaw === 'PKR') ? 'Pakistani Rupee' : htmlspecialchars($currencyRaw, ENT_QUOTES, 'UTF-8');
        
        $printingDate = date('d-m-y');
        $frequency = 'INTERIM / DUPLICATE';
        $userIdentifier = htmlspecialchars(Session::get('username') ?: 'USER', ENT_QUOTES, 'UTF-8');
        $accountType = 'HBL FreedomAccount';

        $branchInfo = '';
        if (!empty($account['bank_code'])) {
            $branchInfo = 'OF ' . htmlspecialchars($account['bank_code'], ENT_QUOTES, 'UTF-8');
        }

        $addressHtml = $this->renderAddressBlock($account);

        $logoPrintHtml = '';
        if ($bankLogo) {
            $logoPrintHtml = '<img src="/atm/public/' . htmlspecialchars($bankLogo, ENT_QUOTES, 'UTF-8') . '" alt="' . $bankName . '" style="height: 40px; max-width: 150px; object-fit: contain; margin-bottom: 8px;">';
        } else {
            $logoPrintHtml = '<div style="font-size: 16px; font-weight: bold; color: #065f46; text-transform: uppercase; margin-bottom: 6px;">' . $bankName . '</div>';
        }

        // Build table rows
        $rowsHtml = '';
        // Brought Forward row
        $rowsHtml .= '<tr class="hbl-bf-row">';
        $rowsHtml .= '<td class="text-center">-</td>';
        $rowsHtml .= '<td class="text-center">-</td>';
        $rowsHtml .= '<td class="font-bold">BROUGHT FORWARD</td>';
        $rowsHtml .= '<td class="text-right">-</td>';
        $rowsHtml .= '<td class="text-right">-</td>';
        $rowsHtml .= '<td class="text-right font-bold">' . $opening . '</td>';
        $rowsHtml .= '</tr>';

        foreach ($transactions as $tx) {
            $date = htmlspecialchars($this->formatDateColumn($tx['date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $valueDate = $date;
            $narration = nl2br(htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8'));
            $debit = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '';
            $credit = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '';
            $balance = CurrencyHelper::format($tx['balance'] ?? '0.00');

            $rowsHtml .= '<tr>';
            $rowsHtml .= '<td class="align-top nowrap">' . $date . '</td>';
            $rowsHtml .= '<td class="align-top nowrap">' . $valueDate . '</td>';
            $rowsHtml .= '<td class="align-top hbl-particulars">' . $narration . '</td>';
            $rowsHtml .= '<td class="align-top text-right nowrap">' . $debit . '</td>';
            $rowsHtml .= '<td class="align-top text-right nowrap">' . $credit . '</td>';
            $rowsHtml .= '<td class="align-top text-right font-bold nowrap">' . $balance . '</td>';
            $rowsHtml .= '</tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement - {$accountTitle}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 15mm 10mm;
            @bottom-center {
                content: "Page " counter(page) " of " counter(pages);
            }
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #111827;
            background: #fff;
            margin: 0;
            padding: 15px;
        }
        .no-print {
            margin-bottom: 20px;
            padding: 10px 14px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-print {
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            background: #047857;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-print:hover {
            background: #065f46;
        }
        .header-container {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .doc-title {
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            color: #000;
        }
        .period-title {
            font-size: 11px;
            font-weight: 700;
            font-family: 'Courier New', Courier, monospace;
            text-transform: uppercase;
            margin-bottom: 14px;
            color: #1f2937;
        }
        .customer-block {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.4;
            text-transform: uppercase;
            color: #111;
        }
        .customer-name {
            font-weight: bold;
            font-size: 12px;
        }
        .meta-table {
            border-collapse: collapse;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10.5px;
            line-height: 1.35;
        }
        .meta-table td {
            padding: 1px 4px;
        }
        .meta-label {
            font-weight: bold;
            color: #374151;
            white-space: nowrap;
        }
        .meta-val {
            color: #000;
        }
        .hbl-tx-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10.5px;
            margin-top: 10px;
        }
        .hbl-tx-table thead {
            display: table-header-group;
        }
        .hbl-tx-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 6px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            background: #f9fafb;
            text-align: left;
        }
        .hbl-tx-table td {
            padding: 4px 6px;
            border-bottom: 1px dotted #e5e7eb;
        }
        .hbl-tx-table tr {
            page-break-inside: avoid;
        }
        .hbl-bf-row td {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            font-weight: bold;
        }
        .align-top {
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .nowrap {
            white-space: nowrap;
        }
        .hbl-particulars {
            white-space: normal;
            word-break: break-word;
            line-height: 1.35;
        }
        .statement-footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #9ca3af;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            color: #4b5563;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .hbl-tx-table th {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .hbl-bf-row td {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <strong>{$this->formatName}</strong> &bull; Print Preview / Save as PDF
        </div>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <div class="doc-title">YOUR ACCOUNT STATEMENT</div>
                    <div class="period-title">
                        FOR THE PERIOD ENDING: {$periodString}
                    </div>
                    <div class="customer-block">
                        <div class="customer-name">{$accountTitle}</div>
                        {$addressHtml}
                    </div>
                </td>
                <td style="width: 45%; text-align: right;">
                    <div style="display: inline-block; text-align: left;">
                        <div style="text-align: right;">
                            {$logoPrintHtml}
                        </div>
                        <table class="meta-table">
                            <tbody>
                                <tr>
                                    <td class="meta-label">ACCOUNT NO</td>
                                    <td class="meta-val">: {$accountNumber} {$branchInfo}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">ACCOUNT TYPE</td>
                                    <td class="meta-val">: {$accountType}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">CURRENCY</td>
                                    <td class="meta-val">: {$currencyDisplay}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">PRINTING DATE</td>
                                    <td class="meta-val">: {$printingDate}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">FREQUENCY</td>
                                    <td class="meta-val">: {$frequency}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">PAGE NO</td>
                                    <td class="meta-val">: 1</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">USER</td>
                                    <td class="meta-val">: {$userIdentifier}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="hbl-tx-table">
        <thead>
            <tr>
                <th style="width: 11%;">DATE</th>
                <th style="width: 11%;">VALUE</th>
                <th style="width: 44%;">PARTICULARS</th>
                <th style="width: 11%; text-align: right;">DEBIT</th>
                <th style="width: 11%; text-align: right;">CREDIT</th>
                <th style="width: 12%; text-align: right;">BALANCE</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <div class="statement-footer">
        <div>Continuation on next page</div>
        <div>This is a system generated account statement and does not require a signature.</div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Format a date string to uppercase banking format (e.g. JUL01,2025).
     */
    private function formatPeriodDate(string $dateStr): string {
        $ts = strtotime($dateStr);
        if (!$ts) {
            return strtoupper($dateStr);
        }
        return strtoupper(date('M d, Y', $ts));
    }

    /**
     * Format transaction table date column (e.g. 05-07-25).
     */
    private function formatDateColumn(string $dateStr): string {
        $ts = strtotime($dateStr);
        if (!$ts) {
            return $dateStr;
        }
        return date('d-m-y', $ts);
    }

    /**
     * Render address block cleanly without placeholder leaks.
     */
    private function renderAddressBlock(array $account): string {
        $notes = trim($account['notes'] ?? '');
        if (empty($notes)) {
            return '<div class="text-gray-500 italic text-[11px] mt-1">PK</div>';
        }

        $lines = explode("\n", $notes);
        $output = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $output .= '<div>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
        $output .= '<div>PK</div>';
        return $output;
    }
}
