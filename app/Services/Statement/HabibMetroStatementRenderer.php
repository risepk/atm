<?php
// app/Services/Statement/HabibMetroStatementRenderer.php
// ================================================================================
// Habib Metropolitan Bank Ltd. (Habib Metro / HMB) Statement Layout Renderer
// Replicated strictly from authentic scanned banking statement
// Reference: media_1791044654672.png / Habib Metropolitan Bank Ltd. PDF
// --------------------------------------------------------------------------------
// Key Characteristics:
// 1. Top Center: HABIB METROPOLITAN BANK LTD. (Serif typography)
// 2. Top Right: Crisp Authentic Square QR Code Box
// 3. Header Left: Customer Title (IRFAN ULLAH) & Multi-line Address
// 4. Header Right: Bordered Bank Metadata Grid
//    - BRANCH NAME (e.g. IBB - Tarnol - Islamabad)
//    - A/C TYPE (e.g. SIRAT Current Plus Account) & A/C NUMBER (e.g. 6-98-83-29310-714-109975)
//    - IBAN (e.g. PK13MPBL9883727140109975)
//    - 4-Col Grid: CURRENCY (PKR) | FROM (d-M-Y) | TO (d-M-Y) | PRINTED ON (d-M-Y)
// 5. Table Header: Dark Charcoal Background (#595959), White Bold Text
//    - Date (12%) | Particulars (42%) | Debit (15%) | Credit (15%) | Balance (16%)
// 6. Page 1 Opening Balance row:
//    - Date/Particulars/Debit blank, Credit: "Opening Balance :", Balance: formatted opening balance
// 7. End-of-Date Running Balance:
//    - Balance displayed on the last transaction row of each distinct date
// 8. Multi-page Pagination:
//    - Repeating authentic header & metadata on each page
//    - Bottom Right: "Page X of Y"
// 9. Strict omission of digital stamp / signature (reserved for physical stamping)
// ================================================================================

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Helpers/qrcode.php';
require_once __DIR__ . '/../../Core/Session.php';

class HabibMetroStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'habibmetro';
    protected string $formatName = 'Habib Metropolitan Bank Statement Layout';

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    /**
     * Format date as d-M-Y (e.g. 24-Mar-2026)
     */
    protected function formatDate(?string $dateStr): string {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        if (!$ts) return $dateStr;
        return date('d-M-Y', $ts);
    }

    /**
     * Split narration into lines of max 55 characters
     */
    protected function wrapNarration(string $narration, int $maxLen = 55): array {
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
     * Generate an authentic, fully scannable SVG QR Code (containing IBAN and Account Title)
     */
    protected function generateQrCodeSvg(string $payload): string {
        try {
            return QRCode::svg($payload, 68, 2);
        } catch (\Throwable $e) {
            // Fallback safe square if any error occurs
            return '<svg width="68" height="68" viewBox="0 0 37 37" xmlns="http://www.w3.org/2000/svg" style="display:block;"><rect width="37" height="37" fill="#ffffff" /><rect x="2" y="2" width="7" height="7" fill="#000000" /><rect x="28" y="2" width="7" height="7" fill="#000000" /><rect x="2" y="28" width="7" height="7" fill="#000000" /></svg>';
        }
    }

    /**
     * Paginate transactions based on line heights to fit A4 pages cleanly.
     * Page 1 includes the Opening Balance row (takes 1 line).
     * Subsequent pages continue directly.
     * Configured for 33 units per page (+6 lines over original 27 for full A4 coverage).
     */
    protected function paginateTransactions(array $transactions, int $maxUnitsPerPage = 33): array {
        $pages = [];
        $currentPage = [];
        $currentUnits = 0;
        $isFirstPage = true;

        foreach ($transactions as $tx) {
            $narrationLines = $this->wrapNarration($tx['narration'] ?? '');
            $txUnits = max(1, count($narrationLines));

            $pageLimit = $isFirstPage ? ($maxUnitsPerPage - 1) : $maxUnitsPerPage;

            if (($currentUnits + $txUnits > $pageLimit) && !empty($currentPage)) {
                $pages[] = $currentPage;
                $currentPage = [];
                $currentUnits = 0;
                $isFirstPage = false;
            }

            $currentPage[] = $tx;
            $currentUnits += $txUnits;
        }

        if (!empty($currentPage) || empty($pages)) {
            $pages[] = $currentPage;
        }

        return $pages;
    }

    /**
     * Shared CSS Styles for Screen Preview and A4 Printing
     */
    protected function getSharedStyles(): string {
        return <<<CSS
        .hmb-statement-wrapper {
            font-family: Arial, Helvetica, sans-serif !important;
            color: #000000;
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            box-sizing: border-box;
            font-size: 11px;
            line-height: 1.3;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .hmb-page {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 10px 18px 10px;
            margin-bottom: 30px;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
            position: relative;
        }
        .hmb-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }
        
        /* Top Bank Title Bar */
        .hmb-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            width: 100%;
        }
        .hmb-title-col {
            flex: 1;
            text-align: center;
            padding-top: 6px;
        }
        .hmb-bank-title {
            font-family: 'Times New Roman', Times, Georgia, serif;
            font-size: 20px;
            font-weight: 500;
            letter-spacing: 0.6px;
            color: #000000;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.15;
            white-space: nowrap;
        }
        .hmb-bank-title .hmb-title-initial {
            font-size: 25px;
            font-weight: 500;
        }
        .hmb-qr-col {
            width: 72px;
            text-align: right;
            padding-top: 0;
        }

        /* Two-Column Header Info Grid */
        .hmb-header-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0;
            width: 100%;
            gap: 16px;
        }
        .hmb-cust-block {
            width: 48%;
            text-align: left;
            padding-top: 2px;
        }
        .hmb-cust-title {
            font-size: 12px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .hmb-cust-line {
            font-size: 11px;
            color: #111111;
            line-height: 1.35;
            text-transform: uppercase;
        }

        /* Right Bank Metadata Box */
        .hmb-meta-col {
            width: 52%;
        }
        .hmb-meta-group {
            margin-bottom: 5px;
            width: 100%;
        }
        .hmb-caption {
            font-size: 9.5px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            margin-bottom: 2px;
            line-height: 1.1;
            font-family: Arial, Helvetica, sans-serif;
        }
        .hmb-val-box {
            border: 1px solid #000000;
            background: #ffffff;
            padding: 2.5px 5px;
            font-size: 10.5px;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
            box-sizing: border-box;
            width: 100%;
            min-height: 20px;
            line-height: 1.25;
            word-break: break-all;
        }
        .hmb-box-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }
        .hmb-caption-td {
            border: none !important;
            padding: 0 0 2px 2px;
            font-size: 9.5px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            line-height: 1.1;
            font-family: Arial, Helvetica, sans-serif;
        }
        .hmb-val-td {
            border: 1px solid #000000 !important;
            background: #ffffff;
            padding: 2.5px 5px;
            font-size: 10.5px;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
            vertical-align: middle;
            line-height: 1.25;
        }

        /* Main Transaction Table */
        .hmb-tx-table {
            width: 100%;
            border-collapse: collapse;
            
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            margin-bottom: 6px;
        }
        .hmb-tx-table thead th {
            background-color: #555555 !important;
            color: #ffffff !important;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            border: 1px solid #777777;
            border-right: 1px solid #ffffff;
        }
        .hmb-tx-table thead th:last-child {
            border-right: 1px solid #777777;
        }
        .hmb-tx-table tbody td {
            border: 1px solid #999999;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.35;
        }
        
        .hmb-col-date {
            width: 12%;
            text-align: center;
            white-space: nowrap;
        }
        .hmb-col-desc {
            width: 42%;
            text-align: left;
            word-break: break-word;
        }
        .hmb-col-dr {
            width: 15%;
            text-align: right;
            white-space: nowrap;
        }
        .hmb-col-cr {
            width: 15%;
            text-align: right;
            white-space: nowrap;
        }
        .hmb-col-bal {
            width: 16%;
            text-align: right;
            white-space: nowrap;
        }

        .hmb-open-label {
            font-weight: bold;
            text-align: right;
        }
        .hmb-open-amount {
            font-weight: bold;
            text-align: right;
        }
        /* The original HMB statement has no cell borders on Opening Balance. */
        .hmb-tx-table tbody tr.hmb-opening-row td {
            border: none !important;
        }

        /* Bottom Page Number */
        .hmb-footer-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 8px;
            font-size: 10px;
            color: #000000;
            font-weight: normal;
        }
