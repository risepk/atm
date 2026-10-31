<?php
// app/Services/Statement/StandardStatementRenderer.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';

class StandardStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'standard';
    protected string $formatName = 'Standard Statement Format';

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    public function renderHtml(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');
        $startDate = htmlspecialchars($statementData['start_date'] ?? '', ENT_QUOTES, 'UTF-8');
        $endDate = htmlspecialchars($statementData['end_date'] ?? '', ENT_QUOTES, 'UTF-8');
        $bankLogo = $account['bank_logo'] ?? null;
        $bankName = htmlspecialchars($account['bank_name'] ?? 'Bank', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars($account['account_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? '', ENT_QUOTES, 'UTF-8');

        $logoHtml = '';
        if ($bankLogo) {
            $logoHtml = '<img src="/atm/public/' . htmlspecialchars($bankLogo, ENT_QUOTES, 'UTF-8') . '" alt="' . $bankName . '" class="w-12 h-12 object-contain rounded-lg border border-gray-200 bg-white p-1 flex-shrink-0 shadow-sm">';
        } else {
            $initials = htmlspecialchars(substr($account['bank_name'] ?? 'BNK', 0, 3), ENT_QUOTES, 'UTF-8');
            $logoHtml = '<div class="w-12 h-12 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center font-bold text-sm flex-shrink-0">' . $initials . '</div>';
        }

        $rowsHtml = '';
        if (empty($transactions)) {
            $rowsHtml = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 italic">No transactions recorded during this period.</td></tr>';
        } else {
            foreach ($transactions as $tx) {
                $date = htmlspecialchars($tx['date'] ?? '', ENT_QUOTES, 'UTF-8');
                $ref = htmlspecialchars($tx['reference_no'] ?? '-', ENT_QUOTES, 'UTF-8');
                $narration = htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8');
                $debit = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '-';
                $credit = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '-';
                $balance = CurrencyHelper::format($tx['balance'] ?? '0.00');

                $rowsHtml .= '<tr class="hover:bg-gray-50">';
                $rowsHtml .= '<td class="px-4 py-2 text-gray-700 font-mono whitespace-nowrap">' . $date . '</td>';
                $rowsHtml .= '<td class="px-4 py-2 text-gray-500 font-mono whitespace-nowrap">' . $ref . '</td>';
                $rowsHtml .= '<td class="px-4 py-2 text-gray-900 font-sans">' . $narration . '</td>';
                $rowsHtml .= '<td class="px-4 py-2 text-right text-red-600 font-bold whitespace-nowrap font-mono">' . $debit . '</td>';
                $rowsHtml .= '<td class="px-4 py-2 text-right text-green-600 font-bold whitespace-nowrap font-mono">' . $credit . '</td>';
                $rowsHtml .= '<td class="px-4 py-2 text-right text-gray-900 font-bold whitespace-nowrap font-mono">' . $balance . '</td>';
                $rowsHtml .= '</tr>';
            }
        }

        $deferredNoticeHtml = '';
        if (!empty($statementData['is_deferred']) && !empty($statementData['deferred_note'])) {
            $note = htmlspecialchars($statementData['deferred_note'], ENT_QUOTES, 'UTF-8');
            $deferredNoticeHtml = '<div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg font-medium">Notice: ' . $note . ' (Rendering in Standard Presentation Format).</div>';
        }

        return <<<HTML
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b-2 border-indigo-900 pb-4">
        <div class="flex items-center space-x-4">
            {$logoHtml}
            <div>
                <h2 class="text-2xl font-black tracking-tight text-indigo-950 uppercase">Account Statement</h2>
                <div class="text-xs text-gray-500 font-mono mt-0.5">{$this->formatName}</div>
            </div>
        </div>
        <div class="mt-4 md:mt-0 text-right">
            <div class="text-sm font-extrabold text-gray-900">{$accountTitle}</div>
            <div class="text-xs text-gray-600 font-mono">{$bankName} &bull; Acc: {$accountNumber}</div>
            <div class="text-xs text-gray-500 font-mono">Period: {$startDate} to {$endDate}</div>
        </div>
    </div>

    {$deferredNoticeHtml}

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200 text-xs">
        <div>
            <span class="text-gray-700 uppercase font-bold">Opening Balance:</span>
            <div class="text-sm font-bold text-gray-900 font-mono mt-0.5">PKR {$opening}</div>
        </div>
        <div>
            <span class="text-red-700 uppercase font-bold">Total Debits:</span>
            <div class="text-sm font-bold text-red-600 font-mono mt-0.5">PKR {$totalDebit}</div>
        </div>
        <div>
            <span class="text-green-700 uppercase font-bold">Total Credits:</span>
            <div class="text-sm font-bold text-green-600 font-mono mt-0.5">PKR {$totalCredit}</div>
        </div>
        <div>
            <span class="text-indigo-900 uppercase font-bold">Closing Balance:</span>
            <div class="text-sm font-bold text-indigo-900 font-mono mt-0.5">PKR {$closing}</div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-100 text-gray-700 uppercase font-bold">
                <tr>
                    <th class="px-4 py-2.5 text-left">Date</th>
                    <th class="px-4 py-2.5 text-left">Ref / Instrument</th>
                    <th class="px-4 py-2.5 text-left">Description / Narration</th>
                    <th class="px-4 py-2.5 text-right">Debit</th>
                    <th class="px-4 py-2.5 text-right">Credit</th>
                    <th class="px-4 py-2.5 text-right">Running Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-mono">
                {$rowsHtml}
            </tbody>
        </table>
    </div>
</div>
HTML;
    }

    public function renderPrint(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');
        $startDate = htmlspecialchars($statementData['start_date'] ?? '', ENT_QUOTES, 'UTF-8');
        $endDate = htmlspecialchars($statementData['end_date'] ?? '', ENT_QUOTES, 'UTF-8');
        $bankLogo = $account['bank_logo'] ?? null;
        $bankName = htmlspecialchars($account['bank_name'] ?? 'Bank', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars($account['account_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $timestamp = date('d M Y H:i:s');

        $logoPrintHtml = '';
        if ($bankLogo) {
            $logoPrintHtml = '<img src="/atm/public/' . htmlspecialchars($bankLogo, ENT_QUOTES, 'UTF-8') . '" alt="' . $bankName . '" style="height: 48px; max-width: 140px; object-fit: contain; vertical-align: middle; margin-right: 12px;">';
        }

        $txRows = '';
        foreach ($transactions as $tx) {
            $date = htmlspecialchars($tx['date'] ?? '', ENT_QUOTES, 'UTF-8');
            $ref = htmlspecialchars($tx['reference_no'] ?? '-', ENT_QUOTES, 'UTF-8');
            $narration = htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8');
            $debit = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '-';
            $credit = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '-';
            $balance = CurrencyHelper::format($tx['balance'] ?? '0.00');

            $txRows .= "<tr>";
            $txRows .= "<td>{$date}</td>";
            $txRows .= "<td>{$ref}</td>";
            $txRows .= "<td style=\"font-family: inherit;\">{$narration}</td>";
            $txRows .= "<td class=\"text-right\">{$debit}</td>";
            $txRows .= "<td class=\"text-right\">{$credit}</td>";
            $txRows .= "<td class=\"text-right font-bold\">{$balance}</td>";
            $txRows .= "</tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Statement - {$accountTitle}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #111; margin: 20px; }
        .header-table { width: 100%; border-bottom: 2px solid #1e1b4b; padding-bottom: 10px; margin-bottom: 15px; }
        .summary-box { width: 100%; margin-bottom: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-collapse: collapse; }
        .summary-box td { padding: 8px; border: 1px solid #cbd5e1; font-size: 11px; }
        .tx-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .tx-table th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        .tx-table td { border: 1px solid #e2e8f0; padding: 5px 6px; font-family: 'Courier New', Courier, monospace; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .footer { margin-top: 25px; text-align: center; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 12px; font-size: 13px; font-weight: bold; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <table class="header-table">
        <tr>
            <td>
                <div style="display: flex; align-items: center;">
                    {$logoPrintHtml}
                    <div>
                        <h1 style="margin: 0; font-size: 18px; color: #1e1b4b;">{$this->formatName}</h1>
                        <div style="font-size: 11px; color: #64748b;">Official Customer Account Ledger</div>
                    </div>
                </div>
            </td>
            <td class="text-right">
                <div style="font-size: 14px; font-weight: bold;">{$accountTitle}</div>
                <div style="color: #475569;">{$bankName} &bull; Acc: {$accountNumber}</div>
                <div style="color: #64748b;">Period: {$startDate} to {$endDate}</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td><strong>Opening Balance:</strong> PKR {$opening}</td>
            <td><strong>Total Debits:</strong> PKR {$totalDebit}</td>
            <td><strong>Total Credits:</strong> PKR {$totalCredit}</td>
            <td><strong>Closing Balance:</strong> PKR {$closing}</td>
        </tr>
    </table>

    <table class="tx-table">
        <thead>
            <tr>
                <th style="width: 85px;">Date</th>
                <th style="width: 100px;">Ref #</th>
                <th>Description / Narration</th>
                <th class="text-right" style="width: 90px;">Debit</th>
                <th class="text-right" style="width: 90px;">Credit</th>
                <th class="text-right" style="width: 100px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            {$txRows}
        </tbody>
    </table>

    <div class="footer">
        Generated by Account & Transaction Management System &bull; {$timestamp} &bull; Page 1 of 1
    </div>
</body>
</html>
HTML;
    }
}
