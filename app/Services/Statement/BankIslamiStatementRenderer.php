<?php
// app/Services/Statement/BankIslamiStatementRenderer.php
// ================================================================================
// BankIslami Pakistan Limited Statement Layout Renderer
// Replicated strictly from authentic scanned banking statement (bank islami.pdf)
// Reference: media_1792881237 / BankIslami Pakistan Duplicate Statement Of Account
// --------------------------------------------------------------------------------
// Key Characteristics:
// 1. Top Header Banner: Dark charcoal/black full-width bar with white bold serif "BankIslami"
// 2. Sub-Header:
//    - "Duplicate Statement Of Account" (bold left)
//    - "Issuing Branch : [Branch]" (left) | "Issue Date : [d-M-Y]" (right)
// 3. Two-Column Metadata Zone:
//    - Left Box: Bordered rectangle (1px solid black) containing Customer Title (bold)
//      followed by multi-line street address, city, and mobile phone numbers.
//    - Right Grid: Key-value metadata table with right-aligned values:
//      Account Branch, Account Type, Currency, Account No, IBAN, From Date, To Date,
//      and Opening Balance (PKR 0.00).
// 4. Transaction Table:
//    - Header: Top & bottom black borders, vertical divider lines between columns:
//      Date (11%) | Description (49%) | Withdrawal (13%) | Deposit (13%) | Balance (14%)
//    - Rows: Clean layout without vertical lines or row borders.
//    - Date format: d/m/Y (e.g. 16/07/2025, 12/01/2026)
//    - Narration: Multi-line wrapped text
// 5. Final Page Summary Block:
//    - Separated by thin horizontal rule under last row
//    - DEBIT TRANSACTIONS COUNT & TOTAL DEBIT AMOUNT
//    - CREDIT TRANSACTIONS COUNT & TOTAL CREDIT AMOUNT
//    - CLOSING BALANCE AS ON [d-M-Y]: PKR [Amount]
//    - AVAILABLE BALANCE AS ON [d-M-Y]: PKR [Amount]
// 6. Footer (Every Page):
//    - Left: "Printed By : [UserCode] on [n/j/Y g:i A]"
//    - Right: "Page X of Y"
// 7. Strict omission of digital stamp / signature (reserved for physical stamping)
// ================================================================================

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

class BankIslamiStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'bankislami';
    protected string $formatName = 'BankIslami Statement Layout';

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    /**
     * Format date as d-M-Y (e.g. 29-Sep-2026, 01-Jan-2025)
     */
    protected function formatDateDmy(?string $dateStr): string {
        if (!$dateStr) return '';
        $clean = str_replace('/', '-', trim($dateStr));
        $ts = strtotime($clean);
        if (!$ts) return $dateStr;
        return date('d-M-Y', $ts);
    }

    /**
     * Format date for transaction rows as d/m/Y (e.g. 16/07/2025, 12/01/2026)
     */
    protected function formatTableDate(?string $dateStr): string {
        if (!$dateStr) return '';
        $clean = str_replace('/', '-', trim($dateStr));
        $ts = strtotime($clean);
        if (!$ts) return $dateStr;
        return date('d/m/Y', $ts);
    }

    /**
     * Format date and time for footer as n/j/Y g:i A (e.g. 9/29/2026 5:06 PM)
     */
    protected function formatFooterDateTime(?string $dateTimeStr): string {
        if (!$dateTimeStr) return date('n/j/Y g:i A');
        $ts = strtotime(str_replace('/', '-', trim($dateTimeStr)));
        if (!$ts) return $dateTimeStr;
        return date('n/j/Y g:i A', $ts);
    }

    /**
     * Wrap narration text into visual lines of approx ~52 characters
     */
    protected function wrapNarration(string $narration, int $maxLen = 52): array {
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
     * Compute estimated vertical height (in mm) of a transaction row
     */
    protected function getTransactionHeight(array $tx): float {
        $narration = trim((string)($tx['narration'] ?? ''));
        $lines = $this->wrapNarration($narration);
        $lineCount = max(1, count($lines));
        // Base single line row with padding takes 5.5mm; each extra wrapped line takes 3.8mm
        return 5.5 + ($lineCount - 1) * 3.8;
    }

    /**
     * Paginate transactions dynamically based on estimated physical height in mm
     */
    protected function chunkTransactions(array $transactions): array {
        if (empty($transactions)) {
            return [[]];
        }

        $pages = [];
        $currentPage = [];
        $currentHeight = 0.0;
        $pageIndex = 0;

        $totalTx = count($transactions);

        for ($i = 0; $i < $totalTx; $i++) {
            $tx = $transactions[$i];
            $h = $this->getTransactionHeight($tx);

            // Page 1 has top header + metadata box (~95mm); available height is ~168mm
            // Other pages start directly with table thead; available height is ~245mm
            $maxHeight = ($pageIndex === 0) ? 168.0 : 245.0;

            // Reserve ~42mm for the final summary block if this could be the final page
            $remainingTxs = $totalTx - $i;
            if ($remainingTxs <= 25) {
                $maxHeight -= 42.0;
            }

            if (($currentHeight + $h > $maxHeight) && !empty($currentPage)) {
                $pages[] = $currentPage;
                $currentPage = [];
                $currentHeight = 0.0;
                $pageIndex++;
            }

            $currentPage[] = $tx;
            $currentHeight += $h;
        }

        if (!empty($currentPage)) {
            $pages[] = $currentPage;
        }

        return $pages;
    }

    /**
     * Shared CSS Styles for Screen Preview and A4 Printing
     */
    protected function getSharedStyles(): string {
        return <<<CSS
        .bi-statement-wrapper {
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

        .bi-page {
            width: 100%;
            min-height: 297mm;
            box-sizing: border-box;
            padding: 8mm 10mm 14mm 10mm;
            margin-bottom: 25px;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
            position: relative;
        }

        .bi-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }

        /* Top Header Banner */
        .bi-banner {
            width: 100%;
            height: 60px;
            min-height: 60px;
            background: #2b2b2b;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 18px;
            box-sizing: border-box;
            margin-bottom: 8px;
        }

        .bi-banner-logo {
            color: #ffffff !important;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: -0.4px;
            line-height: 1;
        }

        .bi-banner-img {
            max-height: 46px;
            width: auto;
            object-fit: contain;
            display: block;
            filter: brightness(0) invert(1);
        }

        /* Sub-Header Area */
        .bi-doc-title {
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            margin: 0 0 4px 0;
            line-height: 1.2;
            text-transform: capitalize;
        }

        .bi-sub-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 11.5px;
            color: #000000;
            margin-bottom: 4px;
        }

        .bi-divider {
            width: 100%;
            border: none;
            border-bottom: 1px solid #000000;
            margin: 4px 0 8px 0;
        }

        /* Two-Column Metadata Zone */
        .bi-meta-zone {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            width: 100%;
            margin-bottom: 10px;
            gap: 16px;
        }

        /* Left Customer Box */
        .bi-customer-box {
            width: 48%;
            border: 1px solid #000000;
            padding: 8px 10px;
            box-sizing: border-box;
            font-size: 11px;
            line-height: 1.35;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .bi-cust-title {
            font-size: 11.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: #000000;
        }

        .bi-cust-line {
            font-size: 11px;
            color: #000000;
            text-transform: uppercase;
            line-height: 1.35;
        }

        /* Right Key-Value Metadata Table */
        .bi-meta-table {
            width: 50%;
            border-collapse: collapse;
            font-size: 11.5px;
            color: #000000;
        }

        .bi-meta-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        .bi-lbl {
            width: 38%;
            white-space: nowrap;
            color: #000000;
        }

        .bi-colon {
            width: 4%;
            text-align: center;
            color: #000000;
        }

        .bi-val {
            width: 58%;
            text-align: right;
            white-space: nowrap;
            color: #000000;
        }

        .bi-row-spacer td {
            height: 6px;
            padding: 0;
        }

        /* Transaction Table */
        .bi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            table-layout: fixed;
            margin: 0;
        }

        .bi-table thead tr {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }

        .bi-table th {
            font-weight: bold;
            font-size: 11px;
            color: #000000;
            padding: 4px 6px;
            text-align: center;
            background: transparent;
            box-sizing: border-box;
        }

        .bi-table th.th-date {
            width: 11%;
            text-align: center;
            border-right: 1px solid #000000;
        }

        .bi-table th.th-desc {
            width: 49%;
            text-align: center;
            border-right: 1px solid #000000;
            padding-left: 6px;
        }

        .bi-table th.th-with {
            width: 13%;
            text-align: center;
            border-right: 1px solid #000000;
        }

        .bi-table th.th-dep {
            width: 13%;
            text-align: center;
            border-right: 1px solid #000000;
        }

        .bi-table th.th-bal {
            width: 14%;
            text-align: center;
        }

        .bi-table td {
            padding: 2.5px 6px;
            vertical-align: top;
            color: #000000;
            border: none;
            box-sizing: border-box;
        }

        .bi-table td.col-date {
            width: 11%;
            white-space: nowrap;
        }

        .bi-table td.col-desc {
            width: 49%;
            word-wrap: break-word;
            word-break: break-word;
            padding-left: 8px;
            line-height: 1.3;
        }

        .bi-table td.col-num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .bi-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Summary Section (Last Page) */
        .bi-summary-wrap {
            margin-top: 6px;
            width: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .bi-summary-line {
            width: 100%;
            border: none;
            border-bottom: 1px solid #000000;
            margin: 0 0 8px 0;
        }

        .bi-summary-grid {
            width: 100%;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            line-height: 1.4;
            color: #000000;
            margin-bottom: 8px;
        }

        .bi-summary-counts {
            width: 45%;
        }

        .bi-summary-totals {
            width: 50%;
            text-align: right;
        }

        .bi-count-row, .bi-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .bi-bal-summary {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 11px;
            line-height: 1.45;
            color: #000000;
        }

        .bi-bal-row {
            display: flex;
            justify-content: flex-end;
            width: 75%;
            margin-bottom: 2px;
        }

        .bi-bal-label {
            margin-right: 18px;
            text-align: right;
        }

        .bi-bal-val {
            min-width: 140px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* Footer (Every Page) */
        .bi-footer {
            position: absolute;
            left: 10mm;
            right: 10mm;
            bottom: 6mm;
            height: 8mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 10px;
            color: #000000;
            border-top: none;
        }

        @media print {
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .bi-statement-wrapper {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .bi-page {
                margin: 0 !important;
                padding: 8mm 10mm 14mm 10mm !important;
                min-height: 297mm !important;
                height: 297mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .bi-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .no-print {
                display: none !important;
            }
        }
CSS;
    }

    /**
     * Render statement HTML
     */
    public function renderHtml(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $period = $statementData['period'] ?? [];
        $transactions = $statementData['transactions'] ?? [];

        $accountTitle = htmlspecialchars(trim((string)($account['account_title'] ?? '')), ENT_QUOTES, 'UTF-8');
        $accountNumber = htmlspecialchars(trim((string)($account['account_number'] ?? '')), ENT_QUOTES, 'UTF-8');
        $branchName = htmlspecialchars(trim((string)($account['branch_name'] ?? 'KUCHLAK')), ENT_QUOTES, 'UTF-8');
        $issuingBranch = htmlspecialchars(trim((string)($account['issuing_branch'] ?? $branchName)), ENT_QUOTES, 'UTF-8');
        $accountType = htmlspecialchars(trim((string)($account['account_type'] ?? 'Current Account')), ENT_QUOTES, 'UTF-8');
        $currency = htmlspecialchars(trim((string)($account['currency'] ?? 'Pakistani Rupee')), ENT_QUOTES, 'UTF-8');
        $iban = htmlspecialchars(trim((string)($account['iban'] ?? '')), ENT_QUOTES, 'UTF-8');
        
        $startDateRaw = $statementData['start_date'] ?? ($period['start_date'] ?? ($account['start_date'] ?? ''));
        $endDateRaw = $statementData['end_date'] ?? ($period['end_date'] ?? ($account['end_date'] ?? ''));

        // Fallbacks if dates are not explicitly passed
        if (empty($endDateRaw) && !empty($transactions)) {
            $lastTx = end($transactions);
            $endDateRaw = $lastTx['date'] ?? ($lastTx['transaction_date'] ?? date('Y-m-d'));
        }
        if (empty($endDateRaw)) {
            $endDateRaw = date('Y-m-d');
        }
        if (empty($startDateRaw) && !empty($transactions)) {
            $firstTx = reset($transactions);
            $startDateRaw = $firstTx['date'] ?? ($firstTx['transaction_date'] ?? date('Y-01-01'));
        }
        if (empty($startDateRaw)) {
            $startDateRaw = date('Y-01-01');
        }

        $fromDate = $this->formatDateDmy($startDateRaw);
        $toDate = $this->formatDateDmy($endDateRaw);

        $issueDateRaw = $account['issue_date'] ?? $endDateRaw;
        $issueDate = $this->formatDateDmy($issueDateRaw);

        $statementTitle = !empty($account['statement_title']) 
            ? htmlspecialchars(trim((string)$account['statement_title']), ENT_QUOTES, 'UTF-8') 
            : 'Duplicate Statement Of Account';

        $openingBal = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closingBal = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        // Address lines extraction
        $rawAddr = trim((string)($account['address'] ?? ''));
        $addrLines = [];
        if ($rawAddr !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $rawAddr) as $l) {
                $l = trim($l);
                if ($l !== '') $addrLines[] = htmlspecialchars($l, ENT_QUOTES, 'UTF-8');
            }
        }
        if (!empty($account['city']) && !in_array(strtoupper($account['city']), array_map('strtoupper', $addrLines))) {
            $addrLines[] = htmlspecialchars(trim((string)$account['city']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($account['phone1'])) {
            $addrLines[] = htmlspecialchars(trim((string)$account['phone1']), ENT_QUOTES, 'UTF-8');
        }
        if (!empty($account['phone2'])) {
            $addrLines[] = htmlspecialchars(trim((string)$account['phone2']), ENT_QUOTES, 'UTF-8');
        }

        // Count debit & credit transactions
        $debitCount = 0;
        $creditCount = 0;
        foreach ($transactions as $tx) {
            $d = CurrencyHelper::normalizeDecimal($tx['debit'] ?? '0.00');
            $c = CurrencyHelper::normalizeDecimal($tx['credit'] ?? '0.00');
            if (CurrencyHelper::comp($d, '0.00') > 0) $debitCount++;
            if (CurrencyHelper::comp($c, '0.00') > 0) $creditCount++;
        }

        // User code & printed on
        $userCode = htmlspecialchars(trim((string)($account['user_code'] ?? ($account['generated_by'] ?? 'UM12926'))), ENT_QUOTES, 'UTF-8');
        $printedOn = $this->formatFooterDateTime($account['printed_on'] ?? ($account['print_date'] ?? null));

        // Bank Logo
        $bankLogo = !empty($account['bank_logo']) ? trim((string)$account['bank_logo']) : null;

        // Chunking
        $pages = $this->chunkTransactions($transactions);
        $totalPages = count($pages);

        ob_start();
        ?>
        <style>
            <?= $this->getSharedStyles() ?>
        </style>

        <div class="bi-statement-wrapper">
            <?php foreach ($pages as $pIdx => $pageTx): ?>
                <?php
                    $pageNum = $pIdx + 1;
                    $isFirstPage = ($pageNum === 1);
                    $isLastPage = ($pageNum === $totalPages);
                ?>

                <div class="bi-page">
                    <!-- Page 1 Only: Top Header Banner & Metadata Zone -->
                    <?php if ($isFirstPage): ?>
                        <div class="bi-banner">
                            <?php 
                                $logoPath = 'uploads/banks/bank_islami_logo.png';
                                if ($bankLogo && (strpos($bankLogo, 'bank_islami') !== false || file_exists(dirname(__DIR__, 3) . '/public/' . ltrim($bankLogo, '/')))) {
                                    $logoPath = $bankLogo;
                                }
                            ?>
                            <img src="/atm/public/<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="BankIslami" class="bi-banner-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                            <span class="bi-banner-logo" style="display: none;">BankIslami</span>
                        </div>

                        <div class="bi-doc-title"><?= $statementTitle ?></div>

                        <div class="bi-sub-row">
                            <div>Issuing Branch : <?= $issuingBranch ?></div>
                            <div>Issue Date : <?= $issueDate ?></div>
                        </div>

                        <div class="bi-divider"></div>

                        <div class="bi-meta-zone">
                            <!-- Left: Customer Information Box -->
                            <div class="bi-customer-box">
                                <div class="bi-cust-title"><?= $accountTitle ?></div>
                                <?php foreach ($addrLines as $aLine): ?>
                                    <div class="bi-cust-line"><?= $aLine ?></div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Right: Account Details Table -->
                            <table class="bi-meta-table">
                                <tr>
                                    <td class="bi-lbl">Account Branch</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $branchName ?></td>
                                </tr>
                                <tr>
                                    <td class="bi-lbl">Account Type</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $accountType ?></td>
                                </tr>
                                <tr>
                                    <td class="bi-lbl">Currency</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $currency ?></td>
                                </tr>
                                <tr>
                                    <td class="bi-lbl">Account No</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $accountNumber ?></td>
                                </tr>
                                <tr>
                                    <td class="bi-lbl">IBAN</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $iban ?></td>
                                </tr>
                                <tr class="bi-row-spacer"><td colspan="3"></td></tr>
                                <tr>
                                    <td class="bi-lbl">From Date</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $fromDate ?></td>
                                </tr>
                                <tr>
                                    <td class="bi-lbl">To Date</td>
                                    <td class="bi-colon">:</td>
                                    <td class="bi-val"><?= $toDate ?></td>
                                </tr>
                                <tr class="bi-row-spacer"><td colspan="3"></td></tr>
                                <tr>
                                    <td class="bi-lbl" style="font-weight: bold;">Opening Balance</td>
                                    <td class="bi-colon" style="font-weight: bold;">:</td>
                                    <td class="bi-val" style="font-weight: bold;">PKR <?= $openingBal ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Transaction Table -->
                    <table class="bi-table">
                        <thead>
                            <tr>
                                <th class="th-date">Date</th>
                                <th class="th-desc">Description</th>
                                <th class="th-with">Withdrawal</th>
                                <th class="th-dep">Deposit</th>
                                <th class="th-bal">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pageTx) && $isFirstPage): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 25px; color: #666; font-style: italic;">
                                        No transactions recorded for this statement period.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pageTx as $tx): ?>
                                    <?php
                                        $tDate = $this->formatTableDate($tx['date'] ?? '');
                                        $narration = htmlspecialchars(trim((string)($tx['narration'] ?? '')), ENT_QUOTES, 'UTF-8');
                                        // Allow visual line breaks for multi-line narrations
                                        $narrationFormatted = nl2br($narration);

                                        $debitVal = CurrencyHelper::normalizeDecimal($tx['debit'] ?? '0.00');
                                        $creditVal = CurrencyHelper::normalizeDecimal($tx['credit'] ?? '0.00');
                                        $balVal = CurrencyHelper::format($tx['balance'] ?? '0.00');

                                        $debitStr = (CurrencyHelper::comp($debitVal, '0.00') > 0) ? CurrencyHelper::format($debitVal) : '';
                                        $creditStr = (CurrencyHelper::comp($creditVal, '0.00') > 0) ? CurrencyHelper::format($creditVal) : '';
                                    ?>
                                    <tr>
                                        <td class="col-date"><?= $tDate ?></td>
                                        <td class="col-desc"><?= $narrationFormatted ?></td>
                                        <td class="col-num"><?= $debitStr ?></td>
                                        <td class="col-num"><?= $creditStr ?></td>
                                        <td class="col-num"><?= $balVal ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Last Page Summary Block -->
                    <?php if ($isLastPage): ?>
                        <div class="bi-summary-wrap">
                            <div class="bi-summary-line"></div>

                            <div class="bi-summary-grid">
                                <div class="bi-summary-counts">
                                    <div class="bi-count-row">
                                        <span>DEBIT TRANSACTIONS COUNT :</span>
                                        <span style="font-weight: bold; min-width: 40px; text-align: right;"><?= $debitCount ?></span>
                                    </div>
                                    <div class="bi-count-row">
                                        <span>CREDIT TRANSACTIONS COUNT:</span>
                                        <span style="font-weight: bold; min-width: 40px; text-align: right;"><?= $creditCount ?></span>
                                    </div>
                                </div>

                                <div class="bi-summary-totals">
                                    <div class="bi-total-row">
                                        <span>TOTAL DEBIT AMOUNT &nbsp;:</span>
                                        <span style="font-weight: bold; min-width: 120px; text-align: right;"><?= $totalDebit ?></span>
                                    </div>
                                    <div class="bi-total-row">
                                        <span>TOTAL CREDIT AMOUNT :</span>
                                        <span style="font-weight: bold; min-width: 120px; text-align: right;"><?= $totalCredit ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="bi-bal-summary">
                                <div class="bi-bal-row">
                                    <span class="bi-bal-label">CLOSING BALANCE &nbsp;&nbsp;&nbsp;AS ON <?= $toDate ?>:</span>
                                    <span class="bi-bal-val">PKR <?= $closingBal ?></span>
                                </div>
                                <div class="bi-bal-row">
                                    <span class="bi-bal-label">AVAILABLE BALANCE AS ON <?= $toDate ?>:</span>
                                    <span class="bi-bal-val">PKR <?= $closingBal ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Footer (Every Page) -->
                    <div class="bi-footer">
                        <div>Printed By : <?= $userCode ?> on <?= $printedOn ?></div>
                        <div>Page <?= $pageNum ?> of <?= $totalPages ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render full standalone printable document
     */
    public function renderPrint(array $statementData): string {
        $htmlBody = $this->renderHtml($statementData);
        $account = $statementData['account'] ?? [];
        $title = 'BankIslami Statement — ' . htmlspecialchars($account['account_title'] ?? 'Customer', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            background: #eef2f6;
            font-family: Arial, Helvetica, sans-serif;
        }
        .print-toolbar {
            background: #1e293b;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .print-btn, .btn-print {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 7px 18px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .print-btn:hover, .btn-print:hover {
            background: #0369a1;
        }
        @media print {
            .print-toolbar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <span style="font-weight: bold; font-size: 13px;">BankIslami Pakistan Limited — Statement Print Preview</span>
        <button class="print-btn btn-print" onclick="window.print()">🖨 Print Statement</button>
    </div>
    {$htmlBody}
</body>
</html>
HTML;
    }
}
