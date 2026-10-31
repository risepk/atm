<!-- 
================================================================================
File: views/statements/print.php
Browser URL: http://localhost/atm/statements/print.php (or /atm/print.php)
Description: Printable Bank Statement Output View (پرنٹ شدہ بینک اسٹیٹمنٹ رپورٹ)
--------------------------------------------------------------------------------
URDU GUIDE FOR DEVELOPER (رہنمائی برائے ڈویلپر):
1. جب بھی آپ اسٹیٹمنٹ پرنٹ کرتے ہیں، تو یہ فائل چلتی ہے۔
2. یہ فائل خودکار طریقے سے بینک کے منتخب فارمیٹ کو کال کرتی ہے:
   - الائیڈ بینک (Allied Bank Limited / ABL): app/Services/Statement/AlliedStatementRenderer.php
   - فیصل بینک (Faysal Bank): app/Services/Statement/FaysalStatementRenderer.php
   - میزان بینک (Meezan Bank Limited): app/Services/Statement/MeezanStatementRenderer.php
   - ای اسٹیٹمنٹ (Online Format): app/Services/Statement/OnlineStatementRenderer.php
   - حبیب بینک (HBL Format): app/Services/Statement/HBLStatementRenderer.php
   - اسٹینڈرڈ / ڈیفالٹ فارمیٹ: app/Services/Statement/StandardStatementRenderer.php
3. اگر آپ نے میزان بینک، الائیڈ بینک یا فیصل بینک کے پرنٹ پیج کا ڈیزائن (لوگو سائز، فونٹ، ٹیبل کی چوڑائی وغیرہ) بدلنا ہو،
   تو بالترتیب MeezanStatementRenderer.php، AlliedStatementRenderer.php یا FaysalStatementRenderer.php کو ایڈٹ کریں۔
================================================================================
-->
<?php
require_once __DIR__ . '/../../app/Services/Statement/StatementRendererFactory.php';

$formatCode = $statement['format']['format_code'] ?? 'standard';
$renderer = StatementRendererFactory::create($formatCode);
echo $renderer->renderPrint($statement);
