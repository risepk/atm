<?php
// app/Services/Statement/HBLStatementRenderer.php
// ================================================================================
// Habib Bank Limited (HBL) Statement Layout Renderer
// Strictly replicated from authentic scanned banking terminal printout
// Reference: media_1790702900738.jpg
// --------------------------------------------------------------------------------
// Key Characteristics:
// 1. Monospaced ASCII dot-matrix layout ('Courier New', Courier, monospace)
// 2. 111-character fixed-pitch grid with vertical pipe dividers '|' across all lines
// 3. Header left: YOUR ACCOUNT STATEMENT, FOR THE PERIOD ENDING: MonDD,YYYY TO MonDD,YYYY
// 4. Customer block: Name, Street/Plaza, Area, City, PK
// 5. Header right: ACCOUNT NO ... OF, Branch Name, ACCOUNT TYPE, CURRENCY, PRINTING DATE,
//    FREQUENCY, PAGE NO, USER
// 6. Brought Forward row on each page with running balance
// 7. Multi-line transaction narration with vertical pipes '|' preserved on all lines
// 8. Pagination with 'Continue on next page' and 'End of Statement'
// 9. Standard regulatory disclaimer: 'This is a system generated account statement and does not require a signature.'
// 10. Strict omission of digital stamp / signature (reserved for physical client stamping)
// ================================================================================

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

class HBLStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'hbl';
    protected string $formatName = 'HBL Statement Layout';
    protected int $linesPerPage = 51; // Optimized for authentic A4 terminal dot-matrix printout (+7 lines over 44)

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    /**
     * Format period date string: e.g. '2025-07-01' -> 'JUL01,2025'
     */
    protected function formatPeriodDate(?string $dateStr): string {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        if (!$ts) return strtoupper($dateStr);
        return strtoupper(date('M', $ts)) . date('d,Y', $ts);
    }

    /**
     * Format transaction table date column: e.g. '2025-07-01' -> '01JUL25' (7 chars)
     */
    protected function formatTransactionDate(?string $dateStr): string {
        if (!$dateStr) return '       ';
        $ts = strtotime($dateStr);
        if (!$ts) return str_pad(substr($dateStr, 0, 7), 7, ' ');
        return date('d', $ts) . strtoupper(date('M', $ts)) . date('y', $ts);
    }

    /**
     * Format printing date: e.g. '10-08-26' (DD-MM-YY)
     */
    protected function formatPrintingDate(?string $dateStr): string {
        if (!empty($dateStr)) {
            $trimmed = trim($dateStr);
            if (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{2}$/', $trimmed)) {
                return str_replace('/', '-', $trimmed);
            }
            $ts = strtotime($trimmed);
            if ($ts) {
                return date('d-m-y', $ts);
            }
            return $trimmed;
        }
        return date('d-m-y');
    }

    /**
     * Split narration text into lines of max 47 characters
     */
    protected function wrapNarration(string $narration, int $maxLen = 47): array {
        $rawLines = preg_split('/\r\n|\r|\n/', trim($narration));
        $lines = [];
        foreach ($rawLines as $raw) {
            $raw = trim($raw);
            if ($raw === '') continue;
            if (mb_strlen($raw) <= $maxLen) {
                $lines[] = $raw;
            } else {
                $wrapped = wordwrap($raw, $maxLen, "\n", true);
                foreach (explode("\n", $wrapped) as $w) {
                    $trimmed = trim($w);
                    if ($trimmed !== '') {
                        $lines[] = $trimmed;
                    }
                }
            }
        }
        return !empty($lines) ? $lines : [''];
    }

    /**
     * Prepare paginated pages of transactions
     */
    protected function paginateTransactions(array $transactions, string $openingBalance, int $maxLinesPerPage = 51): array {
        $pages = [];
        $currentPageTxs = [];
        $currentLinesCount = 0;
        $pageOpeningBalance = $openingBalance;
        $runningBalance = $openingBalance;

        foreach ($transactions as $tx) {
            $narrationLines = $this->wrapNarration($tx['narration'] ?? '');
            $txLineCount = max(1, count($narrationLines));

            // If adding this transaction exceeds max lines and current page already has transactions, push page
            if (($currentLinesCount + $txLineCount > $maxLinesPerPage) && !empty($currentPageTxs)) {
                $pages[] = [
                    'opening_balance' => $pageOpeningBalance,
                    'closing_balance' => $runningBalance,
                    'transactions' => $currentPageTxs
                ];
                $pageOpeningBalance = $runningBalance;
                $currentPageTxs = [];
                $currentLinesCount = 0;
            }

            $runningBalance = $tx['balance'] ?? $runningBalance;
            $currentPageTxs[] = $tx;
            $currentLinesCount += $txLineCount;
        }

        // Push final page (or empty initial page if no transactions)
        $pages[] = [
            'opening_balance' => $pageOpeningBalance,
            'closing_balance' => $runningBalance,
            'transactions' => $currentPageTxs
        ];

        return $pages;
    }

    /**
     * Generate 111-character ASCII text block for a page of transactions
     */
    protected function buildAsciiTable(array $pageTxs, string $pageOpeningBalance, bool $isLastPage): string {
        $topLine    = str_repeat('-', 111);
        $headerLine = '|DATE   |VALUE  |PARTICULARS                                    |       DEBIT|       CREDIT|          BALANCE |';
        $sepLine    = '|-------|-------|-----------------------------------------------|------------|-------------|------------------|';

        $output = [];
        $output[] = $topLine;
        $output[] = $headerLine;
        $output[] = $sepLine;

        // Brought Forward row
        $bfBalanceFormatted = CurrencyHelper::format($pageOpeningBalance);
        $bfLine = '|       |       |' 
                . str_pad('BROUGHT FORWARD', 47, ' ', STR_PAD_RIGHT) 
                . '|' . str_repeat(' ', 12) 
                . '|' . str_repeat(' ', 13) 
                . '|' . str_pad($bfBalanceFormatted, 17, ' ', STR_PAD_LEFT) . ' |';
        $output[] = $bfLine;

        // Transaction rows
        foreach ($pageTxs as $tx) {
            $dateStr = $this->formatTransactionDate($tx['date'] ?? '');
            $valStr  = '       '; // In authentic HBL terminal statements, VALUE is 7 spaces
            $narrationLines = $this->wrapNarration($tx['narration'] ?? '');

            $debitVal = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '';
            $creditVal = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '';
            $balVal = CurrencyHelper::format($tx['balance'] ?? '0.00');

            // Line 0
            $debitCol = !empty($debitVal) ? str_pad($debitVal, 12, ' ', STR_PAD_LEFT) : str_repeat(' ', 12);
            $creditCol = !empty($creditVal) ? str_pad($creditVal, 13, ' ', STR_PAD_LEFT) : str_repeat(' ', 13);
            $balCol = str_pad($balVal, 17, ' ', STR_PAD_LEFT) . ' ';
            $partCol0 = str_pad(mb_substr($narrationLines[0], 0, 47), 47, ' ', STR_PAD_RIGHT);

            $output[] = "|{$dateStr}|{$valStr}|{$partCol0}|{$debitCol}|{$creditCol}|{$balCol}|";

            // Continuation lines
            for ($k = 1; $k < count($narrationLines); $k++) {
                $partColK = str_pad(mb_substr($narrationLines[$k], 0, 47), 47, ' ', STR_PAD_RIGHT);
                $output[] = "|       |       |{$partColK}|            |             |                  |";
            }
        }

        // Bottom continuation or end border
        if (!$isLastPage) {
            // 45 dashes + Continue on next page + 45 dashes = 111 chars
            $output[] = str_repeat('-', 45) . 'Continue on next page' . str_repeat('-', 45);
        } else {
            // 47 dashes + End of Statement + 48 dashes = 111 chars
            $output[] = str_repeat('-', 47) . 'End of Statement' . str_repeat('-', 48);
        }

        $output[] = 'This is a system generated account statement and does not require a signature.';

        return implode("\n", $output);
    }

    /**
     * Shared styles for interactive preview and print
     */
    protected function getSharedStyles(): string {
        return <<<CSS
        /* HBL Authentic Monospaced Statement Typography */
        .hbl-statement-wrapper {
            font-family: 'Courier New', Courier, 'Lucida Console', monospace !important;
            font-size: 11px;
            line-height: 1.35;
            color: #000000;
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .hbl-page {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 10px;
            margin-bottom: 25px;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
        }
        .hbl-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }
        .hbl-header-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            width: 100%;
        }
        .hbl-hdr-left {
            width: 53%;
            text-align: left;
            text-transform: uppercase;
        }
        .hbl-hdr-right {
            width: 47%;
            text-align: left;
        }
        .hbl-doc-title {
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .hbl-period-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 12px;
        }
        .hbl-cust-name {
            font-weight: bold;
            font-size: 11.5px;
            margin-bottom: 1px;
        }
        .hbl-cust-line {
            font-size: 11px;
            line-height: 1.3;
        }
        .hbl-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-family: inherit;
            font-size: 11px;
            line-height: 1.35;
        }
        .hbl-meta-table td {
            padding: 0;
            vertical-align: top;
        }
        .hbl-meta-lbl {
            width: 125px;
            white-space: nowrap;
            font-weight: normal;
        }
        .hbl-meta-sep {
            width: 12px;
            text-align: center;
        }
        .hbl-meta-val {
            white-space: nowrap;
        }
        .hbl-ascii-pre {
            font-family: 'Courier New', Courier, 'Lucida Console', monospace !important;
            font-size: 10.5px;
            line-height: 1.28;
            color: #000000;
            background: transparent;
            margin: 0;
            padding: 0;
            white-space: pre;
            overflow-x: auto;
            letter-spacing: 0px;
        }
