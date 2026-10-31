<?php
// app/Services/Statement/UBLStatementRenderer.php
// Authentic UBL (United Bank Limited) / UBL Ameen Islamic Banking Statement Layout
// ================================================================================
// Features:
// 1. Full-width UBL Ameen Islamic Banking Header Banner across top of all pages
// 2. Page 1 Middle Zone:
//    - Left: Customer title, street address, city, Reg Cell No, IBAN No, CIF#
//    - Right Box: 1px border key-value metadata table:
//      * Statement Period: From [d-M-Y] To [d-M-Y]
//      * Account No : [AccountNo]
//      * Account Type : [AccountType]
//      * Product Type : [ProductType]
//      * Currency : [Currency]
//      * Balance: [ClosingBalance] Cr
//      * As of : [ToDate]
//    - Right Sub-Block: "Account Statement" + "[BranchCode]-[AccountNo]"
// 3. Continuation Pages:
//    - Banner at top, left customer name, right "Account Statement" + "[BranchCode]-[AccountNo]"
// 4. Transaction Table:
//    - Thead: light gray (#e9e9e9), centered captions with full 1px black cell borders
//    - Columns: Date (11%), Particulars (39%), Inst No. (11%), Debit (12%), Credit (12%), Balance (15%)
//    - Page 1 starts with "** OPENING BALANCE **" row
//    - Column vertical dividers between every column in data rows
//    - Running balance ends with "Cr" (or "Dr") suffix
// 5. Footer:
//    - Right: "Page # X / Y"
//    - Note: 2-week discrepancy notice
//    - Head Office address on left, UAN & website on right
// 6. Strict omission of digital stamp/signature (client manual physical stamping)
// ================================================================================

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

class UBLStatementRenderer implements StatementRendererInterface {
    protected string $formatCode = 'ubl';
    protected string $formatName = 'UBL Ameen Statement Layout';

    public function getSupportedFormatCode(): string {
        return $this->formatCode;
    }

    public function getFormatName(): string {
        return $this->formatName;
    }

    /**
     * Format date as d-M-Y in uppercase (e.g. 01-MAR-2026, 26-JUN-2026)
     */
    protected function formatDateDmy(?string $dateStr): string {
        if (!$dateStr) return '';
        $clean = str_replace('/', '-', trim($dateStr));
        $ts = strtotime($clean);
        if (!$ts) return strtoupper($dateStr);
        return strtoupper(date('d-M-Y', $ts));
    }

    /**
     * Format running balance with Cr / Dr suffix
     */
    protected function formatBalanceCr(string $balance): string {
        $clean = str_replace(',', '', trim($balance));
        if ($clean === '' || !is_numeric($clean)) return '0.00 Cr';
        $num = (float)$clean;
        $formatted = CurrencyHelper::format(abs($num));
        return $num < 0 ? $formatted . ' Dr' : $formatted . ' Cr';
    }

    /**
     * Wrap narration text into visual lines of approx ~46 characters
     */
    protected function wrapNarration(string $narration, int $maxLen = 46): array {
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
        return empty($lines) ? [''] : $lines;
    }

    /**
     * Compute estimated vertical height (in mm) of a transaction row
     */
    protected function getTransactionHeight(array $tx): float {
        $narration = trim((string)($tx['narration'] ?? ''));
        $lines = $this->wrapNarration($narration);
        $lineCount = max(1, count($lines));
        // Base single line row takes 5.2mm; each wrapped line adds 3.6mm
        return 5.2 + ($lineCount - 1) * 3.6;
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

            // Page 1 has top banner + metadata box + title (~115mm); available height is ~150mm
            // Continuation pages have banner + subrow (~38mm); available height is ~225mm
            $maxHeight = ($pageIndex === 0) ? 150.0 : 225.0;

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
        .ubl-statement-wrapper {
            font-family: Arial, Helvetica, sans-serif !important;
            color: #000000;
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            box-sizing: border-box;
            font-size: 10px;
            line-height: 1.25;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .ubl-page {
            width: 100%;
            min-height: 297mm;
            box-sizing: border-box;
            padding: 6mm 8mm 8mm 8mm;
            margin-bottom: 25px;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .ubl-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }

        .ubl-page-content {
            flex: 1;
        }

        /* Top Header Banner */
        .ubl-header-banner {
            width: 100%;
            margin-bottom: 6px;
            text-align: center;
        }

        .ubl-banner-img {
            width: 100%;
            height: auto;
            max-height: 58px;
            object-fit: fill;
            display: block;
        }

        /* Continuation Header Sub-Row */
        .ubl-cont-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 6px;
            padding: 0 2px;
        }

        .ubl-cont-cust-name {
            font-size: 11.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
        }

        .ubl-cont-right {
            text-align: right;
        }

        .ubl-cont-title {
            font-size: 14px;
            font-weight: bold;
            color: #000000;
            line-height: 1.1;
        }

        .ubl-cont-acc {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
        }

        /* Page 1 Middle Metadata Zone */
        .ubl-meta-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
            gap: 15px;
        }

