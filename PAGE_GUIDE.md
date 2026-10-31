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
| **`/atm/statements/meezan.php`** | `/atm/meezan.php`<br>`/atm/meezan` | `views/statements/meezan.php` | `app/Controllers/StatementController.php` | **Meezan Statement Portal:** میزان بینک کی مخصوص اسٹیٹمنٹ (QUICK ACCOUNT TRANSACTIONS) جنریٹر |
| **`/atm/statements/hbl.php`** | `/atm/hbl.php`<br>`/atm/hbl` | `views/statements/hbl.php` | `app/Controllers/StatementController.php` | **HBL Statement Portal:** حبیب بینک لمیٹڈ کی مستند ٹرمینل ڈاٹ میٹرکس اسٹیٹمنٹ (111 کالمز، بروڈ فارورڈ اور پائپ لے آؤٹ) جنریٹر |
| **`/atm/statements/bankislami.php`** | `/atm/bankislami.php`<br>`/atm/bankislami`<br>`/atm/bipl`<br>`/atm/islami` | `views/statements/bankislami.php` | `app/Controllers/StatementController.php` | **BankIslami Statement Portal:** بینک اسلامی کی مستند اسٹیٹمنٹ (Duplicate Statement Of Account) جنریٹر |
| **`/atm/statements/ubl.php`** | `/atm/ubl.php`<br>`/atm/ubl`<br>`/atm/ublameen`<br>`/atm/ameen` | `views/statements/ubl.php` | `app/Controllers/StatementController.php` | **UBL Ameen Statement Portal:** یو بی ایل امین اسلامک بینکنگ کی مستند اسٹیٹمنٹ جنریٹر بمعہ ہیڈر بینر، کسٹمر میٹا باکس، انسٹرومنٹ نمبر اور Cr رننگ بیلنس |
| **`/atm/statements/print.php`** | `/atm/print.php` | `views/statements/print.php`<br>+ Renderers | `app/Controllers/StatementController.php` | **Print Report:** پرنٹ کے لیے تیار شدہ اسٹیٹمنٹ رپورٹ (A4 فارمیٹ) |
| **`/atm/banks.php`** | `/atm/bank.php` | `views/banks/index.php` | `app/Controllers/MasterDataController.php` | **Bank Registry:** بینکس کا اندراج اور بینک لوگو (Logo) اپلوڈ کرنا |
| **`/atm/templates.php`** | `/atm/template.php` | `views/templates/index.php` | `app/Controllers/TemplateController.php` | **Narration Templates:** خودکار نریشن پیٹرنز کی فہرست |
| **`/atm/templates/{id}/edit.php`** | `/atm/templates/{id}/edit` | `views/templates/edit.php` | `app/Controllers/TemplateController.php` | **Template Editor:** مخصوص ٹیمپلیٹ کے متغیرات (Variables) ایڈٹ کرنا |
| **`/atm/users.php`** | `/atm/user.php` | `views/users/index.php` | `app/Controllers/UserController.php` | **User Admin:** نئے آپریٹرز اور ایڈمن کا اندراج اور پاس ورڈ ری سیٹ |
| **`/atm/login.php`** | `/atm/login` | `views/auth/login.php` | `app/Controllers/AuthController.php` | لاگ ان ونڈو (Authentication Window) |

---

## 2. اہم فائلوں میں تبدیلی کی رہنمائی (Developer Editing Instructions)

### الف. بینک اسٹیٹمنٹ کے پرنٹ فارمیٹ میں تبدیلی:
- **حبیب بینک لمیٹڈ (Habib Bank Limited / HBL Statement Layout):** `app/Services/Statement/HBLStatementRenderer.php`
  اس فائل میں HBL کی مستند ٹرمینل و ڈاٹ میٹرکس پرنٹ رپورٹ ہے جو کلائنٹ کے بھیجے گئے اسکین کے عین مطابق 111 فکسڈ پچ کریکٹر کالمز، پائپ بارڈرز (`|`)، BROUGHT FORWARD کی کیری اوور لائن، برانچ و اکاؤنٹ میٹا ڈیٹا، پیجینیشن (Continue on next page / End of Statement) اور ریگولیٹری ڈس کلیمر پر مشتمل ہے۔
- **میزان بینک (Meezan Bank Limited / QUICK ACCOUNT TRANSACTIONS):** `app/Services/Statement/MeezanStatementRenderer.php`
  اس فائل میں میزان بینک کی مستند پرنٹ رپورٹ ہے جس میں ستارے والے کالم ہیڈرز (*******Debit********، *******Credit*******، ******Balance*******)، ڈیبٹ کے ساتھ منفی کی علامت (-34.24)، ہر تاریخ کی آخری ٹرانزیکشن پر بیلنس، سمری بلاک (Total Credit/Debit Turnover)، اور اسٹیٹ بینک و بینکنگ محتسب کے اردو و انگریزی قانونی نوٹسز شامل ہیں۔
