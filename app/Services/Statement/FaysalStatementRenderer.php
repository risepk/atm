<?php
// app/Services/Statement/FaysalStatementRenderer.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

class FaysalStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'faysal';
    protected string $formatName = 'Faysal Bank Statement Layout';

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    /**
     * Format date as DD-MM-YYYY (e.g. 21-04-2023)
     */
    protected function formatDateDmy(?string $dateStr): string {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        return $ts ? date('d-m-Y', $ts) : $dateStr;
    }

    /**
     * Format summary date as DD-MON-YY uppercase (e.g. 30-JUN-23)
     */
    protected function formatSummaryDate(?string $dateStr): string {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        return $ts ? strtoupper(date('d-M-y', $ts)) : $dateStr;
    }

    /**
     * Render the Faysal Bank SVG logo if no custom uploaded logo exists
     */
    protected function getDefaultLogoSvg(): string {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 55" class="h-10 w-auto">
    <!-- Geometric Diamond / Islamic Star Pattern Emblem -->
    <g transform="translate(185, 27) scale(1.1)">
        <!-- Outer faceted star/diamond -->
        <polygon points="0,-20 14,-14 20,0 14,14 0,20 -14,14 -20,0 -14,-14" fill="#002855" stroke="#001838" stroke-width="1"/>
        <!-- Inner facets -->
        <polygon points="0,-14 10,-10 14,0 10,10 0,14 -10,10 -14,0 -10,-10" fill="#ffffff"/>
        <!-- Center geometric core -->
        <polygon points="0,-9 6,-6 9,0 6,6 0,9 -6,6 -9,0 -6,-6" fill="#002855"/>
        <circle cx="0" cy="0" r="3.5" fill="#c49a45"/>
    </g>
    <!-- Brand Wordmark "faysalbank" -->
    <text x="5" y="38" font-family="'Helvetica Neue', Arial, sans-serif" font-size="34" font-weight="900" letter-spacing="-1" fill="#002855">faysal<tspan font-weight="400">bank</tspan></text>
</svg>
SVG;
    }

    /**
     * Render realistic branch stamp & signature
     */
    protected function getBranchStampHtml(): string {
        return <<<HTML
<div class="relative w-44 h-32 flex items-center justify-center select-none pointer-events-none">
    <!-- Circular Branch Stamp -->
    <svg class="absolute w-28 h-28 opacity-80" viewBox="0 0 120 120">
        <circle cx="60" cy="60" r="54" fill="none" stroke="#2554a0" stroke-width="2" stroke-dasharray="3,1"/>
        <circle cx="60" cy="60" r="48" fill="none" stroke="#2554a0" stroke-width="1"/>
        <circle cx="60" cy="60" r="34" fill="none" stroke="#2554a0" stroke-width="1"/>
        
        <!-- Stamp Text Path Top -->
        <path id="stampTop" d="M 18,60 A 42,42 0 0,1 102,60" fill="none"/>
        <text font-family="Arial, sans-serif" font-size="7.5" font-weight="bold" fill="#2554a0" letter-spacing="1.5">
            <textPath href="#stampTop" startOffset="50%" text-anchor="middle">FAYSAL BANK LTD</textPath>
        </text>

        <!-- Stamp Text Path Bottom -->
        <path id="stampBottom" d="M 102,60 A 42,42 0 0,1 18,60" fill="none"/>
        <text font-family="Arial, sans-serif" font-size="7" font-weight="bold" fill="#2554a0" letter-spacing="1">
            <textPath href="#stampBottom" startOffset="50%" text-anchor="middle">ISLAMABAD BRANCH</textPath>
        </text>

        <!-- Center Stamp Info -->
        <text x="60" y="55" font-family="Arial, sans-serif" font-size="6.5" font-weight="bold" fill="#2554a0" text-anchor="middle">3275</text>
        <text x="60" y="65" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="#2554a0" text-anchor="middle">VERIFIED</text>
    </svg>

    <!-- Handwritten Red Signature Loop -->
    <svg class="absolute w-36 h-28 -rotate-12 translate-x-2 -translate-y-1" viewBox="0 0 160 120">
        <path d="M 20,70 C 40,30 65,10 85,25 C 105,40 100,85 70,80 C 45,75 55,45 80,50 C 110,55 140,40 155,65 M 40,85 Q 90,60 145,75" 
              fill="none" stroke="#cc1f2f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" opacity="0.88"/>
    </svg>
</div>
HTML;
    }

    /**
     * Render statement HTML markup for interactive screen preview.
     */
    public function renderHtml(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        $startDateFormatted = $this->formatDateDmy($statementData['start_date'] ?? date('Y-m-01'));
        $endDateFormatted = $this->formatDateDmy($statementData['end_date'] ?? date('Y-m-d'));
        $summaryDate = $this->formatSummaryDate($statementData['end_date'] ?? date('Y-m-d'));

        $statementDateTime = date('d-m-Y H:i:s');
        $txCount = count($transactions);

        // Account Details
        $accountNo = htmlspecialchars($account['account_number'] ?? $account['account_identifier'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars(strtoupper($account['account_title'] ?? $account['account_identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        // Address & Father name / Phone
        $addressParts = [];
        if (!empty($account['address'])) {
            $addressParts[] = trim($account['address']);
        }
        if (!empty($account['father_name'])) {
            $addressParts[] = 'S/O ' . trim($account['father_name']);
        }
        $address = !empty($addressParts) ? htmlspecialchars(strtoupper(implode('   ', $addressParts)), ENT_QUOTES, 'UTF-8') : '';

        $rawType = trim((string)($account['account_type'] ?? 'CURRENT'));
        if (stripos($rawType, 'saving') !== false) {
            $depositType = 'SAVINGS';
        } elseif (stripos($rawType, 'current') !== false || empty($rawType)) {
            $depositType = 'CURRENT';
        } else {
            $depositType = htmlspecialchars(strtoupper($rawType), ENT_QUOTES, 'UTF-8');
        }
        $currency = htmlspecialchars(strtoupper($account['currency'] ?? 'PKR'), ENT_QUOTES, 'UTF-8');

        // Bank Logo: Priority 1 = Uploaded custom logo; Priority 2 = Authentic Faysal Bank SVG logo
        $logoHtml = '';
        if (!empty($account['bank_logo'])) {
            $logoHtml = '<img src="/atm/public/' . htmlspecialchars($account['bank_logo'], ENT_QUOTES, 'UTF-8') . '" alt="Faysal Bank" class="h-11 max-w-[210px] object-contain">';
        } else {
            $logoHtml = $this->getDefaultLogoSvg();
        }

        // Build Transaction Rows (Compact, no row divider borders, matching client screenshot)
        $rowsHtml = '';
        if (empty($transactions)) {
            $rowsHtml .= '<tr><td colspan="7" class="py-6 text-center text-gray-400 italic text-xs">No transactions recorded for this period.</td></tr>';
        } else {
            foreach ($transactions as $tx) {
                $postDate = htmlspecialchars($this->formatDateDmy($tx['date'] ?? ''), ENT_QUOTES, 'UTF-8');
                $effDate = $postDate;
                $narration = htmlspecialchars(strtoupper($tx['narration'] ?? ''), ENT_QUOTES, 'UTF-8');
                $refNo = !empty($tx['reference_no']) && $tx['reference_no'] !== '-' ? htmlspecialchars(strtoupper($tx['reference_no']), ENT_QUOTES, 'UTF-8') : '';
                
                $debitVal = (string)($tx['debit'] ?? 0);
                $creditVal = (string)($tx['credit'] ?? 0);
                
                $withdrawalStr = CurrencyHelper::comp($debitVal, '0.00') > 0 ? CurrencyHelper::format($debitVal) : '0.00';
                $depositStr = CurrencyHelper::comp($creditVal, '0.00') > 0 ? CurrencyHelper::format($creditVal) : '0.00';
                $balanceStr = CurrencyHelper::format($tx['balance'] ?? '0.00');

                $rowsHtml .= '<tr class="text-[10.5px] leading-tight text-gray-900">';
                $rowsHtml .= '<td class="py-1 px-1.5 font-mono whitespace-nowrap align-top">' . $postDate . '</td>';
                $rowsHtml .= '<td class="py-1 px-1.5 font-mono whitespace-nowrap align-top">' . $effDate . '</td>';
                $rowsHtml .= '<td class="py-1 px-1.5 font-sans align-top break-words max-w-sm">' . $narration . '</td>';
                $rowsHtml .= '<td class="py-1 px-1.5 font-mono align-top whitespace-nowrap">' . $refNo . '</td>';
                $rowsHtml .= '<td class="py-1 px-1.5 font-mono text-right align-top whitespace-nowrap">' . $withdrawalStr . '</td>';
                $rowsHtml .= '<td class="py-1 px-1.5 font-mono text-right align-top whitespace-nowrap">' . $depositStr . '</td>';
                $rowsHtml .= '<td class="py-1 px-1.5 font-mono text-right align-top whitespace-nowrap font-normal">' . $balanceStr . '</td>';
                $rowsHtml .= '</tr>';
            }
        }

        return <<<HTML
<div class="faysal-statement-container bg-white text-gray-900 font-sans p-4 sm:p-8 border border-gray-300 rounded-lg shadow-sm space-y-3 max-w-5xl mx-auto" style="font-family: Arial, Helvetica, sans-serif;">
    <!-- Format Badge (UI Only) -->
    <div class="flex items-center justify-between pb-1.5 border-b border-gray-200">
        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-[#002855] text-white tracking-wide">
            Faysal Bank Statement Layout
        </span>
        <span class="text-[11px] font-mono text-gray-500">Authentic Client Physical Reproduction</span>
    </div>

    <!-- Top Header: Logo (Left), Account Statement (Center), Statement Date (Right) -->
    <div class="grid grid-cols-12 items-center gap-2 pt-1">
        <div class="col-span-12 sm:col-span-4 flex items-center justify-start">
            {$logoHtml}
        </div>
        <div class="col-span-12 sm:col-span-4 text-center">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight" style="margin: 0; text-transform: none;">Account Statement</h1>
            <div class="text-xs sm:text-sm font-bold text-gray-900 mt-1">
                Statement Period From: <span class="underline underline-offset-4 decoration-1 font-bold">{$startDateFormatted}</span>
                To: <span class="underline underline-offset-4 decoration-1 font-bold">{$endDateFormatted}</span>
            </div>
        </div>
        <div class="col-span-12 sm:col-span-4 text-right text-xs text-gray-800">
            <div>Statement Date: <span class="font-normal">{$statementDateTime}</span></div>
            <div class="mt-0.5 font-bold">Page 1 of 1</div>
        </div>
    </div>

    <!-- Account Details Block (Labels bold, values regular weight, no artificial borders) -->
    <div class="pt-2 pb-1 text-xs leading-relaxed">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-y-1 gap-x-4">
            <!-- Row 1: Account No & Title Of Account -->
            <div class="sm:col-span-6 flex">
                <span class="font-bold text-gray-900 w-28 whitespace-nowrap">Account No :</span>
                <span class="font-mono font-normal text-gray-900">{$accountNo}</span>
            </div>
            <div class="sm:col-span-6 flex">
                <span class="font-bold text-gray-900 w-36 whitespace-nowrap">Title Of Account :</span>
                <span class="font-normal text-gray-900">{$accountTitle}</span>
            </div>

            <!-- Row 2: Address -->
            <div class="sm:col-span-12 flex">
                <span class="font-bold text-gray-900 w-28 whitespace-nowrap">Address :</span>
                <span class="font-normal text-gray-800">{$address}</span>
            </div>

            <!-- Row 3: Deposit Type & Currency -->
            <div class="sm:col-span-6 flex">
                <span class="font-bold text-gray-900 w-28 whitespace-nowrap">Deposit Type :</span>
                <span class="font-normal text-gray-900">{$depositType}</span>
            </div>
            <div class="sm:col-span-6 flex">
                <span class="font-bold text-gray-900 w-36 whitespace-nowrap">Currency :</span>
                <span class="font-normal text-gray-900">{$currency}</span>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse" style="font-family: Arial, Helvetica, sans-serif;">
            <thead>
                <tr class="bg-[#eaedf1] text-gray-900 text-[11px] font-bold border-y border-gray-600">
                    <th class="py-1.5 px-1.5 text-left whitespace-nowrap" style="width: 82px;">Posting<br>Date</th>
                    <th class="py-1.5 px-1.5 text-left whitespace-nowrap" style="width: 82px;">Effective<br>Date</th>
                    <th class="py-1.5 px-1.5 text-left">Narration</th>
                    <th class="py-1.5 px-1.5 text-left whitespace-nowrap" style="width: 115px;">Reference No</th>
                    <th class="py-1.5 px-1.5 text-right whitespace-nowrap" style="width: 95px;">Withdrawal</th>
                    <th class="py-1.5 px-1.5 text-right whitespace-nowrap" style="width: 95px;">Deposit</th>
                    <th class="py-1.5 px-1.5 text-right whitespace-nowrap" style="width: 105px;">Balance</th>
                </tr>
            </thead>
            <tbody>
                {$rowsHtml}
                <!-- Totals Line (Enclosed in top and bottom borders) -->
                <tr class="font-bold border-y border-gray-600 text-[11px]">
                    <td colspan="4" class="py-1 px-1.5"></td>
                    <td class="py-1 px-1.5 text-right whitespace-nowrap">{$totalDebit}</td>
                    <td class="py-1 px-1.5 text-right whitespace-nowrap">{$totalCredit}</td>
                    <td class="py-1 px-1.5"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Account Summary Box (Single open rectangle, matching client photo) -->
    <div class="border border-gray-600 bg-[#f8fafc] p-2.5 text-xs">
        <table class="w-full border-collapse">
            <tr>
                <td class="font-bold text-gray-900 align-top" style="width: 45%;">
                    No. of Transactions ({$txCount})
                </td>
                <td class="align-top" style="width: 55%;">
                    <table class="w-full border-collapse text-xs">
                        <tr>
                            <td class="font-bold text-gray-900 text-left">Ending Balance as of {$summaryDate}</td>
                            <td class="font-bold text-gray-900 text-right font-mono" style="width: 115px;">{$closing}</td>
                        </tr>
                        <tr>
                            <td class="font-bold text-gray-900 text-left pt-0.5">Available Balance as of {$summaryDate}</td>
                            <td class="font-bold text-gray-900 text-right font-mono pt-0.5" style="width: 115px;">{$closing}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer Disclaimers (Left-aligned, stamp and signature omitted for physical client signing) -->
    <div class="pt-2">
        <div class="max-w-2xl text-[9.5px] text-gray-800 leading-tight space-y-1.5 font-sans">
            <p class="font-bold">
                ***AS PER SBP MANDATE PLEASE SUBMIT AN ATTESTED COPY OF YOUR VALID CNIC, IF NOT PROVIDED EARLIER. NON-PROVISION CAN HAMPER THE SERVICES***
            </p>
            <p class="font-bold">
                ** FOR BANK'S PROFILE PLEASE VISIT OUR WEBSITE &ldquo;WWW.FAYSALBANK.COM&rdquo; **
            </p>
            <p class="font-medium text-gray-700">
                &ldquo;FOR COMPLAINTS WHICH REMAIN UNRESOLVED BEYOND 45 DAYS, YOU MAY WRITE TO BANKING MOHTASIB PAKISTAN, SHAHEEN COMPLEX, M.R KIYANI ROAD, KARACHI OR VISIT WWW.BANKINGMOHTASIB.GOV.PK&rdquo;
            </p>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Render printable standalone HTML document for A4 printing / PDF export.
     */
    public function renderPrint(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        $startDateFormatted = $this->formatDateDmy($statementData['start_date'] ?? date('Y-m-01'));
        $endDateFormatted = $this->formatDateDmy($statementData['end_date'] ?? date('Y-m-d'));
        $summaryDate = $this->formatSummaryDate($statementData['end_date'] ?? date('Y-m-d'));

        $statementDateTime = date('d-m-Y H:i:s');
        $txCount = count($transactions);

        $accountNo = htmlspecialchars($account['account_number'] ?? $account['account_identifier'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars(strtoupper($account['account_title'] ?? $account['account_identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        $addressParts = [];
        if (!empty($account['address'])) {
            $addressParts[] = trim($account['address']);
        }
        if (!empty($account['father_name'])) {
            $addressParts[] = 'S/O ' . trim($account['father_name']);
        }
        $address = !empty($addressParts) ? htmlspecialchars(strtoupper(implode('   ', $addressParts)), ENT_QUOTES, 'UTF-8') : '';

        $rawType = trim((string)($account['account_type'] ?? 'CURRENT'));
        if (stripos($rawType, 'saving') !== false) {
            $depositType = 'SAVINGS';
        } elseif (stripos($rawType, 'current') !== false || empty($rawType)) {
            $depositType = 'CURRENT';
        } else {
            $depositType = htmlspecialchars(strtoupper($rawType), ENT_QUOTES, 'UTF-8');
        }
        $currency = htmlspecialchars(strtoupper($account['currency'] ?? 'PKR'), ENT_QUOTES, 'UTF-8');

        // Bank Logo
        $logoHtml = '';
        if (!empty($account['bank_logo'])) {
            $logoHtml = '<img src="/atm/public/' . htmlspecialchars($account['bank_logo'], ENT_QUOTES, 'UTF-8') . '" alt="Faysal Bank" style="max-height: 48px; max-width: 220px; object-fit: contain;">';
        } else {
            $logoHtml = $this->getDefaultLogoSvg();
        }

        // Build Table Rows (Compact, no row divider borders, matching client screenshot)
        $rowsHtml = '';
        if (empty($transactions)) {
            $rowsHtml .= '<tr><td colspan="7" style="padding: 16px; text-align: center; color: #666; font-style: italic;">No transactions recorded for this period.</td></tr>';
        } else {
            foreach ($transactions as $tx) {
                $postDate = htmlspecialchars($this->formatDateDmy($tx['date'] ?? ''), ENT_QUOTES, 'UTF-8');
                $effDate = $postDate;
                $narration = htmlspecialchars(strtoupper($tx['narration'] ?? ''), ENT_QUOTES, 'UTF-8');
                $refNo = !empty($tx['reference_no']) && $tx['reference_no'] !== '-' ? htmlspecialchars(strtoupper($tx['reference_no']), ENT_QUOTES, 'UTF-8') : '';
                
                $debitVal = (string)($tx['debit'] ?? 0);
                $creditVal = (string)($tx['credit'] ?? 0);
                
                $withdrawalStr = CurrencyHelper::comp($debitVal, '0.00') > 0 ? CurrencyHelper::format($debitVal) : '0.00';
                $depositStr = CurrencyHelper::comp($creditVal, '0.00') > 0 ? CurrencyHelper::format($creditVal) : '0.00';
                $balanceStr = CurrencyHelper::format($tx['balance'] ?? '0.00');

                $rowsHtml .= '<tr style="font-size: 10.5px;">';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: monospace; white-space: nowrap;">' . $postDate . '</td>';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: monospace; white-space: nowrap;">' . $effDate . '</td>';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: Arial, sans-serif; word-break: break-word;">' . $narration . '</td>';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: monospace; white-space: nowrap;">' . $refNo . '</td>';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: monospace; text-align: right; white-space: nowrap;">' . $withdrawalStr . '</td>';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: monospace; text-align: right; white-space: nowrap;">' . $depositStr . '</td>';
                $rowsHtml .= '<td style="padding: 2.5px 4px; font-family: monospace; text-align: right; white-space: nowrap; font-weight: normal;">' . $balanceStr . '</td>';
                $rowsHtml .= '</tr>';
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement — Faysal Bank</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #fff;
            margin: 0;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .account-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11px;
            line-height: 1.4;
        }
        .account-details td {
            padding: 1.5px 4px;
            vertical-align: top;
        }
        .account-details .label {
            font-weight: bold;
            color: #111;
        }
        .account-details .val {
            font-weight: normal;
            color: #111;
        }
        .tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .tx-table th {
            background-color: #eaedf1 !important;
            color: #111;
            font-size: 10.5px;
            font-weight: bold;
            padding: 5px 4px;
            border-top: 1px solid #555;
            border-bottom: 1px solid #555;
            text-align: left;
        }
        .tx-table td {
            padding: 2.5px 4px;
            font-size: 10.5px;
            vertical-align: top;
            border: none;
        }
        .totals-row td {
            border-top: 1px solid #555 !important;
            border-bottom: 1px solid #555 !important;
            padding: 3px 4px;
            font-family: monospace;
            font-weight: bold;
            font-size: 10.5px;
        }
        .summary-box {
            border: 1px solid #555;
            background-color: #fafbfc !important;
            padding: 6px 10px;
            margin-top: 5px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .disclaimer-section {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            line-height: 1.25;
            page-break-inside: avoid;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Print toolbar -->
    <div class="no-print" style="background: #eef2f7; padding: 8px 14px; margin-bottom: 12px; border: 1px solid #cbd5e1; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold; color: #002855;">Faysal Bank Account Statement — Printable A4 Reproduction</span>
        <div>
            <button onclick="window.print()" style="padding: 5px 16px; font-weight: bold; background: #002855; color: white; border: none; border-radius: 3px; cursor: pointer;">Print / Save as PDF</button>
            <button onclick="window.close()" style="padding: 5px 12px; margin-left: 8px; background: #e2e8f0; border: 1px solid #94a3b8; border-radius: 3px; cursor: pointer;">Close</button>
        </div>
    </div>

    <!-- Header Table: Logo (Left), Account Statement (Center), Statement Date (Right) -->
    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                {$logoHtml}
            </td>
            <td style="width: 44%; text-align: center;">
                <h1 style="font-size: 18px; font-weight: bold; margin: 0; letter-spacing: -0.2px;">Account Statement</h1>
                <div style="font-size: 11.5px; font-weight: bold; margin-top: 3px;">
                    Statement Period From: <span style="text-decoration: underline; font-weight: bold;">{$startDateFormatted}</span>
                    To: <span style="text-decoration: underline; font-weight: bold;">{$endDateFormatted}</span>
                </div>
            </td>
            <td style="width: 26%; text-align: right; font-size: 11px;">
                <div>Statement Date: {$statementDateTime}</div>
                <div style="margin-top: 2px; font-weight: bold;">Page 1 of 1</div>
            </td>
        </tr>
    </table>

    <!-- Account Details Table (Clean whitespace, no borders, labels bold, values normal weight) -->
    <table class="account-details">
        <tr>
            <td style="width: 14%;" class="label">Account No :</td>
            <td style="width: 36%; font-family: monospace;" class="val">{$accountNo}</td>
            <td style="width: 16%;" class="label">Title Of Account :</td>
            <td style="width: 34%;" class="val">{$accountTitle}</td>
        </tr>
        <tr>
            <td class="label">Address :</td>
            <td colspan="3" class="val">{$address}</td>
        </tr>
        <tr>
            <td class="label">Deposit Type :</td>
            <td class="val">{$depositType}</td>
            <td class="label">Currency :</td>
            <td class="val">{$currency}</td>
        </tr>
    </table>

    <!-- Transaction Table -->
    <table class="tx-table">
        <thead>
            <tr>
                <th style="width: 78px;">Posting<br>Date</th>
                <th style="width: 78px;">Effective<br>Date</th>
                <th>Narration</th>
                <th style="width: 110px;">Reference No</th>
                <th style="width: 90px; text-align: right;">Withdrawal</th>
                <th style="width: 90px; text-align: right;">Deposit</th>
                <th style="width: 100px; text-align: right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
            <!-- Totals Row (Enclosed in top & bottom borders, matching screenshot) -->
            <tr class="totals-row">
                <td colspan="4"></td>
                <td style="text-align: right;">{$totalDebit}</td>
                <td style="text-align: right;">{$totalCredit}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Account Summary Box (Single open rectangle, matching client photo) -->
    <div class="summary-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="font-weight: bold; font-size: 11px; vertical-align: top; width: 45%;">
                    No. of Transactions ({$txCount})
                </td>
                <td style="vertical-align: top; width: 55%;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr>
                            <td style="font-weight: bold; text-align: left;">Ending Balance as of {$summaryDate}</td>
                            <td style="font-family: monospace; font-weight: bold; text-align: right; width: 105px;">{$closing}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; text-align: left; padding-top: 1px;">Available Balance as of {$summaryDate}</td>
                            <td style="font-family: monospace; font-weight: bold; text-align: right; padding-top: 1px; width: 105px;">{$closing}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Official Disclaimers (Left-aligned ~62% width, right area left open for physical stamp & signature) -->
    <div class="disclaimer-section">
        <div style="max-width: 62%; color: #222;">
            <p style="margin: 0 0 5px 0; font-weight: bold;">
                ***AS PER SBP MANDATE PLEASE SUBMIT AN ATTESTED COPY OF YOUR VALID CNIC, IF NOT PROVIDED EARLIER. NON-PROVISION CAN HAMPER THE SERVICES***
            </p>
            <p style="margin: 0 0 5px 0; font-weight: bold;">
                ** FOR BANK'S PROFILE PLEASE VISIT OUR WEBSITE &ldquo;WWW.FAYSALBANK.COM&rdquo; **
            </p>
            <p style="margin: 0; color: #333; font-weight: 500;">
                &ldquo;FOR COMPLAINTS WHICH REMAIN UNRESOLVED BEYOND 45 DAYS, YOU MAY WRITE TO BANKING MOHTASIB PAKISTAN, SHAHEEN COMPLEX, M.R KIYANI ROAD, KARACHI OR VISIT WWW.BANKINGMOHTASIB.GOV.PK&rdquo;
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