        /* Left: Customer Info */
        .ubl-cust-info {
            width: 52%;
            font-size: 10px;
            line-height: 1.3;
        }

        .ubl-cust-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            margin-bottom: 2px;
        }

        .ubl-cust-addr {
            font-size: 10px;
            text-transform: uppercase;
            color: #000000;
        }

        .ubl-cust-meta-row {
            margin-top: 2px;
            font-size: 10px;
            color: #000000;
        }

        .ubl-meta-lbl {
            font-weight: bold;
            margin-right: 4px;
        }

        .ubl-meta-val {
            font-weight: normal;
        }

        /* Right: Metadata Box + Title */
        .ubl-right-wrapper {
            width: 48%;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .ubl-details-box {
            width: 100%;
            border: 1px solid #000000;
            border-collapse: collapse;
            font-size: 9.5px;
            line-height: 1.25;
            box-sizing: border-box;
        }

        .ubl-details-box td {
            padding: 1.5px 5px;
            vertical-align: top;
            color: #000000;
        }

        .ubl-details-box td.ubl-lbl {
            font-weight: bold;
            width: 38%;
            white-space: nowrap;
        }

        .ubl-details-box td.ubl-val {
            width: 62%;
            text-align: left;
            padding-left: 8px;
        }

        .ubl-doc-title-block {
            margin-top: 4px;
            text-align: right;
            width: 100%;
        }

        .ubl-title-text {
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            line-height: 1.1;
        }

        .ubl-title-acc {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
        }

        /* Transaction Table */
        .ubl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            table-layout: fixed;
            margin: 0;
            border-bottom: 1px solid #000000;
        }

        .ubl-table thead tr {
            background-color: #e9e9e9;
        }

        .ubl-table th {
            font-weight: bold;
            font-size: 10px;
            color: #000000;
            padding: 3px 4px;
            text-align: center;
            border: 1px solid #000000;
            box-sizing: border-box;
        }

        .ubl-table th.th-date { width: 11%; }
        .ubl-table th.th-part { width: 39%; }
        .ubl-table th.th-inst { width: 11%; }
        .ubl-table th.th-dr   { width: 12%; }
        .ubl-table th.th-cr   { width: 12%; }
        .ubl-table th.th-bal  { width: 15%; }

        .ubl-table td {
            padding: 2.2px 4px;
            vertical-align: top;
            color: #000000;
            border-right: 1px solid #000000;
            box-sizing: border-box;
            line-height: 1.25;
        }

        .ubl-table td:first-child {
            border-left: 1px solid #000000;
        }

        .ubl-table td.col-date {
            text-align: left;
            white-space: nowrap;
        }

        .ubl-table td.col-part {
            text-align: left;
            word-wrap: break-word;
            word-break: break-word;
            text-transform: uppercase;
        }

        .ubl-table td.col-inst {
            text-align: center;
            white-space: nowrap;
        }

        .ubl-table td.col-num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .ubl-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Footer Section */
        .ubl-page-footer {
            margin-top: 4px;
            width: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .ubl-page-num-row {
            text-align: right;
            font-size: 9.5px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 4px;
            padding-right: 2px;
        }

        .ubl-note-text {
            font-size: 8.5px;
            font-weight: bold;
            line-height: 1.2;
            color: #000000;
            margin-bottom: 6px;
        }

        .ubl-office-bar {
            border-top: 1px solid #000000;
            padding-top: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8.5px;
            color: #000000;
        }

        .ubl-office-left {
            width: 72%;
            font-weight: bold;
        }

        .ubl-office-right {
            width: 26%;
            border-left: 1px solid #000000;
            padding-left: 8px;
            text-align: left;
            line-height: 1.25;
        }

        .ubl-office-right span {
            font-weight: bold;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .ubl-statement-wrapper {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .ubl-page {
                margin: 0 !important;
                padding: 5mm 7mm 7mm 7mm !important;
                min-height: 297mm !important;
                height: 297mm !important;
                page-break-after: always !important;
                break-after: page !important;
                box-shadow: none !important;
            }
            .ubl-page:last-child {
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
        
        $branchName = htmlspecialchars(trim((string)($account['branch_name'] ?? '0053-UBL AMEEN CITY MAIN BR, PWR')), ENT_QUOTES, 'UTF-8');
        $branchCode = htmlspecialchars(trim((string)($account['branch_code'] ?? '0053')), ENT_QUOTES, 'UTF-8');
        if (empty($branchCode) && preg_match('/^(\d+)/', $branchName, $m)) {
            $branchCode = $m[1];
        }

        $accountType = htmlspecialchars(trim((string)($account['account_type'] ?? 'CURRENT')), ENT_QUOTES, 'UTF-8');
        $productType = htmlspecialchars(trim((string)($account['product'] ?? ($account['product_type'] ?? 'AMEEN MUKAMMAL CURRENT ACCOUNT'))), ENT_QUOTES, 'UTF-8');
        $currency = htmlspecialchars(trim((string)($account['currency'] ?? 'PAKISTANI RUPEE')), ENT_QUOTES, 'UTF-8');
        $iban = htmlspecialchars(trim((string)($account['iban'] ?? 'PK81 UNIL 0109 0003 4432 7894')), ENT_QUOTES, 'UTF-8');
        $regCellNo = htmlspecialchars(trim((string)($account['reg_cell_no'] ?? ($account['phone1'] ?? '009203089470136'))), ENT_QUOTES, 'UTF-8');
        $cifNo = htmlspecialchars(trim((string)($account['cif_no'] ?? '47187224')), ENT_QUOTES, 'UTF-8');

        $startDateRaw = $statementData['start_date'] ?? ($period['start_date'] ?? ($account['start_date'] ?? ''));
        $endDateRaw = $statementData['end_date'] ?? ($period['end_date'] ?? ($account['end_date'] ?? ''));

        if (empty($endDateRaw) && !empty($transactions)) {
            $lastTx = end($transactions);
            $endDateRaw = $lastTx['date'] ?? ($lastTx['transaction_date'] ?? date('Y-m-d'));
        }
        if (empty($endDateRaw)) $endDateRaw = date('Y-m-d');

        if (empty($startDateRaw) && !empty($transactions)) {
            $firstTx = reset($transactions);
            $startDateRaw = $firstTx['date'] ?? ($firstTx['transaction_date'] ?? date('Y-01-01'));
        }
        if (empty($startDateRaw)) $startDateRaw = date('Y-01-01');

        $fromDate = $this->formatDateDmy($startDateRaw);
        $toDate = $this->formatDateDmy($endDateRaw);

        $openingBal = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closingBal = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');

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

        // Chunking
        $pages = $this->chunkTransactions($transactions);
        $totalPages = count($pages);

        $bannerSrc = '/atm/public/uploads/banks/ubl_ameen_banner.png';
        if (!empty($account['bank_logo']) && file_exists(dirname(__DIR__, 3) . '/public/' . ltrim($account['bank_logo'], '/'))) {
            $bannerSrc = '/atm/public/' . ltrim($account['bank_logo'], '/');
        }

        $css = $this->getSharedStyles();

        ob_start();
        ?>
        <style><?= $css ?></style>
        <div class="ubl-statement-wrapper">
            <?php foreach ($pages as $pIdx => $pageTx): ?>
                <?php
                    $pageNum = $pIdx + 1;
                    $isFirstPage = ($pageNum === 1);
                ?>

                <div class="ubl-page">
                    <div class="ubl-page-content">
                        <!-- Top Header Banner (All Pages) -->
                        <div class="ubl-header-banner">
                            <img src="<?= $bannerSrc ?>" alt="UBL Ameen Islamic Banking" class="ubl-banner-img">
                        </div>

                        <?php if ($isFirstPage): ?>
                            <!-- Page 1 Only: Middle Metadata Zone -->
                            <div class="ubl-meta-container">
                                <!-- Left: Customer Info -->
                                <div class="ubl-cust-info">
                                    <div class="ubl-cust-name"><?= $accountTitle ?></div>
                                    <?php foreach ($addrLines as $aLine): ?>
                                        <div class="ubl-cust-addr"><?= $aLine ?></div>
                                    <?php endforeach; ?>

                                    <div class="ubl-cust-meta-row" style="margin-top: 12px;">
                                        <span class="ubl-meta-lbl">Reg Cell No:</span>
                                        <span class="ubl-meta-val"><?= $regCellNo ?></span>
                                    </div>
                                    <div class="ubl-cust-meta-row">
                                        <span class="ubl-meta-lbl">IBAN No:</span>
                                        <span class="ubl-meta-val" style="margin-right: 20px;"><?= $iban ?></span>
                                        <span class="ubl-meta-lbl">CIF#:</span>
                                        <span class="ubl-meta-val"><?= $cifNo ?></span>
                                    </div>
                                </div>

                                <!-- Right: Details Box + Title -->
                                <div class="ubl-right-wrapper">
                                    <table class="ubl-details-box">
                                        <tr>
                                            <td class="ubl-lbl">Statement Period</td>
                                            <td class="ubl-val">From <?= $fromDate ?> To <?= $toDate ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ubl-lbl">Account No :</td>
                                            <td class="ubl-val"><?= $accountNumber ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ubl-lbl">Account Type :</td>
                                            <td class="ubl-val"><?= $accountType ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ubl-lbl">Product Type :</td>
                                            <td class="ubl-val"><?= $productType ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ubl-lbl">Currency :</td>
                                            <td class="ubl-val"><?= $currency ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ubl-lbl">Balance:</td>
                                            <td class="ubl-val"><?= $closingBal ?> Cr</td>
                                        </tr>
                                        <tr>
                                            <td class="ubl-lbl">As of :</td>
                                            <td class="ubl-val"><?= $toDate ?></td>
                                        </tr>
                                    </table>

                                    <div class="ubl-doc-title-block">
                                        <div class="ubl-title-text">Account Statement</div>
                                        <div class="ubl-title-acc"><?= $branchCode ?>-<?= $accountNumber ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Continuation Pages: Compact Sub-Row -->
                            <div class="ubl-cont-header">
                                <div class="ubl-cont-cust-name"><?= $accountTitle ?></div>
                                <div class="ubl-cont-right">
                                    <div class="ubl-cont-title">Account Statement</div>
                                    <div class="ubl-cont-acc"><?= $branchCode ?>-<?= $accountNumber ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Transaction Table -->
                        <table class="ubl-table">
                            <thead>
                                <tr>
                                    <th class="th-date">Date</th>
                                    <th class="th-part">Particulars</th>
                                    <th class="th-inst">Inst No.</th>
                                    <th class="th-dr">Debit</th>
                                    <th class="th-cr">Credit</th>
                                    <th class="th-bal">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($isFirstPage): ?>
                                    <!-- Page 1 First Row: Opening Balance -->
                                    <tr>
                                        <td class="col-date"><?= $fromDate ?></td>
                                        <td class="col-part">** OPENING BALANCE **</td>
                                        <td class="col-inst"></td>
                                        <td class="col-num"></td>
                                        <td class="col-num"></td>
                                        <td class="col-num"><?= $openingBal ?> Cr</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($pageTx as $tx): ?>
                                    <?php
                                        $txDate = $this->formatDateDmy($tx['date'] ?? ($tx['transaction_date'] ?? ''));
                                        $narration = htmlspecialchars(trim((string)($tx['narration'] ?? '')), ENT_QUOTES, 'UTF-8');
                                        
                                        $instNo = '';
                                        if (!empty($tx['reference_no']) && $tx['reference_no'] !== '-') {
                                            $instNo = htmlspecialchars(trim((string)$tx['reference_no']), ENT_QUOTES, 'UTF-8');
                                        }

                                        $debitNum = (float)str_replace(',', '', (string)($tx['debit'] ?? '0'));
                                        $debitFormatted = $debitNum > 0 ? CurrencyHelper::format($debitNum) : '';

                                        $creditNum = (float)str_replace(',', '', (string)($tx['credit'] ?? '0'));
                                        $creditFormatted = $creditNum > 0 ? CurrencyHelper::format($creditNum) : '';

                                        $balFormatted = $this->formatBalanceCr((string)($tx['balance'] ?? '0.00'));
                                    ?>
                                    <tr>
                                        <td class="col-date"><?= $txDate ?></td>
                                        <td class="col-part"><?= $narration ?></td>
                                        <td class="col-inst"><?= $instNo ?></td>
                                        <td class="col-num"><?= $debitFormatted ?></td>
                                        <td class="col-num"><?= $creditFormatted ?></td>
                                        <td class="col-num"><?= $balFormatted ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer (Every Page) -->
                    <div class="ubl-page-footer">
                        <div class="ubl-page-num-row">Page # <?= $pageNum ?> / <?= $totalPages ?></div>
                        <div class="ubl-note-text">
                            Note: The items and balance shown on this statement should be verified and the branch manager notified within 2 weeks of any discrepancies, otherwise it will be assumed as correct.
                        </div>
                        <div class="ubl-office-bar">
                            <div class="ubl-office-left">
                                UBL Ameen Head Office: 4th Floor, State Life Building No. 1, I.I Chundrigar Road, Karachi 74000
                            </div>
                            <div class="ubl-office-right">
                                <span>UAN: 021-111-825-111</span><br>
                                <span>www.ubldirect.com</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render complete HTML document for browser printing
     */
    public function renderPrint(array $statementData): string {
        $htmlBody = $this->renderHtml($statementData);
        $account = $statementData['account'] ?? [];
        $title = 'UBL Ameen Statement - ' . htmlspecialchars($account['account_title'] ?? 'Customer', ENT_QUOTES, 'UTF-8');

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
        <span style="font-weight: bold; font-size: 13px;">UBL Ameen Islamic Banking — Statement Print Preview</span>
        <button class="print-btn btn-print" onclick="window.print()">🖨 Print Statement</button>
    </div>
    {$htmlBody}
</body>
</html>
HTML;
    }
}