- **الائیڈ بینک (Allied Bank Limited / ABL):** `app/Services/Statement/AlliedStatementRenderer.php`
  اس فائل میں الائیڈ بینک کی اے 4 پرنٹ رپورٹ، خودکار پیجینیشن (مثلاً 1 of 3, 2 of 3)، کسٹمر ایڈریس بلاک، ڈاٹڈ لائنز والی ٹرانزیکشن ٹیبل (DATE, PARTICULARS, VALUE DATE, DEBITS, CREDITS, BALANCE)، اور آخری صفحے کا سمری بلاک (TOTAL DEBIT / CREDIT, CLOSING BALANCE) کی تمام HTML اور CSS موجود ہے۔
- **فیصل بینک (Faysal Bank Limited / FBL):** `app/Services/Statement/FaysalStatementRenderer.php`
  اس فائل میں فیصل بینک کی پرنٹ رپورٹ کا پورا لے آؤٹ (HTML اور CSS) موجود ہے۔
- **بینک اسلامی پاکستان (BankIslami Pakistan Limited):** `app/Services/Statement/BankIslamiStatementRenderer.php`
  اس فائل میں بینک اسلامی کی گہرے چارکول بینر، 8 رکنی گرڈ اور سمری کاؤنٹس والی اسٹیٹمنٹ کا لے آؤٹ موجود ہے۔
- **یو بی ایل امین اسلامک بینکنگ (United Bank Limited / UBL Ameen):** `app/Services/Statement/UBLStatementRenderer.php`
  اس فائل میں یو بی ایل امین کا پورا ہیڈر بینر، کسٹمر انفارمیشن و رائٹ میٹا ڈیٹا باکس، Inst No. (چیک / ریفرنس نمبر) والا کالم، ** OPENING BALANCE ** پہلی لائن، ہر ٹرانزیکشن پر Cr بیلنس لاحقہ، دو ہفتوں کے اندر ڈسکریپنسی تصدیقی نوٹ، اور ہیڈ آفس و UAN فوٹر بار شامل ہے۔
- **اگر آپ نے لوگو کا سائز، مارجن، فونٹ سائز یا ٹیبل کالمز کی چوڑائی بدلنی ہو:** تو متعلقہ بینک کی رینڈرر فائل کو اپنی مرضی کے مطابق آسانی سے ایڈٹ کر سکتے ہیں۔

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

---

## 4. ملٹی فارمیٹ ماڈیولر آرکیٹیکچر اور حفاظت (Multi-Format Isolation Guide)

تمام بینک اسٹیٹمنٹ فارمیٹس کو 100% ماڈیولر اور الگ الگ (Decoupled) بنایا گیا ہے تاکہ مستقبل میں کوئی نیا بینک فارمیٹ شامل کرنے سے پرانے فارمیٹس پر کوئی اثر نہ پڑے:

### الف: فرنٹ اینڈ فارم رجسٹری (`views/statements/view.php`)
- فائل کے اندر `BankFormRegistry` نامی ایک کنفیگریشن آبجیکٹ موجود ہے۔
- ہر بینک فارمیٹ کی اپنی الگ الگ ترتیبات ہیں:
  1. `'allied'`: الائیڈ بینک لمیٹڈ کے فیلڈز (Account Title, Street/Area, City, Postal Code, Province, Mobile 1, Mobile 2, Branch Name, Regular Status, Start Balance)۔
  2. `'faysal'`: فیصل بینک لمیٹڈ کے فیلڈز (Title Of Account, Full Address, Deposit Type, Currency PKR, Opening Balance, IBAN)۔
  3. `'meezan'`: میزان بینک لمیٹڈ کے فیلڈز (Customer Title, Customer Address, Old Account No, Branch Code & Name, Branch Address, Product, Currency, Opening Balance, IBAN, Generated By, Print Date)۔
  4. `'hbl'`: حبیب بینک لمیٹڈ کے فیلڈز (Account Title, Address, Branch Name, Account Type, Currency, Printing Date, Frequency, User Code, Opening Balance)۔
  5. `'habibmetro'`: حبیب میٹروپولیٹن بینک لمیٹڈ کے فیلڈز (Branch Name, A/C Type, A/C Number, IBAN, Currency, Printed On, Account Title, Address)۔
  6. `'bankislami'`: بینک اسلامی پاکستان لمیٹڈ کے فیلڈز (Account Branch, Issuing Branch, Issue Date, Statement Title, Account Type, Currency, Account No, IBAN, Customer Title, Address, Opening Balance)۔
  7. `'ubl'`: یو بی ایل امین اسلامک بینکنگ کے فیلڈز (Account Number, Account Title, Address, Branch Code, Branch Name, Account Type, Product Type, Currency, IBAN No, Reg Cell No, CIF#, Opening Balance)۔
  8. `'default'`: دیگر تمام بینکس کے لیے اسٹینڈرڈ فارم۔
- **نئے فارمیٹ کا اصول**: جب بھی کسی نئے بینک کا فارمیٹ بنے گا، صرف `BankFormRegistry` میں اس بینک کی نئی انٹری آئے گی، پرانے تمام فارمیٹس (`faysal`، `allied`، `meezan`، `hbl`، `habibmetro`، `bankislami`) بالکل محفوظ رہیں گے۔

### ب: بیک اینڈ رینڈرر کلاسز (`app/Services/Statement/`)
- ہر بینک فارمیٹ کا الگ اور آزاد PHP رینڈرر موجود ہے:
  - `UBLStatementRenderer.php` (یو بی ایل امین اسلامک بینکنگ کے لیے مخصوص)
  - `BankIslamiStatementRenderer.php` (بینک اسلامی پاکستان لمیٹڈ کے لیے مخصوص)
  - `HabibMetroStatementRenderer.php` (حبیب میٹروپولیٹن بینک لمیٹڈ کے لیے مخصوص)
  - `HBLStatementRenderer.php` (حبیب بینک لمیٹڈ کے لیے مخصوص)
  - `FaysalStatementRenderer.php` (فیصل بینک کے لیے مخصوص)
  - `AlliedStatementRenderer.php` (الائیڈ بینک کے لیے مخصوص)
  - `MeezanStatementRenderer.php` (میزان بینک کے لیے مخصوص)
- یہ تمام کلاسز `StatementRendererFactory.php` میں رجسٹرڈ ہیں، اس لیے نیا بینک شامل کرنے سے پہلے سے موجود کوڈ میں کوئی تبدیلی نہیں آتی۔

---

## 5. پروڈکشن مائیگریشنز اور کسٹمائزیشنز (Production Migrations & Settings)
- **ایڈمن پاس ورڈ مائیگریشن:** `database/migration_update_admin_password_and_production_settings.php`
  - ایڈمن پاس ورڈ پروڈکشن کے لیے `Pakistan@786` سیٹ کیا گیا ہے۔
  - لاگ ان اسکرین سے ڈیفالٹ لاگ ان کریڈینشلز باکس اور پری فِلڈ ڈیٹا مکمل ختم کر دیا گیا ہے۔
  - سب ٹائٹل کو `Web Based Financial Management System` میں تبدیل کر دیا گیا ہے۔
- **اکاؤنٹ ٹائٹل اور کوڈ ویلیڈیشن ایکسپینشن:**
  - فائل: `app/Core/Validator.php`، `app/Controllers/AccountController.php`
  - مائیگریشن: `database/migration_expand_account_title_and_identifier_length.php`
  - اب کسٹمر یا صارف اکاؤنٹ کوڈ / نمبر 100 سے زیادہ (مثلاً 101، 105، 1000 وغیرہ) بغیر کسی رکاوٹ یا "Validation error: Account Title may not be greater than 100" ایرر کے آسانی سے بنا سکتا ہے۔ کالم کی گنجائش کو بھی `VARCHAR(255)` تک وسیع کر دیا گیا ہے۔
- **حبیب میٹروپولیٹن بینک کے مخصوص صفحات اور روٹس:**
  - وقف شدہ فارم: `views/statements/habibmetro.php`
  - روٹس: `/statements/habibmetro`، `/habibmetro`، `/hmb`
  - حقیقی بینک اسٹیٹمنٹ کے مطابق اصلی ویکٹر QR کوڈ، میٹا ڈیٹا باکس، اوپننگ بیلنس قطار، اور تاریخ وار آخری ٹرانزیکشن پر اینڈ-آف-ڈیٹ بیلنس کالم شامل ہے۔
- **بینک اسلامی پاکستان لمیٹڈ کے مخصوص صفحات، فارمیٹس اور روٹس:**
  - وقف شدہ فارم: `views/statements/bankislami.php`
  - مرکزی اسٹیٹمنٹ فارم (`views/statements/view.php`) میں آٹو اڈاپٹو ڈائنامک فیلڈز
  - روٹس: `/statements/bankislami`، `/bankislami`، `/bipl`، `/islami`
  - کسٹمر نمونہ پی ڈی ایف کے مطابق گہرا چارکول بینر، اصلی "BankIslami" لوگو، ڈاکومنٹ ٹائٹل ("Duplicate Statement Of Account")، جاری کنندہ برانچ ("Issuing Branch : KUCHLAK")، تاریخِ اجراء ("Issue Date : 29-Sep-2026")۔
  - بائیں جانب کسٹمر انفارمیشن باکس بمعہ پتہ اور فون نمبرز، دائیں جانب 8 رکنی کلیدی میٹا ڈیٹا گرڈ۔
  - کالمز: Date, Description, Withdrawal, Deposit, Balance.
  - آخری صفحے کے اختتام پر 2 کالم کا سمری گرڈ:
    - DEBIT TRANSACTIONS COUNT اور TOTAL DEBIT AMOUNT
    - CREDIT TRANSACTIONS COUNT اور TOTAL CREDIT AMOUNT
    - CLOSING BALANCE AS ON [تاریخ] اور AVAILABLE BALANCE AS ON [تاریخ]
  - صارف کے واضح اصول کے تحت ڈیجیٹل مہر یا دستخط کو مکمل خارج رکھا گیا ہے تاکہ کسٹمر دستی مہر اور دستخط لگا سکے۔
- **یو بی ایل امین اسلامک بینکنگ کے مخصوص صفحات، فارمیٹس اور روٹس:**
  - وقف شدہ فارم: `views/statements/ubl.php`
  - مرکزی اسٹیٹمنٹ فارم (`views/statements/view.php`) میں آٹو اڈاپٹو ڈائنامک فیلڈز (Branch Code, Product Type, Reg Cell No, CIF#)
  - روٹس: `/statements/ubl`، `/ubl`، `/ublameen`، `/ameen`
  - مائیگریشن: `database/migration_activate_ubl_statement_format.sql` اور `database/migration_activate_ubl_statement_format.php`
  - کسٹمر نمونہ پی ڈی ایف کے مطابق مکمل چوڑائی والا مستند `ubl_ameen_banner.png` ہیڈر بینر۔
  - صفحہ 1 کا بائیں جانب کسٹمر بلاک (`FARHAN KHAN`، ملٹی لائن پتہ، `Reg Cell No: 009203089470136`، `IBAN No: ...`، `CIF#: 47187224`)۔
  - صفحہ 1 کا دائیں جانب 1px بارڈر والا میٹا ڈیٹا باکس (`Statement Period`، `Account No`، `Account Type`، `Product Type`، `Currency`، `Balance: ... Cr`، `As of : ...`) بمعہ بولڈ ٹائٹل `Account Statement` اور `0053-000344327894`۔
  - ٹرانزیکشن ٹیبل: `#e9e9e9` ہیڈر قطار، کالمز (`Date`, `Particulars`, `Inst No.`, `Debit`, `Credit`, `Balance`)۔
  - پہلی لائن مستند `** OPENING BALANCE **` قطار، رننگ بیلنس کے ساتھ `Cr` لاحقہ، ورٹیکل کالم ڈیوائیڈرز۔
  - فوٹر: `Page # X / Y`، 2 ہفتوں کا ڈسکریپنسی تصدیقی نوٹ، ہیڈ آفس پتہ اور UAN/ویب سائٹ بار۔
  - صارف کے واضح اصول کے تحت ڈیجیٹل مہر یا دستخط کو مکمل خارج رکھا گیا ہے تاکہ کسٹمر دستی مہر اور دستخط لگا سکے۔
- **فوٹر برانڈنگ اور روابط:**
  - `views/layouts/footer.php`:
    `Copyright (C) 2026 All rights reserved. Designed & Developed by Faiz Muhammad, Powered by RISE PK - Software Development and Consulting.`
  - Faiz Muhammad پر کلک کرنے سے براہ راست واٹس ایپ (`+923469081669`) پر کنسلٹنگ و سافٹ ویئر ڈویلپمنٹ میسج اوپن ہوتا ہے۔
  - RISE PK کا لنک `https://risepk.com` فراہم کیا گیا ہے۔
- **HBL پیج کیپسٹی ٹیوننگ:**
  - `HBLStatementRenderer::$linesPerPage` کو بڑھا کر 51 لائنز (+7 لائنز) پر سیٹ کیا گیا ہے تاکہ A4 پیج کی فل یوٹیلائزیشن ہو اور نیچے خالی جگہ کا بہترین استعمال ہو۔


