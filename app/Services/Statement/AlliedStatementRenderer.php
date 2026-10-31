<?php
// app/Services/Statement/AlliedStatementRenderer.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/../../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/../../Core/Session.php';

/**
 * Allied Bank Limited (ABL) Statement Renderer
 *
 * Layout:
 *   1. Fixed Header Area
 *   2. Transaction Area
 *   3. Fixed Footer Area
 *
 * Page size:
 *   A4 Portrait — 210mm x 297mm
 */
class AlliedStatementRenderer implements StatementRendererInterface
{
    protected string $formatCode = 'allied';
    protected string $formatName = 'Allied Bank Statement Layout';


    public function getSupportedFormatCode(): string
    {
        return $this->formatCode;
    }


    public function getFormatName(): string
    {
        return $this->formatName;
    }


    /* =========================================================
       DATE FORMATTING
       ========================================================= */

    protected function formatDateDmy(?string $dateStr): string
    {
        if (!$dateStr) {
            return '';
        }

        $ts = strtotime($dateStr);

        return $ts
            ? strtoupper(date('d M y', $ts))
            : $dateStr;
    }


    protected function formatPeriodDate(?string $dateStr): string
    {
        if (!$dateStr) {
            return '';
        }

        $ts = strtotime($dateStr);

        return $ts
            ? strtoupper(date('d M Y', $ts))
            : $dateStr;
    }


    /* =========================================================
       DEFAULT ABL LOGO
       ========================================================= */

    protected function getDefaultLogoSvg(): string
    {
        ob_start();
        ?>
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 240 50"
            style="height: 42px; width: auto; display: block;"
        >
            <g transform="translate(2, 2) scale(0.75)">
                <polygon
                    points="26,2 50,48 37,48 26,26 15,48 2,48"
                    fill="#e65100"
                />

                <polygon
                    points="26,12 42,44 33,44 26,30 19,44 10,44"
                    fill="#f57c00"
                />

                <polygon
                    points="26,22 35,40 28,40 26,35 24,40 17,40"
                    fill="#ff9800"
                />

                <line
                    x1="8"
                    y1="36"
                    x2="44"
                    y2="36"
                    stroke="#bf360c"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />
            </g>

            <text
                x="48"
                y="32"
                font-family="'Segoe UI', Arial, sans-serif"
                font-size="24"
                font-weight="300"
                fill="#444444"
                letter-spacing="0.3"
            >
                Allied Bank
            </text>
        </svg>
        <?php

        return trim(ob_get_clean());
    }


    /* =========================================================
       SHARED STYLES
       ========================================================= */