CSS;
    }

    /**
     * Render statement document pages (Page 1 to N)
     */
    protected function renderDocumentPages(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        $opening = CurrencyHelper::normalizeDecimal($statementData['opening_balance'] ?? '0.00');

        $accountTitle = htmlspecialchars(strtoupper($account['account_title'] ?? $account['account_identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars($account['account_number'] ?? $account['account_identifier'] ?? '', ENT_QUOTES, 'UTF-8');
        $iban = htmlspecialchars($account['iban'] ?? '', ENT_QUOTES, 'UTF-8');
        $branchName = !empty($account['branch_name']) ? htmlspecialchars(strtoupper(trim((string)$account['branch_name'])), ENT_QUOTES, 'UTF-8') : '';
        $accountType = !empty($account['account_type']) ? htmlspecialchars(trim((string)$account['account_type']), ENT_QUOTES, 'UTF-8') : (!empty($account['account_status']) ? htmlspecialchars(trim((string)$account['account_status']), ENT_QUOTES, 'UTF-8') : '');
        $currencyRaw = !empty($account['currency']) ? trim((string)$account['currency']) : 'PKR';
        $currency = htmlspecialchars(strtoupper($currencyRaw), ENT_QUOTES, 'UTF-8');

        $startDateFormatted = $this->formatDate($statementData['start_date'] ?? date('Y-m-01'));
        $endDateFormatted = $this->formatDate($statementData['end_date'] ?? date('Y-m-d'));
        $printedOnFormatted = !empty($account['printed_on']) ? $this->formatDate($account['printed_on']) : (!empty($account['printing_date']) ? $this->formatDate($account['printing_date']) : $endDateFormatted);

        // Address Lines
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
            $addressLines = [ ];
        }

        // Precompute which transaction row is the LAST transaction of each date
        // In Habib Metro statements, the Balance is printed on the last transaction row of each distinct date!
        $lastRowOfDate = [];
        $totalTxCount = count($transactions);
        for ($i = 0; $i < $totalTxCount; $i++) {
            $currentRawDate = (string)($transactions[$i]['date'] ?? '');
            $currentTs = strtotime($currentRawDate);
            $currentDate = $currentTs ? date('Y-m-d', $currentTs) : trim($currentRawDate);

            if ($i + 1 < $totalTxCount) {
                $nextRawDate = (string)($transactions[$i + 1]['date'] ?? '');
                $nextTs = strtotime($nextRawDate);
                $nextDate = $nextTs ? date('Y-m-d', $nextTs) : trim($nextRawDate);
            } else {
                $nextDate = null;
            }

            if ($nextDate === null || $nextDate !== $currentDate) {
                $lastRowOfDate[$i] = true;
            }
        }

        // Attach global index to each transaction for balance mapping
        $txsWithIdx = [];
        foreach ($transactions as $idx => $t) {
            $t['_global_idx'] = $idx;
            $txsWithIdx[] = $t;
        }

        // Paginate transactions (33 units per page for standard A4 portrait)
        $paginatedPages = $this->paginateTransactions($txsWithIdx, 33);
        $totalPages = count($paginatedPages);

        // Generate authentic scannable QR code matching client original scan
        $rawIban = !empty($account['iban']) ? trim((string)$account['iban']) : '';
        $rawTitle = !empty($account['account_title']) ? trim((string)$account['account_title']) : (!empty($account['account_identifier']) ? trim((string)$account['account_identifier']) : 'IRFAN ULLAH');
        $qrPayload = "IBAN: " . $rawIban . ",\nAccountTitle: " . $rawTitle;
        $qrSvg = $this->generateQrCodeSvg($qrPayload);

        ob_start();
        ?>
        <?php foreach ($paginatedPages as $pageIdx => $pageTxs): 
            $pageNumber = $pageIdx + 1;
            $isFirstPage = ($pageNumber === 1);
        ?>
<div class="hmb-page">
    <!-- Top Bar: Bank Title & QR Code -->
    <div class="hmb-top-bar">
        <div style="width: 72px;"></div>
        <div class="hmb-title-col">
            <h1 class="hmb-bank-title" aria-label="HABIB METROPOLITAN BANK LTD."><!-- HABIB METROPOLITAN BANK LTD. --><span class="hmb-title-initial">H</span>ABIB <span class="hmb-title-initial">M</span>ETROPOLITAN <span class="hmb-title-initial">B</span>ANK <span class="hmb-title-initial">L</span>TD.</h1>
        </div>
        <div class="hmb-qr-col">
            <?= $qrSvg ?>
        </div>
    </div>

    <!-- Header Grid: Customer Info & Bank Metadata Box -->
    <div class="hmb-header-grid">
        <div class="hmb-cust-block" style="padding-left: 20px;">
           <br>
 <div class="hmb-cust-title" ><?= $accountTitle ?></div><br />
            <?php foreach ($addressLines as $al): ?><div class="hmb-cust-line"><?= $al ?></div><?php endforeach; ?>
        </div>
        <div class="hmb-meta-col">
            <!-- Row 1: BRANCH NAME -->
            <div class="hmb-meta-group">
                <div class="hmb-caption">BRANCH NAME</div>
                <div class="hmb-val-box"><?= $branchName ?></div>
            </div>

            <!-- Row 2: A/C TYPE & A/C NUMBER -->
            <div class="hmb-meta-group">
                <table class="hmb-box-table">
                    <tr>
                        <td style="width: 50%;" class="hmb-caption-td">A/C TYPE</td>
                        <td style="width: 50%;" class="hmb-caption-td">A/C NUMBER</td>
                    </tr>
                    <tr>
                        <td class="hmb-val-td"><?= $accountType ?></td>
                        <td class="hmb-val-td"><?= $accountNumber ?></td>
                    </tr>
                </table>
            </div>

            <!-- Row 3: IBAN -->
            <div class="hmb-meta-group">
                <div class="hmb-caption">IBAN</div>
                <div class="hmb-val-box"><?= $iban ?></div>
            </div>

            <!-- Row 4: CURRENCY, FROM, TO, PRINTED ON -->
            <div class="hmb-meta-group" style="margin-bottom: 0;">
                <table class="hmb-box-table">
                    <tr>
                        <td style="width: 25%;" class="hmb-caption-td">CURRENCY</td>
                        <td style="width: 25%;" class="hmb-caption-td">FROM</td>
                        <td style="width: 25%;" class="hmb-caption-td">TO</td>
                        <td style="width: 25%;" class="hmb-caption-td">PRINTED ON</td>
                    </tr>
                    <tr>
                        <td class="hmb-val-td"><?= $currency ?></td>
                        <td class="hmb-val-td"><?= $startDateFormatted ?></td>
                        <td class="hmb-val-td"><?= $endDateFormatted ?></td>
                        <td class="hmb-val-td"><?= $printedOnFormatted ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <table class="hmb-tx-table">
        <thead>
            <tr>
                <th class="hmb-col-date">Date</th>
                <th class="hmb-col-desc">Particulars</th>
                <th class="hmb-col-dr">Debit</th>
                <th class="hmb-col-cr">Credit</th>
                <th class="hmb-col-bal">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($isFirstPage): 
                $formattedOpening = CurrencyHelper::format($opening);
            ?>
<tr class="hmb-opening-row">
    <td class="hmb-col-date"></td>
    <td class="hmb-col-desc"></td>
    <td class="hmb-col-dr"></td>
    <td class="hmb-col-cr hmb-open-label">Opening Balance :</td>
    <td class="hmb-col-bal hmb-open-amount"><?= $formattedOpening ?></td>
</tr>
            <?php endif; ?>
            <?php foreach ($pageTxs as $tx): 
                $txDateFormatted = $this->formatDate($tx['date'] ?? '');
                $narrationEscaped = nl2br(htmlspecialchars($tx['narration'] ?? '', ENT_QUOTES, 'UTF-8'));
                $debitVal = CurrencyHelper::comp((string)($tx['debit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['debit']) : '';
                $creditVal = CurrencyHelper::comp((string)($tx['credit'] ?? 0), '0.00') > 0 ? CurrencyHelper::format($tx['credit']) : '';
                $gIdx = $tx['_global_idx'] ?? null;
                $showBalance = ($gIdx !== null && !empty($lastRowOfDate[$gIdx]));
                $balVal = $showBalance ? CurrencyHelper::format($tx['balance'] ?? '0.00') : '';
            ?>
<tr>
    <td class="hmb-col-date"><?= $txDateFormatted ?></td>
    <td class="hmb-col-desc"><?= $narrationEscaped ?></td>
    <td class="hmb-col-dr"><?= $debitVal ?></td>
    <td class="hmb-col-cr"><?= $creditVal ?></td>
    <td class="hmb-col-bal"><?= $balVal ?></td>
</tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Footer: Page X of Y -->
    <div class="hmb-footer-bar">
        Page <?= $pageNumber ?> of <?= $totalPages . "\n" ?>
    </div>
</div>
        <?php endforeach; ?>
        <?php
        return trim(ob_get_clean());
    }

    /**
     * Render interactive screen preview HTML
     */
    public function renderHtml(array $statementData): string {
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        $pagesHtml = $this->renderDocumentPages($statementData);
        $sharedCss = $this->getSharedStyles();

        ob_start();
        ?>
<style>
<?= $sharedCss . "\n" ?>
.hmb-preview-shell {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 16px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}
</style>

<div class="hmb-preview-shell space-y-4">
    <!-- Format Badge & Quick Info Bar -->
    <div class="flex items-center justify-between pb-3 border-b border-gray-200">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-300">
                <?= $this->formatName . "\n" ?>
            </span>
            <span class="text-xs text-gray-500 font-mono">Authentic HMB Layout with Vector QR &amp; Metadata Grid</span>
        </div>
        <div class="text-xs text-gray-500 font-mono">
            Zero Signature/Stamp (Ready for Physical Stamping)
        </div>
    </div>

    <!-- Quick Financial Summary Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-gray-50 border border-gray-200 rounded text-xs font-mono">
        <div>
            <div class="text-gray-500 uppercase font-semibold text-[10px]">Opening Balance</div>
            <div class="font-bold text-gray-900 text-xs mt-0.5"><?= $opening ?></div>
        </div>
        <div>
            <div class="text-red-600 uppercase font-semibold text-[10px]">Total Debits</div>
            <div class="font-bold text-red-600 text-xs mt-0.5"><?= $totalDebit ?></div>
        </div>
        <div>
            <div class="text-green-600 uppercase font-semibold text-[10px]">Total Credits</div>
            <div class="font-bold text-green-600 text-xs mt-0.5"><?= $totalCredit ?></div>
        </div>
        <div>
            <div class="text-indigo-900 uppercase font-semibold text-[10px]">Closing Balance</div>
            <div class="font-bold text-indigo-900 text-xs mt-0.5"><?= $closing ?></div>
        </div>
    </div>

    <!-- Document Container -->
    <div class="hmb-statement-wrapper border border-gray-300 p-4 bg-white overflow-x-auto shadow-xs">
        <?= $pagesHtml ?>
    </div>
</div>
        <?php
        return trim(ob_get_clean());
    }

    /**
     * Render standalone printable A4 HTML document for browser print / Save as PDF.
     */
    public function renderPrint(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $accountTitle = htmlspecialchars($account['account_title'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
        $pagesHtml = $this->renderDocumentPages($statementData);
        $sharedCss = $this->getSharedStyles();

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement — <?= $accountTitle ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 8mm 8mm 8mm;
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
            background: #1e293b;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-print:hover {
            background: #0f172a;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
            }
            .hmb-page {
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }
        }
        <?= $sharedCss . "\n" ?>
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <strong><?= $this->formatName ?></strong> &bull; Habib Metropolitan Bank Ltd. (Save as PDF / Print)
        </div>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="hmb-statement-wrapper">
        <?= $pagesHtml ?>
    </div>
</body>
</html>
        <?php
        return trim(ob_get_clean());
    }
}
