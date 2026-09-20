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

    protected function formatDateDmy(?string $dateStr): string {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        return $ts ? date('d-m-Y', $ts) : $dateStr;
    }

    protected function formatSummaryDate(?string $dateStr): string {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        return $ts ? strtoupper(date('d-M-y', $ts)) : $dateStr;
    }

    protected function getDefaultLogoSvg(): string {
        ob_start();
        ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 210 50" class="h-10 w-auto" style="display: block;">
            <text x="0" y="33" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="700" letter-spacing="-1" fill="#1c252c">abank</text>
            <g transform="translate(102, 6) scale(0.72)">
                <polygon points="24,0 48,14 48,42 24,56 0,42 0,14" fill="#202930" stroke="#111" stroke-width="1.5"/>
                <polygon points="24,8 40,18 40,38 24,48 8,38 8,18" fill="#ffffff"/>
                <polygon points="24,14 34,21 34,35 24,42 14,35 14,21" fill="#202930"/>
                <circle cx="24" cy="28" r="4.5" fill="#caa149"/>
            </g>
        </svg>
        <?php
        return trim(ob_get_clean());
    }

    protected function getSharedStyles(): string {
        return <<<CSS
        .faysal-doc {
            font-family: Arial, Helvetica, sans-serif !important;
            color: #000;
            background: #fff;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 24px;
            box-sizing: border-box;
            font-size: 11px;
            line-height: 1.25;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .header-table td {
            vertical-align: top;
        }

        .statement-date-text {
            white-space: nowrap !important;
            font-size: 11px;
        }

        .page-num-wrap {
            margin-top: 6px;
            padding-right: 24px; /* Right edge se 4-5 characters ka space */
            text-align: right;
            font-weight: 500;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            line-height: 1.5;
            margin-bottom: 14px;
        }
        .info-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .lbl {
            font-weight: bold;
            color: #000;
            white-space: nowrap;
        }
        .val {
            font-weight: 500;
            color: #000;
        }

        .statement-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            table-layout: fixed;
        }
        .statement-table thead tr {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }
        .statement-table th {
            font-weight: bold;
            color: #000;
            padding: 4px 3px;
            vertical-align: bottom;
            line-height: 1.15;
            background: transparent !important;
        }
        .statement-table td {
            padding: 2px 3px;
            vertical-align: top;
            color: #000;
            border: none;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        /* Fixed column widths */
        .col-post-date { width: 68px; }
        .col-eff-date  { width: 68px; }
        .col-narration { width: auto; padding-right: 6px; }
        .col-ref-no    { width: 115px; }
        .col-withdr    { width: 90px; }
        .col-dep       { width: 85px; }
        .col-bal       { width: 95px; }

        /* Alignment styles */
        .text-center-align {
            text-align: center;
            white-space: nowrap;
        }
        .text-left-align {
            text-align: left;
        }
        .num-txt {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        /* Totals Row */
        .totals-row td {
            border-top: 1px solid #222 !important;
            border-bottom: 1px solid #222 !important;
            padding: 3px 3px;
            font-weight: bold;
            font-size: 10.5px;
        }

        /* Summary Box */
        .summary-box {
            border: 1px solid #444;
            margin-top: 4px;
            padding: 4px 6px 4px 8px;
            page-break-inside: avoid;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            table-layout: fixed;
        }
        .summary-table td {
            vertical-align: top;
            padding: 1px 0;
            font-weight: bold;
        }

        /* Legal Notice */
        .legal-notice {
            margin-top: 24px;
            max-width: 65%;
            font-size: 9px;
            line-height: 1.35;
            color: #000;
            page-break-inside: avoid;
        }
        .legal-notice p {
            margin: 0 0 8px 0;
        }
CSS;
    }

    protected function renderDocumentBody(array $statementData): string {
        $account = $statementData['account'] ?? [];
        $transactions = $statementData['transactions'] ?? [];
        
        $opening = CurrencyHelper::format($statementData['opening_balance'] ?? '0.00');
        $closing = CurrencyHelper::format($statementData['closing_balance'] ?? '0.00');
        $totalDebit = CurrencyHelper::format($statementData['total_debit'] ?? '0.00');
        $totalCredit = CurrencyHelper::format($statementData['total_credit'] ?? '0.00');

        $startDateFormatted = $this->formatDateDmy($statementData['start_date'] ?? '2023-07-01');
        $endDateFormatted = $this->formatDateDmy($statementData['end_date'] ?? '2023-06-30');
        $summaryDate = $this->formatSummaryDate($statementData['end_date'] ?? '2023-06-30');

        $statementDateTime = date('d-m-Y H:i:s');
        $txCount = count($transactions);

        $accountNo = htmlspecialchars($account['account_number'] ?? $account['account_identifier'] ?? '', ENT_QUOTES, 'UTF-8');
        $accountTitle = htmlspecialchars(strtoupper($account['account_title'] ?? $account['account_identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
        $address = htmlspecialchars(strtoupper(trim((string)($account['address'] ?? ''))), ENT_QUOTES, 'UTF-8');

        $rawType = trim((string)($account['account_type'] ?? 'CURRENT'));
        $depositType = htmlspecialchars(strtoupper($rawType ?: 'CURRENT'), ENT_QUOTES, 'UTF-8');
        $currency = htmlspecialchars(strtoupper($account['currency'] ?? 'PKR'), ENT_QUOTES, 'UTF-8');

        ob_start();
        ?>
        <div class="faysal-doc">
            <!-- Header Table -->
            <table class="header-table">
                <tr>
                    <td style="width: 20%;">
                        <?php if (!empty($account['bank_logo'])): ?>
                            <img src="/atm/public/<?= htmlspecialchars($account['bank_logo'], ENT_QUOTES, 'UTF-8') ?>" alt="Faysal Bank" style="height: 38px; object-fit: contain;">
                        <?php else: ?>
                            <?= $this->getDefaultLogoSvg() ?>
                        <?php endif; ?>
                    </td>
                    <td style="width: 48%; padding-left: 10px; padding-top: 1px;">
                        <div style="font-size: 19px; font-weight: bold; letter-spacing: -0.2px;">Account Statement</div>
                        <div style="font-size: 11.5px; font-weight: bold; margin-top: 7px; white-space: nowrap;">
                            Statement Period From:&nbsp;&nbsp;<span style="text-decoration: underline;"><?= $startDateFormatted ?></span>&nbsp;&nbsp;To:&nbsp;&nbsp;<span style="text-decoration: underline;"><?= $endDateFormatted ?></span>
                        </div>
                    </td>
                    <td style="width: 32%; text-align: right; font-size: 11px; padding-top: 2px;">
                        <div class="statement-date-text">Statement Date:&nbsp;&nbsp;<?= $statementDateTime ?></div>
                        <!-- Page 1 of 1 thoda sa andar align (right edge se offset) -->
                        <div class="page-num-wrap">Page 1 of 1</div>
                    </td>
                </tr>
            </table>

            <!-- Account Details -->
            <table class="info-table">
                <tr>
                    <td style="width: 14%;" class="lbl">Account No :</td>
                    <td style="width: 32%;" class="val"><?= $accountNo ?></td>
                    <td style="width: 16%;" class="lbl">Title Of Account :</td>
                    <td style="width: 38%;" class="val"><?= $accountTitle ?></td>
                </tr>
                <tr>
                    <td class="lbl">Address :</td>
                    <td colspan="3" class="val"><?= $address ?></td>
                </tr>
                <tr>
                    <td class="lbl">Deposit Type :</td>
                    <td class="val"><?= $depositType ?></td>
                    <td class="lbl">Currency :</td>
                    <td class="val"><?= $currency ?></td>
                </tr>
            </table>

            <!-- Transaction Table -->
            <table class="statement-table">
                <thead>
                    <tr>
                        <!-- Dates header center aligned -->
                        <th class="col-post-date text-center-align" style="border-left: 1px solid black;">Posting<br>Date</th>
                        <th class="col-eff-date text-center-align">Effective<br>Date</th>
                        
                        <!-- Narration aur Reference No left aligned -->
                        <th class="col-narration text-left-align">Narration</th>
                        <th class="col-ref-no text-left-align">Reference No</th>
                        
                        <!-- Financial column headers left aligned -->
                        <th class="col-withdr text-left-align">Withdrawal</th>
                        <th class="col-dep text-left-align">Deposit</th>
                        <th class="col-bal text-left-align" style="border-right: 1px solid black;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" style="padding: 16px; text-align: center; color: #555; font-style: italic;">No transactions recorded for this period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): 
                            $postDate = htmlspecialchars($this->formatDateDmy($tx['date'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $effDate = $postDate;
                            $narration = htmlspecialchars(strtoupper($tx['narration'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $refNo = !empty($tx['reference_no']) && $tx['reference_no'] !== '-' ? htmlspecialchars(strtoupper($tx['reference_no']), ENT_QUOTES, 'UTF-8') : '';
                            
                            $debitVal = (string)($tx['debit'] ?? 0);
                            $creditVal = (string)($tx['credit'] ?? 0);
                            
                            $withdrawalStr = CurrencyHelper::comp($debitVal, '0.00') > 0 ? CurrencyHelper::format($debitVal) : '0.00';
                            $depositStr = CurrencyHelper::comp($creditVal, '0.00') > 0 ? CurrencyHelper::format($creditVal) : '0.00';
                            $balanceStr = CurrencyHelper::format($tx['balance'] ?? '0.00');
                        ?>
                            <tr>
                                <!-- Dates rows center aligned -->
                                <td class="col-post-date text-center-align"><?= $postDate ?></td>
                                <td class="col-eff-date text-center-align"><?= $effDate ?></td>
                                
                                <!-- Narration aur Reference No left aligned -->
                                <td class="col-narration text-left-align" style="word-break: break-word;"><?= $narration ?></td>
                                <td class="col-ref-no text-left-align"><?= $refNo ?></td>
                                
                                <!-- Values cells hamesha right aligned -->
                                <td class="col-withdr num-txt"><?= $withdrawalStr ?></td>
                                <td class="col-dep num-txt"><?= $depositStr ?></td>
                                <td class="col-bal num-txt"><?= $balanceStr ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Totals Line -->
                    <tr class="totals-row">
                        <td colspan="4" style="border-left: 1px solid black;"></td>
                        <td class="col-withdr num-txt"><?= $totalDebit ?></td>
                        <td class="col-dep num-txt"><?= $totalCredit ?></td>
                        <td class="col-bal" style="border-right: 1px solid black;"></td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary Box -->
            <div class="summary-box" style="margin-top: -1px;">
                <table class="summary-table">
                    <tr>
                        <td style="width: 28%;">
                            No. of Transactions (<?= $txCount ?>)
                        </td>
                        <td style="width: 44%; text-align: left; padding-left: 10px;">
                            <div>Ending Balance as of <?= $summaryDate ?></div>
                            <div style="margin-top: 4px;">Available Balance as of <?= $summaryDate ?></div>
                        </td>
                        <td style="width: 28%; text-align: right;">
                            <div class="num-txt" style="font-weight: bold;"><?= $closing ?></div>
                            <div class="num-txt" style="font-weight: bold; margin-top: 4px;"><?= $closing ?></div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Footer Legal Disclaimers -->
            <div class="legal-notice">
                <p style="font-weight: bold;">
                    ***AS PER SBP MANDATE PLEASE SUBMIT AN ATTESTED COPY OF<br>
                    YOUR VALID CNIC, IF NOT PROVIDED EARLIER. NON-PROVISION CAN<br>
                    HAMPER THE SERVICES***
                </p>
                <p style="font-weight: bold;">
                    ** FOR BANK'S PROFILE PLEASE VISIT OUR WEBSITE<br>
                    "WWW.FAYSALBANK.COM" **
                </p>
                <p style="font-weight: 500; color: #222;">
                    "FOR COMPLAINTS WHICH REMAIN UNRESOLVED BEYOND 45 DAYS,<br>
                    YOU MAY WRITE TO BANKING MOHTASIB PAKISTAN, SHAHEEN<br>
                    COMPLEX, M.R KIYANI ROAD, KARACHI OR VISIT<br>
                    WWW.BANKINGMOHTASIB.GOV.PK"
                </p>
            </div>
        </div>
        <?php
        return trim(ob_get_clean());
    }

    public function renderHtml(array $statementData): string {
        $styles = $this->getSharedStyles();
        $body = $this->renderDocumentBody($statementData);

        ob_start();
        ?>
        <div class="faysal-interactive-wrapper" style="background: #e2e8f0; padding: 20px 10px; min-height: 100vh;">
            <style>
                <?= $styles ?>
                .faysal-doc {
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                    border: 1px solid #cbd5e1;
                }
            </style>
            <?= $body ?>
        </div>
        <?php
        return trim(ob_get_clean());
    }

    public function renderPrint(array $statementData): string {
        $styles = $this->getSharedStyles();
        $body = $this->renderDocumentBody($statementData);

        ob_start();
        ?>
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
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        <?= $styles ?>
        @media print {
            .no-print {
                display: none !important;
            }
            .faysal-doc {
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f1f5f9; padding: 10px 16px; margin-bottom: 15px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; font-family: Arial, sans-serif;">
        <span style="font-weight: bold; color: #1e293b; font-size: 12px;">Print Preview — Faysal Bank Account Statement</span>
        <div>
            <button onclick="window.print()" style="padding: 6px 16px; font-weight: bold; background: #0f172a; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Print Document</button>
            <button onclick="window.close()" style="padding: 6px 12px; margin-left: 8px; background: #fff; border: 1px solid #94a3b8; border-radius: 4px; cursor: pointer; font-size: 12px;">Close</button>
        </div>
    </div>
    <?= $body ?>
</body>
</html>
        <?php
        return trim(ob_get_clean());
    }
}