CSS;
    }

    /**
     * Render document body (pages containing headers and tables)
     */
    protected function renderDocumentPages(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        $opening = CurrencyHelper::normalizeDecimal($statementData['opening_balance'] ?? '0.00');

        $startDateFormatted = $this->formatPeriodDate($statementData['start_date'] ?? date('Y-07-01'));
        $endDateFormatted = $this->formatPeriodDate($statementData['end_date'] ?? date('Y-06-30'));
        $periodString = "{$startDateFormatted} TO {$endDateFormatted}";

        $accountTitle = htmlspecialchars(strtoupper($account['account_title'] ?? $account['account_identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? $account['account_identifier'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        
        $branchName = !empty($account['branch_name']) ? htmlspecialchars(strtoupper(trim((string)$account['branch_name'])), ENT_QUOTES, 'UTF-8') : 'PESHAWAR-CITY BRANCH';
        $accountType = !empty($account['account_type']) ? htmlspecialchars(trim((string)$account['account_type']), ENT_QUOTES, 'UTF-8') : (!empty($account['account_status']) ? htmlspecialchars(trim((string)$account['account_status']), ENT_QUOTES, 'UTF-8') : 'HBL FreedomAccount');
        
        $currencyRaw = !empty($account['currency']) ? trim((string)$account['currency']) : 'Pakistan Rupee';
        $currency = (stripos($currencyRaw, 'pak') !== false || strtoupper($currencyRaw) === 'PKR') ? 'Pakistan Rupee' : htmlspecialchars($currencyRaw, ENT_QUOTES, 'UTF-8');
        
        $printingDate = $this->formatPrintingDate($account['printing_date'] ?? date('d-m-y'));
        $frequency = !empty($account['frequency']) ? htmlspecialchars(trim((string)$account['frequency']), ENT_QUOTES, 'UTF-8') : 'INTERIM / DUPLICATE';
        $userCode = !empty($account['user_code']) ? htmlspecialchars(trim((string)$account['user_code']), ENT_QUOTES, 'UTF-8') : (!empty($account['generated_by']) ? htmlspecialchars(trim((string)$account['generated_by']), ENT_QUOTES, 'UTF-8') : 'APR801');

        // Customer Address Lines
        $addressLines = [];
        if (!empty($account['address'])) {
            $rawLines = preg_split('/\r\n|\r|\n/', trim((string)$account['address']));
            foreach ($rawLines as $l) {
                $trimmed = trim($l);
                if ($trimmed !== '') {
                    $addressLines[] = htmlspecialchars(strtoupper($trimmed), ENT_QUOTES, 'UTF-8');
                }
            }
        }
        if (empty($addressLines)) {
            $addressLines = ['PESHAWAR', 'PK'];
        } elseif (!in_array(end($addressLines), ['PK', 'PAKISTAN'])) {
            $addressLines[] = 'PK';
        }

        $addressLinesHtml = '';
        foreach ($addressLines as $addrLine) {
            $addressLinesHtml .= '<div class="hbl-cust-line">' . $addrLine . '</div>';
        }

        // Paginate transactions
        $pagesData = $this->paginateTransactions($transactions, $opening, $this->linesPerPage);
        $totalPages = count($pagesData);

        $htmlPages = '';
        foreach ($pagesData as $index => $pData) {
            $pageNum = $index + 1;
            $isLastPage = ($pageNum === $totalPages);
            $pageAscii = $this->buildAsciiTable($pData['transactions'], $pData['opening_balance'], $isLastPage);
            $safeAscii = htmlspecialchars($pageAscii, ENT_QUOTES, 'UTF-8');

            $htmlPages .= <<<HTML
<div class="hbl-page">
    <!-- Header Section (Left Customer Info & Right Bank Metadata) -->
    <div class="hbl-header-grid">
        <div class="hbl-hdr-left">
            <div class="hbl-doc-title">YOUR ACCOUNT STATEMENT</div>
            <div class="hbl-period-title">FOR THE PERIOD ENDING: {$periodString}</div>
            <div class="hbl-cust-name">{$accountTitle}</div>
            {$addressLinesHtml}
        </div>
        <div class="hbl-hdr-right">
            <table class="hbl-meta-table">
                <tbody>
                    <tr>
                        <td class="hbl-meta-lbl">ACCOUNT NO</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">{$accountNumber} OF</td>
                    </tr>
                    <tr>
                        <td class="hbl-meta-lbl"></td>
                        <td class="hbl-meta-sep"></td>
                        <td class="hbl-meta-val">{$branchName}</td>
                    </tr>
                    <tr><td colspan="3" style="height: 8px;"></td></tr>
                    <tr>
                        <td class="hbl-meta-lbl">ACCOUNT TYPE</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">{$accountType}</td>
                    </tr>
                    <tr>
                        <td class="hbl-meta-lbl">CURRENCY</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">{$currency}</td>
                    </tr>
                    <tr>
                        <td class="hbl-meta-lbl">PRINTING DATE</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">{$printingDate}</td>
                    </tr>
                    <tr>
                        <td class="hbl-meta-lbl">FREQUENCY</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">{$frequency}</td>
                    </tr>
                    <tr>
                        <td class="hbl-meta-lbl">PAGE NO</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">&nbsp;&nbsp;{$pageNum}</td>
                    </tr>
                    <tr>
                        <td class="hbl-meta-lbl">USER</td>
                        <td class="hbl-meta-sep">:</td>
                        <td class="hbl-meta-val">{$userCode}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 111-Character Monospaced ASCII Transaction Table -->
    <pre class="hbl-ascii-pre">{$safeAscii}</pre>
</div>
HTML;
        }

        return $htmlPages;
    }

    /**
     * Render statement HTML for interactive screen preview.
     */
    public function renderHtml(array $statementData): string {
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');
        $pagesHtml = $this->renderDocumentPages($statementData);
        $sharedCss = $this->getSharedStyles();

        return <<<HTML
<style>
{$sharedCss}
.hbl-preview-shell {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 16px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}
</style>

<div class="hbl-preview-shell space-y-4">
    <!-- Format Badge & Quick Info Bar -->
    <div class="flex items-center justify-between pb-3 border-b border-gray-200">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                {$this->formatName}
            </span>
            <span class="text-xs text-gray-500 font-mono">Terminal Fixed-Pitch Layout (111 Cols)</span>
        </div>
        <div class="text-xs text-gray-500 font-mono">
            Zero Signature/Stamp (Ready for Physical Stamping)
        </div>
    </div>

    <!-- Quick Financial Summary Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-gray-50 border border-gray-200 rounded text-xs font-mono">
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

    <!-- Authentic Document Container -->
    <div class="hbl-statement-wrapper border border-gray-300 p-4 bg-white overflow-x-auto shadow-xs">
        {$pagesHtml}
    </div>
</div>
HTML;
    }

    /**
     * Render standalone printable A4 HTML document for browser print / Save as PDF.
     */
    public function renderPrint(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $accountTitle = htmlspecialchars($account['account_title'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
        $pagesHtml = $this->renderDocumentPages($statementData);
        $sharedCss = $this->getSharedStyles();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement — {$accountTitle}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            background: #ffffff;
            margin: 0;
            padding: 10px;
            color: #000000;
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
            font-family: Arial, sans-serif;
            font-size: 13px;
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
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
            }
            .hbl-page {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
        {$sharedCss}
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <strong>{$this->formatName}</strong> &bull; Authentic Terminal Printout (Save as PDF / Print)
        </div>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="hbl-statement-wrapper">
        {$pagesHtml}
    </div>
</body>
</html>
HTML;
    }
}
