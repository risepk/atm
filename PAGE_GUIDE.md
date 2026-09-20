# ATM Ledger — Page & Code Reference Guide (رہنمائی برائے ڈویلپر)

خوش آمدید! اس دستاویز کا مقصد آپ کے لیے سسٹم کے تمام پیجز کو تلاش کرنا، براؤزر میں براہ راست `.php` ایکسٹینشن کے ساتھ کھولنا، اور اپنی مرضی کے مطابق کوڈ میں چھوٹی موٹی تبدیلیاں کرنا آسان بنانا ہے۔

---

## 1. براؤزر لنکس اور فائل میپنگ (Complete Pages & Files Map)

اب آپ براؤزر میں ہر پیج کو براہ راست `.php` لگا کر کھول سکتے ہیں۔ نیچے مکمل فہرست دی گئی ہے کہ کون سا براؤزر لنک کس فائل کے ساتھ منسلک ہے:

| براؤزر کا لنک (Browser URL) | متبادل چھوٹا لنک (Alias) | ویو فائل (View / HTML File) | کنٹرولر فائل (Controller) | تفصیل اور مقصد (Description) |
| :--- | :--- | :--- | :--- | :--- |
| **`/atm/dashboard.php`** | `/atm/dashboard` | `views/dashboard/admin.php`<br>`views/dashboard/user.php` | `app/Controllers/DashboardController.php` | مرکزی ڈیش بورڈ (کل یوزرز، کھاتے دار، ٹوٹل بیلنس اور سمری) |
| **`/atm/transactions/rapid.php`** | `/atm/rapid.php`<br>`/atm/transaction.php`<br>`/atm/home.php` | `views/transactions/rapid.php` | `app/Controllers/TransactionController.php` | **TransactionFrm:** ٹرانزیکشن انٹری فارم، Particulars فیلڈ، آٹو تجویزاتی بٹنز |
| **`/atm/accounts.php`** | `/atm/account.php` | `views/accounts/index.php` | `app/Controllers/AccountController.php` | **Account Detail:** تمام کھاتوں کی لسٹ، نیا اکاؤنٹ بنانا اور بیلنس دیکھنا |
| **`/atm/transactions.php`** | `/atm/ledger.php` | `views/transactions/index.php` | `app/Controllers/TransactionController.php` | **Transaction Ledger:** تمام ٹرانزیکشنز کا جنرل جرنل، فلٹر اور ہسٹری |
| **`/atm/statements.php`** | `/atm/statement.php` | `views/statements/view.php` | `app/Controllers/StatementController.php` | **Bank Statements Dialog:** بینک اسٹیٹمنٹ جنریشن فارم اور سرچ |
| **`/atm/statements/print.php`** | `/atm/print.php` | `views/statements/print.php`<br>+ Renderers | `app/Controllers/StatementController.php` | **Print Report:** پرنٹ کے لیے تیار شدہ اسٹیٹمنٹ رپورٹ (A4 فارمیٹ) |
| **`/atm/banks.php`** | `/atm/bank.php` | `views/banks/index.php` | `app/Controllers/MasterDataController.php` | **Bank Registry:** بینکس کا اندراج اور بینک لوگو (Logo) اپلوڈ کرنا |
| **`/atm/templates.php`** | `/atm/template.php` | `views/templates/index.php` | `app/Controllers/TemplateController.php` | **Narration Templates:** خودکار نریشن پیٹرنز کی فہرست |
| **`/atm/templates/{id}/edit.php`** | `/atm/templates/{id}/edit` | `views/templates/edit.php` | `app/Controllers/TemplateController.php` | **Template Editor:** مخصوص ٹیمپلیٹ کے متغیرات (Variables) ایڈٹ کرنا |
| **`/atm/users.php`** | `/atm/user.php` | `views/users/index.php` | `app/Controllers/UserController.php` | **User Admin:** نئے آپریٹرز اور ایڈمن کا اندراج اور پاس ورڈ ری سیٹ |
| **`/atm/login.php`** | `/atm/login` | `views/auth/login.php` | `app/Controllers/AuthController.php` | لاگ ان ونڈو (Authentication Window) |

---

## 2. اہم فائلوں میں تبدیلی کی رہنمائی (Developer Editing Instructions)

### الف. بینک اسٹیٹمنٹ کے پرنٹ فارمیٹ میں تبدیلی:
- **فائل کا راستہ:** `app/Services/Statement/FaysalStatementRenderer.php`
- **تفصیل:** فیصل بینک کی پرنٹ رپورٹ کا پورا لے آؤٹ (HTML اور CSS) اس فائل میں ہے۔
- **اگر آپ نے لوگو کا سائز، مارجن، فونٹ سائز یا ٹیبل کالمز کی چوڑائی بدلنی ہو:** تو اس فائل میں موجود `generatePrintCss()` اور `renderPrint()` فنکشنز کو اپنی مرضی کے مطابق ایڈٹ کر سکتے ہیں۔

### ب. ٹرانزیکشن انٹری فارم (TransactionFrm):
- **فائل کا راستہ:** `views/transactions/rapid.php`
- **تفصیل:** انٹری فارم کے فیلڈز (Date, Voucher No, Dr/Cr, Particulars، اور نیچے والے فوری بٹنز) اس فائل میں ہیں۔
- **فوری بٹنز (Quick Buttons):** لائن 75 کے قریب بٹنز کا کنٹینر `#narration-pills-container` ہے جہاں آپ مزید بٹنز کا لے آؤٹ تبدیل کر سکتے ہیں۔

### ج. نیوی گیشن مینیو اور ربن بار (Top Header & Ribbon):
- **فائل کا راستہ:** `views/layouts/navigation.php`
- **تفصیل:** اوپری ونڈوز ٹائٹل بار، مینیو لنکس اور بینکس کی پٹی (Ribbon Bank Buttons) اس فائل میں ہے۔
- **بینکس کی پٹی:** فائل کے شروع میں `$ribbonBanks` ارے (Array) میں بینکس کے نام اور رنگ دیے گئے ہیں۔

### د. ہیڈر، فوٹر اور اسٹائل شیٹ (Master Styles):
- **ہیڈر:** `views/layouts/header.php` (تمام ونڈوز 7/10 اسٹائل بٹنز، ان پٹس اور ٹیبلز کی CSS یہیں موجود ہے)
- **فوٹر:** `views/layouts/footer.php` (ٹوسٹ نوٹیفکیشن اور اسکرپٹس)

---

## 3. سسٹم کی مکمل مطابقت (100% Backward Compatibility)

- آپ براؤزر میں چاہے `.php` کے ساتھ یو آر ایل لکھیں (مثلاً `/atm/dashboard.php`) یا بغیر ایکسٹینشن کے لکھیں (مثلاً `/atm/dashboard`)، دونوں صورتوں میں سافٹ ویئر بالکل بغیر کسی خرابی کے یکساں اور تیز رفتار کام کرے گا۔
- تمام اندرونی لنکس، AJAX کالز اور ماڈلز مکمل محفوظ طریقے سے کام کرتے رہیں گے۔