    protected function getSharedStyles(): string
    {
        return <<<CSS

/* =========================================================
   GLOBAL
   ========================================================= */

.abl-statement-wrapper {
    font-family: Arial, Helvetica, sans-serif !important;
    color: #222;
    background: transparent;
    font-size: 11px;
    line-height: 1.25;

    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}


/* =========================================================
   ABL A4 PAGE

   Exact physical size:

       Width  = 210mm
       Height = 297mm

   The page itself contains exactly three zones:

       HEADER
       TRANSACTIONS
       FOOTER
   ========================================================= */

.abl-page {
    width: 210mm;
    height: 297mm;

    margin: 0 auto;

    padding: 0;

    background: #fff;

    position: relative;

    overflow: hidden;

    page-break-after: always;
    break-after: page;

    page-break-inside: avoid;
    break-inside: avoid;

    box-sizing: border-box;
}

.abl-page:last-child {
    page-break-after: auto;
    break-after: auto;
}


/* =========================================================
   HEADER ZONE

   Original ABL scanned statement has approximately this
   upper section before the transaction table starts.

   Header is independent from the transaction area.
   ========================================================= */

.abl-header-section {
    position: absolute;

    left: 12mm;
    right: 12mm;

    top: 8mm;

    height: 46mm;

    overflow: hidden;
}


/* =========================================================
   HEADER TABLE
   ========================================================= */

.abl-header-table {
    width: 100%;

    border-collapse: collapse;

    margin: 0;

    table-layout: fixed;
}

.abl-header-table td {
    vertical-align: top;

    padding: 0;
}


/* =========================================================
   LOGO
   ========================================================= */

.abl-logo-row {
    height: 13mm;

    vertical-align: top !important;
}

.abl-logo-wrap {
    height: 12mm;

    display: flex;

    align-items: flex-start;
    justify-content: flex-start;
}

.abl-logo-wrap img {
    height: 42px;

    width: auto;

    max-width: 240px;

    object-fit: contain;

    display: block;
}


/* =========================================================
   CUSTOMER SIDE
   ========================================================= */

.abl-customer-cell {
    width: 61%;

    padding-right: 15px !important;
}

.abl-customer-line {
    font-size: 10.5px;

    line-height: 1.45;

    color: #222;

    text-transform: uppercase;

    white-space: nowrap;
}

.abl-customer-title {
    font-weight: normal;
}


/* =========================================================
   BANK SIDE
   ========================================================= */

.abl-bank-cell {
    width: 39%;

    padding-left: 8px !important;
}

.abl-bank-line {
    display: flex;

    width: 100%;

    white-space: nowrap;

    font-size: 10.5px;

    line-height: 1.45;

    color: #222;
}

.abl-bank-label {
    flex: 0 0 auto;

    margin-right: 4px;

    font-weight: normal;
}

.abl-bank-value {
    flex: 0 0 auto;

    font-weight: normal;
}


/* =========================================================
   OPENING BALANCE
   ========================================================= */

.abl-start-balance-row {
    width: 100%;

    display: flex;

    justify-content: space-between;

    align-items: baseline;

    white-space: nowrap;

    font-size: 10.5px;

    line-height: 1.45;
}

.abl-start-bal-label {
    font-weight: normal;

    letter-spacing: 0.15px;
}

.abl-start-bal-val {
    min-width: 90px;

    text-align: right;

    font-variant-numeric: tabular-nums;
}


/* =========================================================
   TRANSACTION ZONE

   IMPORTANT:
   This is a completely separate zone from the header
   and footer.

   Header ends around 54mm.

   Footer starts around 276mm.

   Therefore transaction area has its own fixed vertical
   space between them.
   ========================================================= */

.abl-transaction-section {
    position: absolute;

    left: 12mm;
    right: 12mm;

    top: 54mm;
    bottom: 18mm;

    overflow: hidden;
}


/* =========================================================
   TRANSACTION TABLE
   ========================================================= */

.abl-table {
    width: 100%;

    border-collapse: collapse;

    font-size: 10.5px;

    table-layout: fixed;

    margin: 0;
}

.abl-table thead tr {
    border-top: 1px dashed #444;

    border-bottom: 1px dashed #444;
}

.abl-table th {
    font-weight: normal;

    color: #222;

    padding: 5px 4px;

    text-align: left;

    background: transparent !important;

    font-size: 10.5px;

    letter-spacing: 0.2px;
}

.abl-table th.num-col {
    text-align: right;
}

.abl-table td {
    padding: 2.5px 4px;

    vertical-align: top;

    color: #222;

    border: none;

    font-size: 10px;

    line-height: 1.3;
}


/* =========================================================
   TRANSACTION COLUMN WIDTHS
   ========================================================= */

.col-abl-date {
    width: 11%;
}

.col-abl-part {
    width: 33%;

    padding-right: 8px;
}

.col-abl-valdate {
    width: 13%;
}

.col-abl-debits {
    width: 14%;

    text-align: right;
}

.col-abl-credits {
    width: 14%;

    text-align: right;
}

.col-abl-balance {
    width: 15%;

    text-align: right;
}


/* =========================================================
   NUMERIC CELLS
   ========================================================= */

.num-cell {
    text-align: right;

    font-variant-numeric: tabular-nums;

    white-space: nowrap;
}


/* =========================================================
   SECONDARY REFERENCE LINE

   Examples:
       462840
       000176
       AC-PL552...
   ========================================================= */

.ref-secondary {
    font-size: 10px;

    color: #222;

    display: block;

    margin-top: 1px;
}


/*
 * Never intentionally split one transaction into two
 * physical pages.
 */
.abl-table tr {
    page-break-inside: avoid;

    break-inside: avoid;
}


/* =========================================================
   SUMMARY SECTION

   Only appears on final logical statement page.
   ========================================================= */

.abl-summary-wrap {
    margin-top: 10px;

    width: 100%;
}

.abl-summary-table {
    width: 100%;

    border-collapse: collapse;

    font-size: 10.5px;

    table-layout: fixed;
}

.abl-summary-table td {
    padding: 2px 4px;

    vertical-align: top;

    color: #222;

    font-size: 10.5px;
}


/* =========================================================
   FOOTER ZONE

   Footer contains ONLY:

       *REVE- Reversal Entry
       This is a computer-generated statement...
       X of Y

   Nothing else belongs in this area.
   ========================================================= */

.abl-footer {
    position: absolute;

    left: 12mm;
    right: 12mm;

    bottom: 7mm;

    height: 10mm;

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    font-size: 9.5px;

    color: #555;

    line-height: 1.3;

    overflow: hidden;
}

.abl-footer-left {
    max-width: 88%;
}

.abl-footer-right {
    text-align: right;

    font-size: 10px;

    color: #333;

    white-space: nowrap;

    padding-left: 8px;
}


/* =========================================================
   SCREEN PREVIEW
   ========================================================= */

.abl-interactive-container {
    background: #e2e8f0;

    padding: 24px 12px;

    min-height: 100vh;
}

@media screen {

    .abl-page {
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.08);

        border: 1px solid #cbd5e1;

        margin-bottom: 24px;
    }
}


/* =========================================================
   PRINT
   ========================================================= */

@media print {

    .no-print {
        display: none !important;
    }

    html,
    body {
        margin: 0 !important;

        padding: 0 !important;

        background: #fff !important;
    }

    .abl-statement-wrapper {
        margin: 0 !important;

        padding: 0 !important;

        background: #fff !important;
    }

    .abl-page {
        width: 210mm !important;

        height: 297mm !important;

        margin: 0 !important;

        padding: 0 !important;

        border: none !important;

        box-shadow: none !important;

        overflow: hidden !important;

        page-break-after: always !important;

        break-after: page !important;

        page-break-inside: avoid !important;

        break-inside: avoid !important;
    }

    .abl-page:last-child {
        page-break-after: auto !important;

        break-after: auto !important;
    }

    .abl-header-section {
        position: absolute !important;

        top: 8mm !important;

        left: 12mm !important;

        right: 12mm !important;

        height: 46mm !important;
    }

    .abl-transaction-section {
        position: absolute !important;

        top: 54mm !important;

        left: 12mm !important;

        right: 12mm !important;

        bottom: 21mm !important;

        overflow: hidden !important;
    }

    .abl-footer {
        position: absolute !important;

        left: 12mm !important;

        right: 12mm !important;

        bottom: 7mm !important;

        height: 10mm !important;

        margin: 0 !important;

        padding: 0 !important;
    }

    .abl-table tr {
        page-break-inside: avoid !important;

        break-inside: avoid !important;
    }
}

CSS;
    }


    /* =========================================================
       TRANSACTION PAGINATION
       ========================================================= */

    /**

     * Compute estimated physical vertical height (in mm) of a transaction row in the Allied table

     */

    protected function getTransactionHeight(array $tx): float

    {

        $narrationRaw = trim((string)($tx['narration'] ?? ''));

        $refNoRaw = (!empty($tx['reference_no']) && $tx['reference_no'] !== '-') ? trim((string)$tx['reference_no']) : '';

        $narrationLines = preg_split('/\r\n|\r|\n/', $narrationRaw);



        $visualLines = 0;

        $mainLine = trim($narrationLines[0] ?? '');

        $mainLen = mb_strlen($mainLine);

        // Column .col-abl-part is 33% width (~62mm). In 10px Arial, ~42 chars fit per line.

        $mainLineCount = max(1, (int)ceil($mainLen / 42));

        $visualLines += $mainLineCount;



        if (count($narrationLines) > 1) {

            $secLine = trim($narrationLines[1]);

            $secLen = mb_strlen($secLine);

            $visualLines += max(1, (int)ceil($secLen / 42));

        } elseif (!empty($refNoRaw)) {

            $visualLines += max(1, (int)ceil(mb_strlen($refNoRaw) / 42));

        }



        // Base row with padding takes 5.5mm; each subsequent wrapped line takes 3.8mm

        return 5.5 + ($visualLines - 1) * 3.8;

    }

    protected function chunkTransactions(array $transactions): array
    {
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

            $maxCount = ($pageIndex === 0) ? 26 : 28;
            $maxHeight = ($pageIndex === 0) ? 185.0 : 195.0;

            // Reserve ~28mm space for the final summary table if this could be the final page
            $remainingTxs = $totalTx - $i;
            if ($remainingTxs <= $maxCount) {
                $maxHeight -= 28.0;
            }

            if ((($currentHeight + $h > $maxHeight) || (count($currentPage) >= $maxCount)) && !empty($currentPage)) {
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

    /* =========================================================
       DOCUMENT BODY
       ========================================================= */

    protected function renderDocumentBody(array $statementData): string
    {
        $account = $statementData['account'] ?? [];

        $transactions = $statementData['transactions'] ?? [];


        $opening = CurrencyHelper::format(
            $statementData['opening_balance'] ?? '0.00'
        );

        $closing = CurrencyHelper::format(
            $statementData['closing_balance'] ?? '0.00'
        );

        $totalDebit = CurrencyHelper::format(
            $statementData['total_debit'] ?? '0.00'
        );

        $totalCredit = CurrencyHelper::format(
            $statementData['total_credit'] ?? '0.00'
        );


        /* ---------------------------------------------------------
           PERIOD
           --------------------------------------------------------- */

        $startDateFormatted = $this->formatPeriodDate(
            $statementData['start_date'] ?? date('Y-m-01')
        );

        $endDateFormatted = $this->formatPeriodDate(
            $statementData['end_date'] ?? date('Y-m-d')
        );

        $periodString =
            "{$startDateFormatted} TO {$endDateFormatted}";


        /* ---------------------------------------------------------
           ACCOUNT
           --------------------------------------------------------- */

        $accountNo = htmlspecialchars(
            $account['account_number']
                ?? $account['account_identifier']
                ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $accountTitle = htmlspecialchars(
            strtoupper(
                $account['account_title']
                    ?? $account['account_identifier']
                    ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );


        /* ---------------------------------------------------------
           ADDRESS
           --------------------------------------------------------- */

        $addressLines = [];

        if (!empty($account['address'])) {

            $rawLines = preg_split(
                '/\r\n|\r|\n/',
                trim((string)$account['address'])
            );

            foreach ($rawLines as $line) {

                $trimmedLine = trim($line);

                if ($trimmedLine !== '') {

                    $addressLines[] = htmlspecialchars(
                        strtoupper($trimmedLine),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                }
            }
        }


        if (!empty($account['city'])) {

            $addressLines[] = htmlspecialchars(
                strtoupper(trim((string)$account['city'])),
                ENT_QUOTES,
                'UTF-8'
            );
        }


        if (!empty($account['postal_code'])) {

            $addressLines[] = htmlspecialchars(
                strtoupper(trim((string)$account['postal_code'])),
                ENT_QUOTES,
                'UTF-8'
            );
        }


        if (!empty($account['province_country'])) {

            $addressLines[] = htmlspecialchars(
                strtoupper(
                    trim((string)$account['province_country'])
                ),
                ENT_QUOTES,
                'UTF-8'
            );
        }


        if (!empty($account['phone1'])) {

            $addressLines[] = htmlspecialchars(
                trim((string)$account['phone1']),
                ENT_QUOTES,
                'UTF-8'
            );
        }


        if (!empty($account['phone2'])) {

            $addressLines[] = htmlspecialchars(
                trim((string)$account['phone2']),
                ENT_QUOTES,
                'UTF-8'
            );
        }


        /* ---------------------------------------------------------
           BRANCH
           --------------------------------------------------------- */

        $branchName = '';

        if (!empty($account['branch_name'])) {

            $branchName = strtoupper(
                trim((string)$account['branch_name'])
            );
        }


        /* ---------------------------------------------------------
           ACCOUNT STATUS
           --------------------------------------------------------- */

        $rawStatus = trim(
            (string)(
                $account['account_status']
                    ?? $account['account_type']
                    ?? ''
            )
        );

        $accountStatus = !empty($rawStatus)
            ? htmlspecialchars(
                ucwords(strtolower($rawStatus)),

                ENT_QUOTES,
                'UTF-8'
            )
            : '';


        /* ---------------------------------------------------------
           PAGINATION
           --------------------------------------------------------- */

        $pages = $this->chunkTransactions(
            $transactions
        );

        $totalPages = count($pages);


        /* ---------------------------------------------------------
           HTML
           --------------------------------------------------------- */

        ob_start();
        ?>

        <div class="abl-statement-wrapper">

            <?php foreach ($pages as $pIdx => $pageTx): ?>

                <?php
                    $pageNum = $pIdx + 1;

                    $isFirstPage = ($pageNum === 1);

                    $isLastPage = ($pageNum === $totalPages);
                ?>


                <!-- =================================================
                     A4 PAGE
                     ================================================= -->

                <div class="abl-page">


                    <!-- =============================================
                         ZONE 1 — HEADER
                         ============================================= -->

                    <div class="abl-header-section">

                        <table class="abl-header-table">

                            <!-- Logo -->
                            <tr>

                                <td
                                    colspan="2"
                                    class="abl-logo-row"
                                >

                                    <div class="abl-logo-wrap">

                                        <?php if (!empty($account['bank_logo'])): ?>

                                            <img
                                                src="/atm/public/<?= htmlspecialchars(
                                                    $account['bank_logo'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                alt="Allied Bank"
                                            >

                                        <?php else: ?>

                                            <?= $this->getDefaultLogoSvg() ?>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>


                            <!-- Account Name / Branch -->
                            <tr>

                                <td class="abl-customer-cell">

                                    <div class="abl-customer-line">
                                        <?= $accountTitle ?>
                                    </div>

                                </td>


                                <td class="abl-bank-cell">

                                    <div class="abl-bank-line">

                                        <span class="abl-bank-label">
                                            Branch Name:
                                        </span>

                                        <span class="abl-bank-value">
                                            <?= htmlspecialchars(
                                                $branchName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </div>

                                </td>

                            </tr>


                            <!-- Address / Statement Period -->
                            <tr>

                                <td class="abl-customer-cell">


                                    <?php if (!empty($addressLines[0])): ?>

                                        <div class="abl-customer-line">
                                            <?= $addressLines[0] ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td class="abl-bank-cell">

                                    <div class="abl-bank-line">

                                        <span class="abl-bank-label">
                                            Statement Period:
                                        </span>

                                        <span class="abl-bank-value">
                                            <?= $periodString ?>
                                        </span>

                                    </div>

                                </td>

                            </tr>


                            <!-- City / Account Number -->
                            <tr>

                                <td class="abl-customer-cell">

                                    <?php if (!empty($addressLines[1])): ?>

                                        <div class="abl-customer-line">
                                            <?= $addressLines[1] ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td class="abl-bank-cell">

                                    <div class="abl-bank-line">

                                        <span class="abl-bank-label">
                                            Account Number:
                                        </span>

                                        <span class="abl-bank-value">
                                            <?= $accountNo ?>
                                        </span>

                                    </div>

                                </td>

                            </tr>


                            <!-- Postal / Account Status -->
                            <tr>

                                <td class="abl-customer-cell">

                                    <?php if (!empty($addressLines[2])): ?>

                                        <div class="abl-customer-line">
                                            <?= $addressLines[2] ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td class="abl-bank-cell">

                                    <div class="abl-bank-line">

                                        <span class="abl-bank-label">
                                            Account Status:
                                        </span>

                                        <span class="abl-bank-value">
                                            <?= $accountStatus ?>
                                        </span>

                                    </div>

                                </td>

                            </tr>


                            <!-- Province / Currency -->
                            <tr>

                                <td class="abl-customer-cell">

                                    <?php if (!empty($addressLines[3])): ?>

                                        <div class="abl-customer-line">
                                            <?= $addressLines[3] ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td class="abl-bank-cell">

                                    <div class="abl-bank-line">

                                        <span class="abl-bank-value">
                                            <?= htmlspecialchars(!empty($account['currency']) ? $account['currency'] : 'Pakistan Rupee', ENT_QUOTES, 'UTF-8') ?>
                                        </span>

                                    </div>

                                </td>

                            </tr>


                            <!-- Phone 1 -->
                            <tr>

                                <td class="abl-customer-cell">

                                    <?php if (!empty($addressLines[4])): ?>

                                        <div class="abl-customer-line">
                                            <?= $addressLines[4] ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td class="abl-bank-cell"></td>

                            </tr>


                            <!-- Phone 2 / Opening Balance -->
                            <tr>

                                <td class="abl-customer-cell">

                                    <?php if (!empty($addressLines[5])): ?>

                                        <div class="abl-customer-line">
                                            <?= $addressLines[5] ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <td class="abl-bank-cell">

                                    <?php if ($isFirstPage): ?>

                                        <div class="abl-start-balance-row">

                                            <span class="abl-start-bal-label">
                                                BALANCE AT PERIOD START :
                                            </span>

                                            <span class="abl-start-bal-val">
                                                <?= $opening ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        </table>

                    </div>


                    <!-- =============================================
                         ZONE 2 — TRANSACTIONS
                         ============================================= -->

                    <div class="abl-transaction-section">

                        <table class="abl-table">

                            <thead>

                                <tr>

                                    <th class="col-abl-date">
                                        DATE
                                    </th>

                                    <th class="col-abl-part">
                                        PARTICULARS
                                    </th>

                                    <th class="col-abl-valdate">
                                        VALUE DATE
                                    </th>

                                    <th class="col-abl-debits num-col">
                                        DEBITS
                                    </th>

                                    <th class="col-abl-credits num-col">
                                        CREDITS
                                    </th>

                                    <th class="col-abl-balance num-col">
                                        BALANCE
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (
                                    empty($pageTx)
                                    && $isFirstPage
                                ): ?>

                                    <tr>

                                        <td
                                            colspan="6"
                                            style="
                                                padding: 24px;
                                                text-align: center;
                                                color: #666;
                                                font-style: italic;
                                            "
                                        >
                                            No transactions recorded for this statement period.
                                        </td>

                                    </tr>

                                <?php else: ?>


                                    <?php foreach ($pageTx as $tx): ?>

                                        <?php

                                        $txDate = htmlspecialchars(
                                            $this->formatDateDmy(
                                                $tx['date'] ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );


                                        $valDate = !empty($tx['value_date'])
                                            ? htmlspecialchars(
                                                $this->formatDateDmy(
                                                    $tx['value_date']
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : $txDate;


                                        $narrationRaw = trim(
                                            (string)(
                                                $tx['narration'] ?? ''
                                            )
                                        );


                                        $refNoRaw = (
                                            !empty($tx['reference_no'])
                                            && $tx['reference_no'] !== '-'
                                        )
                                            ? trim(
                                                (string)$tx['reference_no']
                                            )
                                            : '';


                                        $narrationLines = preg_split(
                                            '/\r\n|\r|\n/',
                                            $narrationRaw
                                        );


                                        $mainNarration = htmlspecialchars(
                                            trim(
                                                $narrationLines[0] ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );


                                        $secondaryText = '';


                                        if (
                                            count($narrationLines) > 1
                                        ) {

                                            $secondaryText =
                                                htmlspecialchars(
                                                    trim(
                                                        $narrationLines[1]
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );

                                        } elseif (
                                            !empty($refNoRaw)
                                        ) {

                                            $secondaryText =
                                                htmlspecialchars(
                                                    trim($refNoRaw),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                        }


                                        $debitVal =
                                            (string)(
                                                $tx['debit'] ?? 0
                                            );


                                        $creditVal =
                                            (string)(
                                                $tx['credit'] ?? 0
                                            );


                                        $debitStr =
                                            CurrencyHelper::comp(
                                                $debitVal,
                                                '0.00'
                                            ) > 0
                                                ? CurrencyHelper::format(
                                                    $debitVal
                                                )
                                                : '';


                                        $creditStr =
                                            CurrencyHelper::comp(
                                                $creditVal,
                                                '0.00'
                                            ) > 0
                                                ? CurrencyHelper::format(
                                                    $creditVal
                                                )
                                                : '';


                                        $balanceStr =
                                            CurrencyHelper::format(
                                                $tx['balance']
                                                    ?? '0.00'
                                            );

                                        ?>


                                        <tr>

                                            <td class="col-abl-date">
                                                <?= $txDate ?>
                                            </td>


                                            <td class="col-abl-part">

                                                <div>
                                                    <?= $mainNarration ?>
                                                </div>

                                                <?php if (
                                                    !empty($secondaryText)
                                                ): ?>

                                                    <span class="ref-secondary">
                                                        <?= $secondaryText ?>
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <td class="col-abl-valdate">
                                                <?= $valDate ?>
                                            </td>


                                            <td class="col-abl-debits num-cell">
                                                <?= $debitStr ?>
                                            </td>


                                            <td class="col-abl-credits num-cell">
                                                <?= $creditStr ?>
                                            </td>


                                            <td class="col-abl-balance num-cell">
                                                <?= $balanceStr ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>


                        <!-- =============================================
                             FINAL SUMMARY
                             ============================================= -->

                        <?php if ($isLastPage): ?>

                            <div class="abl-summary-wrap">

                                <table class="abl-summary-table">

                                    <tr>

                                        <td class="col-abl-date"></td>

                                        <td
                                            class="col-abl-part"
                                            style="
                                                text-align: right;
                                                padding-right: 14px;
                                            "
                                        >
                                            TOTAL DEBIT / CREDIT
                                        </td>

                                        <td class="col-abl-valdate"></td>

                                        <td class="col-abl-debits num-cell">
                                            <?= $totalDebit ?>
                                        </td>

                                        <td class="col-abl-credits num-cell">
                                            <?= $totalCredit ?>
                                        </td>

                                        <td class="col-abl-balance"></td>

                                    </tr>


                                    <tr>

                                        <td class="col-abl-date"></td>

                                        <td
                                            class="col-abl-part"
                                            style="
                                                text-align: right;
                                                padding-right: 14px;
                                            "
                                        >
                                            CLOSING BALANCE
                                        </td>

                                        <td class="col-abl-valdate"></td>

                                        <td class="col-abl-debits"></td>

                                        <td class="col-abl-credits"></td>

                                        <td class="col-abl-balance num-cell">
                                            <?= $closing ?>
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="col-abl-date"></td>

                                        <td
                                            class="col-abl-part"
                                            style="
                                                text-align: right;
                                                padding-right: 14px;
                                            "
                                        >
                                            TOTAL WITHHOLDING TAX DEDUCTED
                                        </td>

                                        <td class="col-abl-valdate"></td>

                                        <td class="col-abl-debits"></td>

                                        <td class="col-abl-credits"></td>

                                        <td class="col-abl-balance num-cell">
                                            0.00
                                        </td>

                                    </tr>

                                </table>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- =============================================
                         ZONE 3 — FOOTER
                         ============================================= -->

                    <div class="abl-footer">

                        <div class="abl-footer-left">

                            <div>
                                *REVE- Reversal Entry
                            </div>

                            <div style="margin-top: 1px;">
                                This is a computer-generated statement, issued without any alteration, and does not require any signature.
                            </div>

                        </div>


                        <div class="abl-footer-right">

                            <?= $pageNum ?>
                            of
                            <?= $totalPages ?>

                        </div>

                    </div>


                </div>

            <?php endforeach; ?>

        </div>

        <?php

        return trim(ob_get_clean());
    }


    /* =========================================================
       INTERACTIVE HTML PREVIEW
       ========================================================= */

    public function renderHtml(array $statementData): string
    {
        $styles = $this->getSharedStyles();

        $body = $this->renderDocumentBody(
            $statementData
        );


        ob_start();
        ?>

        <div class="abl-interactive-container">

            <style>
                <?= $styles ?>
            </style>

            <?= $body ?>

        </div>

        <?php

        return trim(ob_get_clean());
    }


    /* =========================================================
       PRINT VIEW
       ========================================================= */

    public function renderPrint(array $statementData): string
    {
        $styles = $this->getSharedStyles();

        $body = $this->renderDocumentBody(
            $statementData
        );


        ob_start();
        ?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Account Statement — Allied Bank
    </title>


    <style>

        /*
         * Browser PDF page itself is exactly A4.
         *
         * We deliberately use ZERO browser page margins.
         * All visual margins are controlled by the ABL
         * header / transaction / footer zones.
         */

        @page {
            size: A4 portrait;

            margin: 0;
        }


        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }


        html,
        body {
            width: 100%;

            margin: 0;

            padding: 0;

            background: #fff;
        }


        <?= $styles ?>


        @media print {

            .no-print {
                display: none !important;
            }

        }

    </style>

</head>


<body>


    <!-- =========================================================
         PRINT PREVIEW TOOLBAR

         This is hidden when actual printing / Save as PDF.
         ========================================================= -->

    <div
        class="no-print"
        style="
            background: #f1f5f9;
            padding: 10px 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: Arial, sans-serif;
        "
    >

        <span
            style="
                font-weight: bold;
                color: #1e293b;
                font-size: 12px;
            "
        >
            Print Preview — Allied Bank Limited (ABL) Account Statement
        </span>


        <div>

            <button
                onclick="window.print()"
                style="
                    padding: 6px 16px;
                    font-weight: bold;
                    background: #005a9c;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                "
            >
                Print Document
            </button>


            <button
                onclick="window.close()"
                style="
                    padding: 6px 12px;
                    margin-left: 8px;
                    background: #fff;
                    border: 1px solid #94a3b8;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                "
            >
                Close
            </button>

        </div>

    </div>


    <?= $body ?>


</body>

</html>

        <?php

        return trim(ob_get_clean());
    }
}