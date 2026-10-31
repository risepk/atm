<?php
// app/Services/Statement/OnlineStatementRenderer.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

class OnlineStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'online';
    protected string $formatName = 'Online Banking E-Statement';

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

        $netMovement = CurrencyHelper::sub($statementData['total_credit'] ?? '0.00', $statementData['total_debit'] ?? '0.00');
        $isNetPositive = CurrencyHelper::comp($netMovement, '0.00') >= 0;
        $formattedNet = CurrencyHelper::format($netMovement);

        $startDate = htmlspecialchars($statementData['start_date'] ?? date('Y-m-01'), ENT_QUOTES, 'UTF-8');
        $endDate = htmlspecialchars($statementData['end_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8');

        $bankLogo = $account['bank_logo'] ?? null;
        $bankName = htmlspecialchars($account['bank_name'] ?? 'Bank Alfalah Limited', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars($account['account_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $fatherName = htmlspecialchars($account['father_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountType = htmlspecialchars($account['account_type'] ?? 'Current Account', ENT_QUOTES, 'UTF-8');
        $iban = htmlspecialchars($account['iban'] ?? '', ENT_QUOTES, 'UTF-8');
        $address = htmlspecialchars($account['address'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountIdentifier = htmlspecialchars($account['account_identifier'] ?? '', ENT_QUOTES, 'UTF-8');
        $currency = htmlspecialchars($account['currency'] ?? 'PKR', ENT_QUOTES, 'UTF-8');
        $generatedAt = date('d-M-Y H:i:s');

        $logoHtml = '';
        if ($bankLogo) {
            $logoHtml = '<img src="/atm/public/' . htmlspecialchars($bankLogo, ENT_QUOTES, 'UTF-8') . '" alt="' . $bankName . '" class="h-10 max-w-[150px] object-contain">';
        } else {
            $initials = htmlspecialchars(substr($account['bank_name'] ?? 'BNK', 0, 4), ENT_QUOTES, 'UTF-8');
            $logoHtml = '<div class="px-3 py-1.5 rounded bg-blue-900 text-white font-black text-sm tracking-wider uppercase">' . $initials . '</div>';
        }

        $rowsHtml = '';
        if (empty($transactions)) {
            $rowsHtml = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500 italic bg-white">No online transactions recorded for this period.</td></tr>';
        } else {
            foreach ($transactions as $tx) {
                $date = htmlspecialchars($tx['date'] ?? '', ENT_QUOTES, 'UTF-8');
                $valueDate = $date;
                $ref = htmlspecialchars($tx['reference_no'] ?? '-', ENT_QUOTES, 'UTF-8');
                $narration = htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8');
                $debit = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '-';
                $credit = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '-';
                $balance = CurrencyHelper::format($tx['balance'] ?? '0.00');

                $channel = 'Online Banking';
                if (stripos($narration, 'ATM') !== false) {
                    $channel = 'ATM Network';
                } elseif (stripos($narration, 'IBFT') !== false || stripos($narration, 'RAAST') !== false) {
                    $channel = 'Instant Digital (IBFT)';
                } elseif (stripos($narration, 'POS') !== false) {
                    $channel = 'POS Debit';
                }

                $rowsHtml .= '<tr class="border-b border-gray-200 hover:bg-blue-50/40 transition-colors text-xs">';
                $rowsHtml .= '<td class="px-3 py-2 text-gray-700 font-mono whitespace-nowrap">' . $date . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-gray-500 font-mono whitespace-nowrap">' . $valueDate . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-gray-900 font-medium break-words max-w-sm">' . $narration . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-gray-600 font-mono whitespace-nowrap text-center">' . $ref . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-right text-red-600 font-mono font-semibold whitespace-nowrap">' . $debit . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-right text-green-600 font-mono font-semibold whitespace-nowrap">' . $credit . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-right text-gray-900 font-mono font-bold whitespace-nowrap">' . $balance . '</td>';
                $rowsHtml .= '<td class="px-3 py-2 text-center text-gray-600 font-mono whitespace-nowrap"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-semibold uppercase">' . $channel . '</span></td>';
                $rowsHtml .= '</tr>';
            }
        }

        return <<<HTML
<div class="online-statement-preview bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden text-gray-900">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="bg-white p-2 rounded-lg shadow">
                    {$logoHtml}
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white uppercase">{$bankName}</h1>
                    <div class="text-xs text-blue-200 font-medium flex items-center gap-2 mt-0.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                        Online Banking E-Statement (Internet &amp; Mobile Banking)
                    </div>
                </div>
            </div>
            <div class="text-left md:text-right">
                <span class="inline-flex items-center px-2.5 py-1 rounded bg-blue-700/80 border border-blue-500 text-blue-100 text-xs font-semibold uppercase tracking-wider">
                    Show Online Active
                </span>
                <div class="text-xs text-blue-200 font-mono mt-1">Generated: {$generatedAt}</div>
            </div>
        </div>
    </div>

    <!-- Account Details & Summary Grid -->
    <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Account Info -->
            <div class="lg:col-span-2 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider border-b border-gray-200 pb-2 mb-3">Account Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-4 text-xs">
                    <div>
                        <span class="text-gray-600 block text-[11px]">Account Title:</span>
                        <span class="font-bold text-gray-900 text-sm">{$accountTitle}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 block text-[11px]">Account Number:</span>
                        <span class="font-mono font-bold text-blue-900 text-sm">{$accountNumber}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 block text-[11px]">Father / Husband Name:</span>
                        <span class="font-medium text-gray-800">{$fatherName}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 block text-[11px]">Account Identifier:</span>
                        <span class="font-mono font-semibold text-gray-800">{$accountIdentifier}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 block text-[11px]">Account Type:</span>
                        <span class="font-medium text-gray-800">{$accountType}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 block text-[11px]">IBAN:</span>
                        <span class="font-mono text-gray-800">{$iban}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-gray-600 block text-[11px]">Address / Branch:</span>
                        <span class="font-medium text-gray-800">{$address}</span>
                    </div>
                    <div class="sm:col-span-2 text-gray-600 pt-1 border-t border-gray-200 font-mono">
                        Statement Period: <strong class="text-gray-900">{$startDate}</strong> to <strong class="text-gray-900">{$endDate}</strong> &bull; Currency: <strong class="text-gray-900">{$currency}</strong>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Balance Metrics -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col justify-between">
                <h3 class="text-xs font-bold text-blue-900 uppercase tracking-wider border-b border-blue-200 pb-2 mb-3">Balance &amp; Turnover Summary</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Opening Balance:</span>
                        <span class="font-mono font-semibold text-gray-900">PKR {$opening}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-green-800 font-medium">Total Credits (Inflow):</span>
                        <span class="font-mono font-bold text-green-700">+ PKR {$totalCredit}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-800 font-medium">Total Debits (Outflow):</span>
                        <span class="font-mono font-bold text-red-700">- PKR {$totalDebit}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-blue-200">
                        <span class="text-gray-700 font-medium">Net Movement:</span>
                        <span class="font-mono font-bold text-blue-900">PKR {$formattedNet}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t-2 border-blue-300">
                        <span class="text-blue-950 font-bold text-sm">Closing Balance:</span>
                        <span class="font-mono font-black text-blue-950 text-base">PKR {$closing}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Ledger Table -->
        <div>
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Electronic Transaction Record</h3>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100 text-gray-700 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2 text-left">Post Date</th>
                            <th class="px-3 py-2 text-left">Value Date</th>
                            <th class="px-3 py-2 text-left">Description / Particulars</th>
                            <th class="px-3 py-2 text-center">Ref No</th>
                            <th class="px-3 py-2 text-right">Debit (PKR)</th>
                            <th class="px-3 py-2 text-right">Credit (PKR)</th>
                            <th class="px-3 py-2 text-right">Balance (PKR)</th>
                            <th class="px-3 py-2 text-center">Channel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        {$rowsHtml}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Digital Statement Notice Footer -->
        <div class="p-3 bg-gray-50 border border-gray-200 rounded text-center text-xs text-gray-600 font-mono">
            This is an electronically generated statement from the digital banking platform and does not require a physical signature or stamp. For discrepancies, please notify the bank within 30 days.
        </div>
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

        $netMovement = CurrencyHelper::sub($statementData['total_credit'] ?? '0.00', $statementData['total_debit'] ?? '0.00');
        $formattedNet = CurrencyHelper::format($netMovement);

        $startDate = htmlspecialchars($statementData['start_date'] ?? date('Y-m-01'), ENT_QUOTES, 'UTF-8');
        $endDate = htmlspecialchars($statementData['end_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8');

        $bankName = htmlspecialchars($account['bank_name'] ?? 'Bank Alfalah Limited', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars($account['account_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $fatherName = htmlspecialchars($account['father_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountType = htmlspecialchars($account['account_type'] ?? 'Current Account', ENT_QUOTES, 'UTF-8');
        $iban = htmlspecialchars($account['iban'] ?? '', ENT_QUOTES, 'UTF-8');
        $address = htmlspecialchars($account['address'] ?? '', ENT_QUOTES, 'UTF-8');
        $currency = htmlspecialchars($account['currency'] ?? 'PKR', ENT_QUOTES, 'UTF-8');
        $generatedAt = date('d-M-Y H:i:s');

        $rowsHtml = '';
        if (empty($transactions)) {
            $rowsHtml = '<tr><td colspan="7" style="text-align:center; padding: 20px; font-style: italic;">No online transactions recorded for this period.</td></tr>';
        } else {
            foreach ($transactions as $tx) {
                $date = htmlspecialchars($tx['date'] ?? '', ENT_QUOTES, 'UTF-8');
                $ref = htmlspecialchars($tx['reference_no'] ?? '-', ENT_QUOTES, 'UTF-8');
                $narration = htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8');
                $debit = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '-';
                $credit = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '-';
                $balance = CurrencyHelper::format($tx['balance'] ?? '0.00');

                $channel = 'Online';
                if (stripos($narration, 'ATM') !== false) {
                    $channel = 'ATM';
                } elseif (stripos($narration, 'IBFT') !== false) {
                    $channel = 'IBFT';
                }

                $rowsHtml .= '<tr>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-family: monospace;">' . $date . '</td>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb;">' . $narration . '</td>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center; font-family: monospace;">' . $ref . '</td>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-family: monospace; color: #b91c1c;">' . $debit . '</td>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-family: monospace; color: #15803d;">' . $credit . '</td>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-family: monospace; font-weight: bold;">' . $balance . '</td>';
                $rowsHtml .= '<td style="padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center; font-family: monospace; font-size: 11px;">' . $channel . '</td>';
                $rowsHtml .= '</tr>';
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Banking E-Statement - {$accountTitle}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #111827; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 16px; }
        .bank-title { font-size: 20px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
        .sub-title { font-size: 11px; color: #4b5563; font-weight: 600; }
        .meta-box { width: 100%; border-collapse: collapse; margin-bottom: 16px; background-color: #f9fafb; border: 1px solid #e5e7eb; }
        .meta-box td { padding: 6px 10px; font-size: 11px; vertical-align: top; }
        .meta-label { color: #4b5563; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        .meta-val { font-weight: bold; color: #111827; }
        .summary-box { display: flex; justify-content: space-between; border: 1px solid #bfdbfe; background: #eff6ff; padding: 10px; margin-bottom: 16px; font-family: monospace; }
        .tx-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .tx-table th { background-color: #1e3a8a; color: #ffffff; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
        .notice { text-align: center; font-size: 10px; color: #6b7280; font-family: monospace; margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="bank-title">{$bankName}</div>
            <div class="sub-title">Online Banking Electronic Statement / Advice</div>
        </div>
        <div style="text-align: right; font-family: monospace; font-size: 11px;">
            <div><strong>E-STATEMENT</strong></div>
            <div>Generated: {$generatedAt}</div>
        </div>
    </div>

    <table class="meta-box">
        <tr>
            <td width="50%">
                <div class="meta-label">Account Title</div>
                <div class="meta-val" style="font-size: 13px;">{$accountTitle}</div>
                <div class="meta-label" style="margin-top: 6px;">Father / Husband Name</div>
                <div class="meta-val">{$fatherName}</div>
                <div class="meta-label" style="margin-top: 6px;">Address / Branch</div>
                <div class="meta-val">{$address}</div>
            </td>
            <td width="50%">
                <div class="meta-label">Account Number</div>
                <div class="meta-val" style="font-size: 13px; font-family: monospace;">{$accountNumber}</div>
                <div class="meta-label" style="margin-top: 6px;">Account Type / Currency</div>
                <div class="meta-val">{$accountType} &bull; {$currency}</div>
                <div class="meta-label" style="margin-top: 6px;">IBAN</div>
                <div class="meta-val" style="font-family: monospace;">{$iban}</div>
                <div class="meta-label" style="margin-top: 6px;">Statement Period</div>
                <div class="meta-val" style="font-family: monospace;">{$startDate} to {$endDate}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px; font-family: monospace; font-size: 11px; background: #f3f4f6; border: 1px solid #d1d5db;">
        <tr>
            <td style="padding: 6px 10px;">Opening: <strong>PKR {$opening}</strong></td>
            <td style="padding: 6px 10px; color: #15803d;">Credits: <strong>+PKR {$totalCredit}</strong></td>
            <td style="padding: 6px 10px; color: #b91c1c;">Debits: <strong>-PKR {$totalDebit}</strong></td>
            <td style="padding: 6px 10px;">Net: <strong>PKR {$formattedNet}</strong></td>
            <td style="padding: 6px 10px; font-weight: bold; background: #e5e7eb;">Closing: <strong>PKR {$closing}</strong></td>
        </tr>
    </table>

    <table class="tx-table">
        <thead>
            <tr>
                <th style="width: 85px;">Date</th>
                <th>Description / Particulars</th>
                <th style="width: 80px; text-align: center;">Ref No</th>
                <th style="width: 90px; text-align: right;">Debit (PKR)</th>
                <th style="width: 90px; text-align: right;">Credit (PKR)</th>
                <th style="width: 95px; text-align: right;">Balance (PKR)</th>
                <th style="width: 60px; text-align: center;">Channel</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <div class="notice">
        This is an electronically generated e-statement from the digital banking network and does not require a physical signature or stamp.
    </div>
</body>
</html>
HTML;
    }
}
