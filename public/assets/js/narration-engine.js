// public/assets/js/narration-engine.js
// Safe DOM-constructed Real-Time Template Narration Engine

var NarrationEngine = (function() {
    var currentTemplate = null;
    var isManualOverride = false;
    var masterBanks = [];

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var pakistaniFirstNames = [
        'Muhammad', 'Ahmad', 'Ali', 'Usman', 'Tariq', 'Irfan', 'Abdul Rehman', 'Bilal', 
        'Hamza', 'Zeeshan', 'Kamran', 'Imran', 'Faisal', 'Asad', 'Kashif', 'Farhan', 
        'Rizwan', 'Shahzad', 'Naveed', 'Rashid', 'Waqas', 'Abdul Wahab', 'Sajid', 
        'Shoaib', 'Zubair', 'Babar', 'Adnan', 'Junaid', 'Haris', 'Umair', 'Shahid', 
        'Nasir', 'Arslan', 'Zahid', 'Noman', 'Adeel', 'Khurram', 'Saqib', 'Atif', 
        'Faheem', 'Tanveer', 'Waseem', 'Shakeel', 'Aamir', 'Akhtar', 'Javed', 'Khalid'
    ];

    var pakistaniLastNames = [
        'Khan', 'Ahmed', 'Ali', 'Mehmood', 'Ullah', 'Shah', 'Malik', 'Butt', 
        'Chaudhry', 'Siddiqui', 'Qureshi', 'Abbasi', 'Mughal', 'Ansari', 'Iqbal', 
        'Hussain', 'Akram', 'Rehman', 'Afzal', 'Bhatti', 'Raza', 'Sharif', 'Javed', 
        'Niazi', 'Bajwa', 'Cheema', 'Gondal', 'Warraich', 'Tariq', 'Rasheed', 'Dar',
        'Mirza', 'Gillani', 'Bukhari', 'Kazmi'
    ];

    function generatePakistaniName() {
        var first = pakistaniFirstNames[Math.floor(Math.random() * pakistaniFirstNames.length)];
        var last = pakistaniLastNames[Math.floor(Math.random() * pakistaniLastNames.length)];
        if (first === last) {
            last = 'Khan';
        }
        return first + ' ' + last;
    }

    function generateSTAN(rule) {
        var len = (rule && rule.length) ? rule.length : 6;
        var min = Math.pow(10, len - 1);
        var max = Math.pow(10, len) - 1;
        return String(Math.floor(min + Math.random() * (max - min + 1)));
    }

    function generateRaastRef(rule) {
        var prefix = (rule && rule.prefix) ? rule.prefix : 'BAHLPKKA';
        var now = new Date();
        var y = String(now.getFullYear()).slice(-2);
        var m = String(now.getMonth() + 1).padStart(2, '0');
        var d = String(now.getDate()).padStart(2, '0');
        var rand = String(Math.floor(10000000 + Math.random() * 90000000));
        return prefix + y + m + d + rand;
    }

    function generateTransId(rule) {
        var prefix = (rule && rule.prefix) ? rule.prefix : 'FT26';
        var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        var rand = '';
        for (var i = 0; i < 12; i++) {
            rand += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return prefix + rand;
    }

    function generateAlphanumeric(rule) {
        var prefix = (rule && rule.prefix) ? rule.prefix : '';
        var suffix = (rule && rule.suffix) ? rule.suffix : '';
        var len = (rule && rule.length) ? rule.length : 10;
        var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        var rand = '';
        for (var i = 0; i < len; i++) {
            rand += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return prefix + rand + suffix;
    }

    function generateATMCode(existingOrRule) {
        var cities = ['KHI', 'LHE', 'ISB', 'RWP', 'FSD', 'MUL', 'PEW'];
        var city = cities[Math.floor(Math.random() * cities.length)];
        var existing = (typeof existingOrRule === 'string') ? existingOrRule : (existingOrRule ? (existingOrRule.default_value || existingOrRule.prefix || '') : '');
        if (existing) {
            var parts = existing.split('-');
            if (parts.length >= 3 && isNaN(parts[parts.length - 1])) {
                city = parts[parts.length - 1].toUpperCase();
            }
        }
        var num = String(Math.floor(10 + Math.random() * 990)).padStart(3, '0');
        return 'ATM-' + num + '-' + city;
    }

    function generateRRN(rule) {
        var len = (rule && rule.length) ? rule.length : 12;
        var num = '';
        for (var i = 0; i < len; i++) {
            num += Math.floor(Math.random() * 10);
        }
        return num;
    }

    var pakistaniBankCodes = ['HABB', 'BAHL', 'MEZN', 'MCBL', 'UNIL', 'ALFH', 'MPBL', 'FAYS', 'SONE', 'JSBL', 'BIPL'];

    function generateAccountNumber(rule) {
        rule = rule || {};
        var branch = String(Math.floor(100 + Math.random() * 900)).padStart(4, '0');
        var acc = String(Math.floor(10000000 + Math.random() * 90000000));
        var suffix = '01';
        if (rule.format === 'unformatted' || rule.format === 'digits' || rule.hyphen === false) {
            return branch + acc + suffix;
        }
        return branch + '-' + acc + '-' + suffix;
    }

    function generateMaskedAccountNumber(rule) {
        rule = rule || {};
        var branch = String(Math.floor(100 + Math.random() * 900)).padStart(4, '0');
        var last4 = String(Math.floor(1000 + Math.random() * 9000));
        var mask = (rule.mask_char === '*') ? '****' : 'XXXX';
        if (rule.format === 'unformatted' || rule.hyphen === false) {
            return branch + mask + last4 + '01';
        }
        return branch + '-' + mask + last4 + '-01';
    }

    function generateIBAN(rule) {
        rule = rule || {};
        var bankCode = rule.bank_code;
        if (!bankCode) {
            bankCode = pakistaniBankCodes[Math.floor(Math.random() * pakistaniBankCodes.length)];
        }
        bankCode = bankCode.toUpperCase();
        if (bankCode.length < 4) bankCode = (bankCode + 'XXXX').substring(0, 4);

        var check = String(Math.floor(10 + Math.random() * 89));
        var accountDigits = '0000' + String(Math.floor(100000000000 + Math.random() * 900000000000));
        accountDigits = accountDigits.substring(0, 16);
        return 'PK' + check + bankCode + accountDigits;
    }

    function generateMaskedIBAN(rule) {
        rule = rule || {};
        var bankCode = rule.bank_code;
        if (!bankCode) {
            bankCode = pakistaniBankCodes[Math.floor(Math.random() * pakistaniBankCodes.length)];
        }
        bankCode = bankCode.toUpperCase();
        if (bankCode.length < 4) bankCode = (bankCode + 'XXXX').substring(0, 4);

        var check = String(Math.floor(10 + Math.random() * 89));
        var last4 = String(Math.floor(1000 + Math.random() * 9000));
        var mask = (rule.mask_char === '*') ? '************' : 'XXXXXXXXXXXX';
        return 'PK' + check + bankCode + mask + last4;
    }

    function generateAmount(rule) {
        rule = rule || {};
        var amounts = [
            2500, 5000, 7500, 10000, 12500, 15000, 20000, 25000, 30000, 
            35000, 40000, 50000, 65000, 75000, 80000, 95000, 100000, 125000, 150000
        ];
        var amt = amounts[Math.floor(Math.random() * amounts.length)];
        if (rule.min && rule.max) {
            amt = Math.floor(rule.min + Math.random() * (rule.max - rule.min));
        }
        if (rule.raw || rule.format === 'number') {
            return String(amt);
        }
        return amt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function generateNumber(rule) {
        rule = rule || {};
        var len = rule.length || 6;
        var num = '';
        for (var i = 0; i < len; i++) {
            num += Math.floor(Math.random() * 10);
        }
        return (rule.prefix || '') + num + (rule.suffix || '');
    }

    function generateCardNumber(rule) {
        rule = rule || {};
        var bins = ['5120', '4214', '5241', '4508', '4000', '5399'];
        var bin = bins[Math.floor(Math.random() * bins.length)];
        var p2 = String(Math.floor(1000 + Math.random() * 9000));
        var p3 = String(Math.floor(1000 + Math.random() * 9000));
        var p4 = String(Math.floor(1000 + Math.random() * 9000));
        if (rule.format === 'raw' || rule.hyphen === false) {
            return bin + p2 + p3 + p4;
        }
        return bin + '-' + p2 + '-' + p3 + '-' + p4;
    }

    function generateMaskedCardNumber(rule) {
        rule = rule || {};
        var bins = ['5120', '4214', '5241', '4508', '4000', '5399'];
        var bin = bins[Math.floor(Math.random() * bins.length)];
        var last4 = String(Math.floor(1000 + Math.random() * 9000));
        var mask = (rule.mask_char === '*') ? '****-****' : 'XXXX-XXXX';
        return bin + '-' + mask + '-' + last4;
    }

    function maskAccountNumber(accNo) {
        if (!accNo) return generateMaskedAccountNumber();
        var str = String(accNo).trim();
        var parts = str.split('-');
        if (parts.length === 3) {
            var middle = parts[1];
            var last4 = middle.length >= 4 ? middle.slice(-4) : middle;
            return parts[0] + '-XXXX' + last4 + '-' + parts[2];
        }
        if (str.length >= 10) {
            return str.slice(0, 4) + '-XXXX' + str.slice(-4) + '-01';
        }
        return generateMaskedAccountNumber();
    }

    function maskIBAN(iban) {
        if (!iban) return generateMaskedIBAN();
        var str = String(iban).trim().toUpperCase();
        if (str.length >= 20) {
            return str.slice(0, 8) + 'XXXXXXXXXXXX' + str.slice(-4);
        }
        return generateMaskedIBAN();
    }

    function generateBankName(rule) {
        if (masterBanks && masterBanks.length > 0) {
            var b = masterBanks[Math.floor(Math.random() * masterBanks.length)];
            return b.bank_name || 'Habib Bank Limited';
        }
        var sampleBanks = [
            'Habib Bank Limited', 'Meezan Bank Ltd', 'Bank AL Habib Limited', 
            'MCB Bank Limited', 'United Bank Limited', 'Bank Alfalah Limited', 
            'Habib Metropolitan Bank', 'Faysal Bank Limited', 'Standard Chartered Bank'
        ];
        return sampleBanks[Math.floor(Math.random() * sampleBanks.length)];
    }

    var customTypesMap = {};

    function loadCustomTypes(typesList) {
        customTypesMap = {};
        if (Array.isArray(typesList)) {
            typesList.forEach(function(ct) {
                if (ct && ct.type_code) {
                    customTypesMap[ct.type_code.toLowerCase()] = ct;
                }
            });
        }
    }

    function getCustomTypes() {
        return customTypesMap;
    }

    var READY_MADE_PATTERNS = {
        'pakistani_name': '[Pakistani Name]',
        'account': '0417-########-01',
        'masked_account': '0417-XXXX####-01',
        'iban': 'PK##HABB################',
        'masked_iban': 'PK##HABBXXXXXXXXXXXX####',
        'card_number': '5120-####-####-####',
        'masked_card': '5120-XXXX-XXXX-####',
        'stan': '######',
        'transaction_id': 'FT26@@######',
        'reference_no': 'BAHLPKKA########',
        'atm_code': 'ATM-###-@@@',
        'number': '######',
        'amount': '##,###.00',
        'custom_pattern': ''
    };

    function getReadyMadePatterns() {
        return Object.assign({}, READY_MADE_PATTERNS);
    }

    function parseAndGeneratePattern(pattern, context) {
        if (!pattern || typeof pattern !== 'string') return '';
        var str = pattern;
        var dateObj = (context && context.date) ? new Date(context.date) : new Date();
        if (isNaN(dateObj.getTime())) dateObj = new Date();

        var yy = String(dateObj.getFullYear()).slice(-2);
        var yyyy = String(dateObj.getFullYear());
        var mm = String(dateObj.getMonth() + 1).padStart(2, '0');
        var dd = String(dateObj.getDate()).padStart(2, '0');

        // 1. Macro and date token replacements
        str = str.replace(/\[DATE\]|\[YYMMDD\]/gi, yy + mm + dd);
        str = str.replace(/\[YYYYMMDD\]/gi, yyyy + mm + dd);
        str = str.replace(/\[DDMMYY\]/gi, dd + mm + yy);
        str = str.replace(/\[DD-MM-YYYY\]/gi, dd + '-' + mm + '-' + yyyy);
        str = str.replace(/\[YYYY\]/gi, yyyy);
        str = str.replace(/\[YY\]/gi, yy);
        str = str.replace(/\[MM\]/gi, mm);
        str = str.replace(/\[DD\]/gi, dd);

        str = str.replace(/\[PAKISTANI[_\s]NAME\]|\[NAME\]/gi, function() {
            return generatePakistaniName();
        });

        str = str.replace(/\[BANK\]|\[BANK[_\s]NAME\]/gi, function() {
            return (context && context.bank_name) ? context.bank_name : generateBankName();
        });

        str = str.replace(/\[STAN\]/gi, function() {
            return generateSTAN({ length: 6 });
        });

        str = str.replace(/\[ACCOUNT\]|\[ACC\]|\[ACCOUNT[_\s]NO\]|\[ACCOUNT[_\s]NUMBER\]/gi, function() {
            return (context && context.account_number) ? context.account_number : generateAccountNumber();
        });

        str = str.replace(/\[IBAN\]/gi, function() {
            return (context && context.iban) ? context.iban : generateIBAN();
        });

        str = str.replace(/\[CARD\]|\[CARD[_\s]NUMBER\]|\[CARD[_\s]NO\]/gi, function() {
            return generateCardNumber();
        });

        // 2. Character-by-character mask evaluation:
        // # or 9 => random digit (0-9)
        // @ => random uppercase letter (A-Z)
        // a => random lowercase letter (a-z)
        // All others (literals like FT, BAHL, hyphens, prefixes, masks X and *) remain literal constants!
        var result = '';
        var inEscape = false;
        for (var i = 0; i < str.length; i++) {
            var ch = str[i];
            if (ch === '\\' && !inEscape) {
                inEscape = true;
                continue;
            }
            if (inEscape) {
                result += ch;
                inEscape = false;
                continue;
            }

            if (ch === '#') {
                result += Math.floor(Math.random() * 10);
            } else if (ch === '@') {
                var alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                result += alpha[Math.floor(Math.random() * alpha.length)];
            } else if (ch === 'a') {
                var lowerAlpha = 'abcdefghijklmnopqrstuvwxyz';
                result += lowerAlpha[Math.floor(Math.random() * lowerAlpha.length)];
            } else {
                result += ch;
            }
        }
        return result;
    }

    function generateValueForField(field, context) {
        var type = (field.field_type || '').toLowerCase();
        var key = (field.field_key || '').toLowerCase();
        var label = (field.field_label || '').toLowerCase();
        var rule = field.generator_rule;
        if (typeof rule === 'string') {
            try { rule = JSON.parse(rule); } catch(e) { rule = {}; }
        }
        rule = rule || {};
        var ruleType = (rule.type || '').toLowerCase();

        // 0. User-defined pattern and custom types support:
        var customPattern = (rule.pattern || field.options_source || field.placeholder || '').trim();

        // Case A: Field type matches registered custom type in customTypesMap
        if (customTypesMap[type]) {
            var patToUse = customPattern || customTypesMap[type].pattern;
            return parseAndGeneratePattern(patToUse, context);
        }

        // Case B: Explicit Custom Pattern / Mask type
        if (type === 'custom_pattern' || type === 'custom') {
            if (customPattern) {
                return parseAndGeneratePattern(customPattern, context);
            }
            return generateAlphanumeric(rule);
        }

        // Case C: Ready-made type with user-modified custom pattern
        var defaultReadyMadePat = READY_MADE_PATTERNS[type];
        var isCustomized = customPattern && 
                           customPattern !== defaultReadyMadePat && 
                           (customPattern.indexOf('#') !== -1 || customPattern.indexOf('@') !== -1 || customPattern.indexOf('[') !== -1);
        if (isCustomized) {
            return parseAndGeneratePattern(customPattern, context);
        }

        // 1. Pakistani Name
        if (type === 'pakistani_name' || 
            key.indexOf('receiver_name') !== -1 || key.indexOf('beneficiary_name') !== -1 || 
            key.indexOf('sender_name') !== -1 || key === 'sender' || key === 'receiver' || 
            key === 'beneficiary' || key.indexOf('full_name') !== -1 ||
            (label.indexOf('name') !== -1 && label.indexOf('bank') === -1)) {
            return generatePakistaniName();
        }

        // 2. Masked IBAN
        if (type === 'masked_iban' || 
            (key.indexOf('iban') !== -1 && (key.indexOf('mask') !== -1 || key.indexOf('hidden') !== -1 || key.indexOf('xxxx') !== -1)) ||
            (label.indexOf('iban') !== -1 && (label.indexOf('mask') !== -1 || label.indexOf('hidden') !== -1))) {
            return generateMaskedIBAN(rule);
        }

        // 3. IBAN
        if (type === 'iban' || key.indexOf('iban') !== -1 || label.indexOf('iban') !== -1) {
            return generateIBAN(rule);
        }

        // 4. Masked Account Number
        if (type === 'masked_account' ||
            ((key.indexOf('account') !== -1 || key.indexOf('acc') !== -1 || key.endsWith('ac')) && (key.indexOf('mask') !== -1 || key.indexOf('hidden') !== -1 || key.indexOf('xxxx') !== -1)) ||
            (label.indexOf('account') !== -1 && (label.indexOf('mask') !== -1 || label.indexOf('hidden') !== -1))) {
            return generateMaskedAccountNumber(rule);
        }

        // 5. Account Number / Account No (Matches 'account', 'acc', 'ac', 'SenderAc', 'ReceiverAc', etc.)
        if (type === 'account' || 
            key.indexOf('account') !== -1 || key.indexOf('acc_no') !== -1 || key.indexOf('ac_no') !== -1 || 
            key.endsWith('ac') || key.indexOf('acc') !== -1 || key.indexOf('acct') !== -1 ||
            label.indexOf('account') !== -1 || label.indexOf('a/c') !== -1) {
            if (key.indexOf('iban') !== -1 || label.indexOf('iban') !== -1) {
                return (key.indexOf('mask') !== -1 || label.indexOf('mask') !== -1) ? generateMaskedIBAN(rule) : generateIBAN(rule);
            }
            if (key.indexOf('mask') !== -1 || label.indexOf('mask') !== -1) {
                return generateMaskedAccountNumber(rule);
            }
            return generateAccountNumber(rule);
        }

        // 6. Masked Card Number
        if (type === 'masked_card' ||
            (key.indexOf('card') !== -1 && (key.indexOf('mask') !== -1 || key.indexOf('hidden') !== -1 || key.indexOf('xxxx') !== -1)) ||
            (label.indexOf('card') !== -1 && (label.indexOf('mask') !== -1 || label.indexOf('hidden') !== -1))) {
            return generateMaskedCardNumber(rule);
        }

        // 7. Card Number (Unmasked)
        if (type === 'card_number' || key.indexOf('card') !== -1 || label.indexOf('card') !== -1) {
            return generateCardNumber(rule);
        }

        // 8. STAN (6-digit)
        if (type === 'stan' || ruleType === 'stan' || key === 'stan' || key.indexOf('stan') !== -1 || key.indexOf('trace') !== -1 || label.indexOf('stan') !== -1) {
            return generateSTAN(rule);
        }

        // 9. Transaction ID / Trans ID
        if (type === 'transaction_id' || ruleType === 'transaction_id' || key.indexOf('trans_id') !== -1 || key.indexOf('transaction_id') !== -1 || key.indexOf('txid') !== -1 || key.indexOf('ft_id') !== -1 || label.indexOf('trans') !== -1) {
            return generateTransId(rule);
        }

        // 10. Reference No / Raast Ref / RRN
        if (type === 'reference_no' || ruleType === 'reference_no' || ruleType === 'bank_ref' || key.indexOf('reference') !== -1 || key.indexOf('ref_no') !== -1 || key.indexOf('bank_ref') !== -1 || key.indexOf('raast_ref') !== -1 || label.indexOf('reference') !== -1) {
            return generateRaastRef(rule);
        }

        // 11. RRN
        if (type === 'rrn' || ruleType === 'rrn' || key.indexOf('rrn') !== -1 || label.indexOf('rrn') !== -1) {
            return generateRRN(rule);
        }

        // 12. Amount
        if (type === 'amount' || ruleType === 'amount' || key.indexOf('amount') !== -1 || key.indexOf('amt') !== -1 || label.indexOf('amount') !== -1) {
            return generateAmount(rule);
        }

        // 13. Bank Name / Dropdown
        if (type === 'bank' || key.indexOf('bank') !== -1 || label.indexOf('bank') !== -1) {
            return generateBankName(rule);
        }

        // 14. ATM Code
        if (type === 'atm_code' || type === 'atm' || ruleType === 'atm' || ruleType === 'atm_code' || key.indexOf('atm') !== -1 || key.indexOf('terminal') !== -1 || label.indexOf('atm') !== -1) {
            return generateATMCode(field.default_value || rule);
        }

        // 15. Number / Numeric
        if (type === 'number' || ruleType === 'numeric' || key.indexOf('num') !== -1 || key.indexOf('count') !== -1 || key.indexOf('code') !== -1 || key.indexOf('qty') !== -1 || label.indexOf('number') !== -1) {
            return generateNumber(rule);
        }

        // 16. Invoice / Bill
        if (key.indexOf('invoice') !== -1 || key.indexOf('bill') !== -1) {
            return 'INV-' + Math.floor(100000 + Math.random() * 900000);
        }

        // 17. Branch
        if (key.indexOf('branch') !== -1) {
            return 'BR-' + String(Math.floor(10 + Math.random() * 990)).padStart(4, '0');
        }

        // Fallback rule types
        if (ruleType === 'alphanumeric' || ruleType === 'random') {
            return generateAlphanumeric(rule);
        }

        if (field.default_value) {
            return field.default_value;
        }

        return generateAlphanumeric(rule);
    }

    function renderDynamicFields(template, targetContainerId, presetValues) {
        currentTemplate = template;
        isManualOverride = false;
        var $container = $('#' + targetContainerId);
        $container.empty();

        if (!template || !template.fields || template.fields.length === 0) {
            var $p = $('<p>').addClass('text-sm text-gray-500 italic').text('No dynamic fields required for this template.');
            $container.append($p);
            updateNarrationOutput();
            return;
        }

        var $grid = $('<div>').addClass('grid grid-cols-1 md:grid-cols-2 gap-4');

        template.fields.forEach(function(field) {
            var $fieldWrapper = $('<div>').addClass('flex flex-col space-y-1');
            var $labelRow = $('<div>').addClass('flex items-center justify-between');

            var $label = $('<label>').addClass('text-xs font-semibold text-gray-700 tracking-wide').text(field.field_label);
            if (field.is_required) {
                $label.append($('<span>').addClass('text-red-500 ml-0.5').text('*'));
            }
            $labelRow.append($label);

            var initialVal = (presetValues && presetValues[field.field_key] !== undefined) 
                ? presetValues[field.field_key] 
                : (field.default_value || '');

            var hasGenerator = (field.generator_rule && Object.keys(field.generator_rule).length > 0) || 
                               Boolean(customTypesMap[field.field_type]) ||
                               field.field_type === 'custom_pattern' ||
                               field.field_type === 'custom' ||
                               ['pakistani_name', 'stan', 'transaction_id', 'reference_no', 'account', 'masked_account', 'iban', 'masked_iban', 'amount', 'number', 'card_number', 'masked_card', 'atm_code', 'rrn'].includes(field.field_type) ||
                               Boolean(field.is_regeneratable);

            if (hasGenerator && !initialVal) {
                initialVal = generateValueForField(field);
            }

            var $inputEl;

            if (field.field_type === 'bank') {
                $inputEl = $('<select>')
                    .attr('data-key', field.field_key)
                    .addClass('dynamic-field-input searchable-select w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white');

                $inputEl.append($('<option>').attr('value', '').text('-- Select Bank --'));
                masterBanks.forEach(function(b) {
                    var $opt = $('<option>')
                        .attr('value', b.bank_name)
                        .text(b.bank_name + ' (' + b.bank_code + ')');
                    if (initialVal === b.bank_name || initialVal === b.bank_code) {
                        $opt.prop('selected', true);
                    }
                    $inputEl.append($opt);
                });
            } else if (field.field_type === 'dropdown' && field.options_source) {
                $inputEl = $('<select>')
                    .attr('data-key', field.field_key)
                    .addClass('dynamic-field-input w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white');

                var options = field.options_source.split(',');
                options.forEach(function(opt) {
                    opt = opt.trim();
                    var $opt = $('<option>').attr('value', opt).text(opt);
                    if (initialVal === opt) {
                        $opt.prop('selected', true);
                    }
                    $inputEl.append($opt);
                });
            } else if (field.field_type === 'textarea') {
                $inputEl = $('<textarea>')
                    .attr('data-key', field.field_key)
                    .attr('rows', 2)
                    .attr('placeholder', field.placeholder || '')
                    .addClass('dynamic-field-input w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition')
                    .val(initialVal);
            } else {
                var $inputGroup = $('<div>').addClass('relative flex rounded-md shadow-sm');
                $inputEl = $('<input>')
                    .attr('type', 'text')
                    .attr('data-key', field.field_key)
                    .attr('placeholder', field.placeholder || '')
                    .addClass('dynamic-field-input flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition')
                    .val(initialVal);

                if (hasGenerator) {
                    var $refreshBtn = $('<button>')
                        .attr('type', 'button')
                        .attr('title', 'Regenerate value')
                        .addClass('refresh-gen-btn ml-1 px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 transition flex items-center justify-center')
                        .html('<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>');

                    (function(f, inp) {
                        $refreshBtn.on('click', function() {
                            var newVal = generateValueForField(f);
                            inp.val(newVal).trigger('input');
                        });
                    })(field, $inputEl);

                    $inputGroup.append($inputEl).append($refreshBtn);
                    $fieldWrapper.append($labelRow).append($inputGroup);
                    $grid.append($fieldWrapper);
                    return;
                }
            }

            $fieldWrapper.append($labelRow).append($inputEl);
            $grid.append($fieldWrapper);
        });

        $container.append($grid);

        // Bind input and select2 events for live preview
        $container.find('.dynamic-field-input').on('input change select2:select select2:clear', function() {
            updateNarrationOutput();
        });

        // Initialize any searchable selects inside dynamic fields
        if (window.initSearchableSelects) {
            window.initSearchableSelects($container);
        }

        updateNarrationOutput();
    }

    function getFormPayload() {
        var payload = {};
        $('.dynamic-field-input').each(function() {
            var key = $(this).data('key');
            if (key) {
                payload[key] = $(this).val();
            }
        });
        return payload;
    }

    function compile(pattern, values) {
        var res = pattern;
        for (var k in values) {
            var token = '{' + k + '}';
            res = res.split(token).join(values[k] || '');
        }
        return res.replace(/\s+/g, ' ').trim();
    }

    function updateNarrationOutput() {
        if (!currentTemplate) return;

        var values = getFormPayload();
        var compiled = compile(currentTemplate.pattern, values);

        $('#narration-preview-text').text(compiled || '(Narration will appear here...)');

        if (!isManualOverride) {
            $('#final-narration-textarea').val(compiled);
        }
    }

    function setManualOverride(flag) {
        isManualOverride = flag;
        if (isManualOverride) {
            $('#manual-override-badge').removeClass('hidden');
        } else {
            $('#manual-override-badge').addClass('hidden');
        }
    }

    function loadMasterBanks(banks) {
        masterBanks = banks || [];
    }

    function getMasterBanks() {
        return masterBanks;
    }

    function autoPopulateFromTemplate(template, account) {
        if (!template) return { compiled: '', narration: '', payload: {} };
        var payload = {};
        var fields = template.fields || [];
        var pattern = template.pattern || '';

        // 1. Map fields defined in template
        fields.forEach(function(field) {
            var key = field.field_key;
            var type = (field.field_type || '').toLowerCase();
            var lKey = (key || '').toLowerCase();
            var lLabel = (field.field_label || '').toLowerCase();
            var val = field.default_value || '';

            var isRegen = (field.is_regeneratable === 1 || field.is_regeneratable === true || field.is_regeneratable === '1');
            var isReceiver = (lKey.indexOf('receiver') !== -1 || lKey.indexOf('beneficiary') !== -1);
            var isMasked = (type.indexOf('masked') !== -1 || lKey.indexOf('mask') !== -1 || lLabel.indexOf('mask') !== -1 || lKey.indexOf('xxxx') !== -1);
            var isIBAN = (type === 'iban' || type === 'masked_iban' || lKey.indexOf('iban') !== -1 || lLabel.indexOf('iban') !== -1);
            var isAccount = (type === 'account' || type === 'masked_account' || lKey.indexOf('account') !== -1 || lKey.indexOf('acc') !== -1 || lKey.endsWith('ac'));
            var isCard = (type === 'card_number' || type === 'masked_card' || lKey.indexOf('card') !== -1 || lLabel.indexOf('card') !== -1);

            if (customTypesMap[type] || type === 'custom_pattern' || type === 'custom') {
                val = generateValueForField(field, account);
            } else if (isRegen) {
                val = generateValueForField(field, account);
            } else if (isIBAN) {
                if (isMasked) {
                    val = (account && account.iban && !isReceiver) ? maskIBAN(account.iban) : generateMaskedIBAN();
                } else if (isReceiver) {
                    val = generateIBAN();
                } else {
                    val = (account && account.iban) ? account.iban : generateIBAN();
                }
            } else if (isAccount) {
                if (isMasked) {
                    val = (account && account.account_number && !isReceiver) ? maskAccountNumber(account.account_number) : generateMaskedAccountNumber();
                } else if (isReceiver) {
                    val = generateAccountNumber();
                } else {
                    val = (account && account.account_number) ? account.account_number : (account ? (account.account_identifier || generateAccountNumber()) : generateAccountNumber());
                }
            } else if (isCard) {
                val = isMasked ? generateMaskedCardNumber() : generateCardNumber();
            } else if (lKey.indexOf('receiver_bank') !== -1 || lKey.indexOf('beneficiary_bank') !== -1) {
                val = (account && account.bank_name && account.bank_name.indexOf('Meezan') !== -1) 
                    ? 'Habib Bank Limited' 
                    : (masterBanks.length > 1 ? masterBanks[1].bank_name : 'Meezan Bank Ltd');
            } else if (lKey.indexOf('bank') !== -1 || type === 'bank') {
                val = (account && account.bank_name) ? account.bank_name : (masterBanks.length > 0 ? masterBanks[0].bank_name : 'Habib Bank Limited');
            } else if (type === 'pakistani_name' || lKey.indexOf('receiver_name') !== -1 || lKey.indexOf('beneficiary_name') !== -1 || lKey.indexOf('sender_name') !== -1 || lKey === 'sender' || lKey === 'receiver' || lKey === 'beneficiary') {
                val = generatePakistaniName();
            } else if (lKey.indexOf('title') !== -1 || lKey.indexOf('name') !== -1) {
                val = (account && account.account_title) ? account.account_title : generatePakistaniName();
            } else if (type === 'amount' || lKey.indexOf('amount') !== -1 || lKey.indexOf('amt') !== -1) {
                val = generateAmount();
            } else if (type === 'number' || lKey.indexOf('num') !== -1) {
                val = generateNumber();
            } else if (lKey.indexOf('code') !== -1) {
                val = field.default_value || 'PK';
            } else if (lKey.indexOf('invoice') !== -1 || lKey.indexOf('bill') !== -1) {
                val = 'INV-' + Math.floor(100000 + Math.random() * 900000);
            } else {
                val = generateValueForField(field);
            }

            payload[key] = val;
        });

        // 2. Scan pattern for any tokens not covered in fields or left blank
        var tokens = pattern.match(/\{([a-zA-Z0-9_-]+)\}/g) || [];
        tokens.forEach(function(tok) {
            var k = tok.slice(1, -1);
            if (payload[k] === undefined || payload[k] === '') {
                var lKey = k.toLowerCase();
                if (['stan', 'trace'].some(function(x) { return lKey.indexOf(x) !== -1; })) {
                    payload[k] = generateSTAN({ length: 6 });
                } else if (['trans_id', 'transaction_id', 'txid', 'ft_id'].some(function(x) { return lKey.indexOf(x) !== -1; })) {
                    payload[k] = generateTransId({ prefix: 'FT26' });
                } else if (['ref', 'reference_no', 'bank_ref', 'raast_ref'].some(function(x) { return lKey.indexOf(x) !== -1; })) {
                    payload[k] = generateRaastRef({ prefix: 'BAHLPKKA' });
                } else if (['atm', 'atm_code', 'atm_id', 'terminal'].some(function(x) { return lKey.indexOf(x) !== -1; })) {
                    payload[k] = generateATMCode('ATM-042-KHI');
                } else if (['rrn'].some(function(x) { return lKey.indexOf(x) !== -1; })) {
                    payload[k] = generateRRN({ length: 12 });
                } else if (lKey.indexOf('receiver_bank') !== -1 || lKey.indexOf('beneficiary_bank') !== -1) {
                    payload[k] = 'Meezan Bank Ltd';
                } else if (lKey.indexOf('bank') !== -1) {
                    payload[k] = (account && account.bank_name) ? account.bank_name : 'Habib Bank Limited';
                } else if (lKey.indexOf('mask') !== -1 && lKey.indexOf('iban') !== -1) {
                    payload[k] = generateMaskedIBAN();
                } else if (lKey.indexOf('mask') !== -1 && (lKey.indexOf('account') !== -1 || lKey.indexOf('acc') !== -1 || lKey.endsWith('ac'))) {
                    payload[k] = generateMaskedAccountNumber();
                } else if (lKey.indexOf('mask') !== -1 && lKey.indexOf('card') !== -1) {
                    payload[k] = generateMaskedCardNumber();
                } else if (lKey.indexOf('receiver_account') !== -1 || lKey.indexOf('beneficiary_account') !== -1 || lKey === 'receiverac') {
                    payload[k] = generateAccountNumber();
                } else if (lKey.indexOf('account') !== -1 || lKey === 'senderac') {
                    payload[k] = (account && account.account_number) ? account.account_number : generateAccountNumber();
                } else if (lKey.indexOf('iban') !== -1) {
                    payload[k] = generateIBAN();
                } else if (lKey.indexOf('amount') !== -1 || lKey.indexOf('amt') !== -1) {
                    payload[k] = generateAmount();
                } else if (lKey.indexOf('receiver_name') !== -1 || lKey.indexOf('beneficiary_name') !== -1 || lKey.indexOf('sender_name') !== -1 || lKey === 'sender' || lKey === 'receiver') {
                    payload[k] = generatePakistaniName();
                } else if (lKey.indexOf('title') !== -1 || lKey.indexOf('name') !== -1) {
                    payload[k] = (account && account.account_title) ? account.account_title : generatePakistaniName();
                } else if (lKey.indexOf('card') !== -1) {
                    payload[k] = generateCardNumber();
                } else if (lKey.indexOf('code') !== -1) {
                    payload[k] = 'PK';
                } else if (lKey.indexOf('invoice') !== -1 || lKey.indexOf('bill') !== -1) {
                    payload[k] = 'INV-' + Math.floor(100000 + Math.random() * 900000);
                } else {
                    payload[k] = '';
                }
            }
        });

        var compiled = compile(pattern, payload);
        return {
            compiled: compiled,
            narration: compiled,
            payload: payload
        };
    }

    function regenerateAllInNarration(currentNarration, template, payload) {
        var text = currentNarration || '';
        var newPayload = Object.assign({}, payload || {});

        // 1. Template fields regeneration:
        // STRICT USER CONTROL: If a template is provided and has fields defined,
        // ONLY regenerate fields that have is_regeneratable marked!
        if (template && template.fields && template.fields.length > 0) {
            var anyRegenerated = false;
            template.fields.forEach(function(field) {
                var key = field.field_key;
                var isRegen = (field.is_regeneratable !== undefined)
                    ? (field.is_regeneratable === 1 || field.is_regeneratable === true || field.is_regeneratable === '1')
                    : (['stan', 'transaction_id', 'reference_no', 'card_number', 'masked_card', 'pakistani_name', 'account', 'masked_account', 'iban', 'masked_iban', 'amount', 'number', 'atm_code', 'atm', 'rrn'].includes((field.field_type || '').toLowerCase()) || ['stan', 'rrn', 'atm', 'atm_code', 'terminal'].some(function(x) { return (field.field_key || '').toLowerCase().indexOf(x) !== -1; }));

                // If NOT marked regeneratable by user, DO NOT touch!
                if (!isRegen) {
                    return;
                }

                anyRegenerated = true;
                var oldVal = newPayload[key];
                var newVal = (field.field_type === 'pakistani_name') 
                    ? generatePakistaniName() 
                    : generateValueForField(field);

                var attempts = 0;
                while (newVal === oldVal && attempts < 5) {
                    newVal = (field.field_type === 'pakistani_name') 
                        ? generatePakistaniName() 
                        : generateValueForField(field);
                    attempts++;
                }
                newPayload[key] = newVal;

                if (oldVal && text.indexOf(oldVal) !== -1) {
                    text = text.split(oldVal).join(newVal);
                }
            });

            // If template pattern exists and fields were regenerated, recompile cleanly:
            if (anyRegenerated && template.pattern) {
                var recompiled = compile(template.pattern, newPayload);
                if (recompiled) {
                    text = recompiled;
                }
            }

            return {
                updatedNarration: text,
                updatedPayload: newPayload
            };
        }

        // 2. Generic fallback regex replacement for free-text or tokens in narration (when no template fields are set):
        // STAN: STAN: 123456, STAN 123456, STAN# 123456, STAN-123456
        text = text.replace(/(STAN[\s:#-]+)(\d{5,8})/gi, function(match, prefix, digits) {
            var s = generateSTAN({ length: digits.length || 6 });
            newPayload.stan = s;
            return prefix + s;
        });

        // ATM Code: ATM-042-KHI, ATM-0419, ATM: 0419, ATM# 1029, ATM 1029
        text = text.replace(/(ATM[-:][0-9A-Z]+(?:-[A-Z]{3})?|ATM#\s*[0-9A-Z]+|ATM\s+[0-9]{3,6}(?:-[A-Z]{3})?)/gi, function(match) {
            var a = generateATMCode(match);
            newPayload.atm_code = a;
            return a;
        });

        // Reference: REF: BAHLPKKA... or Ref# BAHLPKKA... or REF 2609...
        text = text.replace(/(REF(?:ERENCE)?(?:[\s.#:-]+|\s*#\s*))([A-Z0-9]{8,30})/gi, function(match, prefix, refCode) {
            var p = refCode.length > 8 ? refCode.substring(0, 8) : 'BAHLPKKA';
            var r = generateRaastRef({ prefix: p });
            newPayload.reference_no = r;
            return prefix + r;
        });

        // Transaction ID: TXID: FT... or TRANS.ID: FT... or ID: FT...
        text = text.replace(/(TRANS\.?\s*ID[\s:#-]+|TX(?:ID|N)?[\s:#-]+)(FT[A-Z0-9]{8,24}|[A-Z0-9]{10,24})/gi, function(match, prefix, txId) {
            var t = generateTransId({ prefix: 'FT26' });
            newPayload.transaction_id = t;
            return prefix + t;
        });

        // RRN: RRN: 123456789012
        text = text.replace(/(RRN[\s:#-]+)(\d{10,16})/gi, function(match, prefix) {
            var rrn = generateRRN({ length: 12 });
            newPayload.rrn = rrn;
            return prefix + rrn;
        });

        // Masked Account in free text: e.g. 0417-XXXX1691-01 or 0417-****1691-01
        text = text.replace(/\b(\d{3,4})-([X\*]{4})(\d{4})-(\d{2})\b/gi, function() {
            var ma = generateMaskedAccountNumber();
            newPayload.masked_account = ma;
            return ma;
        });

        // Masked IBAN in free text: e.g. PK36HABBXXXXXXXXXXXX1601 or PK36HABB************1601
        text = text.replace(/\bPK\d{2}[A-Z]{4}[X\*]{12}\d{4}\b/gi, function() {
            var mi = generateMaskedIBAN();
            newPayload.masked_iban = mi;
            return mi;
        });

        // Masked Card in free text: e.g. 5120-XXXX-XXXX-4892 or 5120-****-****-4892
        text = text.replace(/\b(\d{4})-[X\*]{4}-[X\*]{4}-(\d{4})\b/gi, function() {
            var mc = generateMaskedCardNumber();
            newPayload.masked_card = mc;
            return mc;
        });

        // Unreplaced literal tokens in string
        if (text.indexOf('{stan}') !== -1) {
            var s = generateSTAN({ length: 6 });
            newPayload.stan = s;
            text = text.split('{stan}').join(s);
        }
        if (text.indexOf('{atm_code}') !== -1) {
            var a = generateATMCode('ATM-042-KHI');
            newPayload.atm_code = a;
            text = text.split('{atm_code}').join(a);
        }
        if (text.indexOf('{transaction_id}') !== -1) {
            var t = generateTransId({ prefix: 'FT26' });
            newPayload.transaction_id = t;
            text = text.split('{transaction_id}').join(t);
        }
        if (text.indexOf('{reference_no}') !== -1) {
            var r = generateRaastRef({ prefix: 'BAHLPKKA' });
            newPayload.reference_no = r;
            text = text.split('{reference_no}').join(r);
        }
        if (text.indexOf('{masked_account}') !== -1) {
            var ma = generateMaskedAccountNumber();
            newPayload.masked_account = ma;
            text = text.split('{masked_account}').join(ma);
        }
        if (text.indexOf('{masked_iban}') !== -1) {
            var mi = generateMaskedIBAN();
            newPayload.masked_iban = mi;
            text = text.split('{masked_iban}').join(mi);
        }
        if (text.indexOf('{masked_card}') !== -1) {
            var mc = generateMaskedCardNumber();
            newPayload.masked_card = mc;
            text = text.split('{masked_card}').join(mc);
        }
        if (text.indexOf('{account}') !== -1) {
            var accNum = generateAccountNumber();
            newPayload.account = accNum;
            text = text.split('{account}').join(accNum);
        }
        if (text.indexOf('{iban}') !== -1) {
            var ib = generateIBAN();
            newPayload.iban = ib;
            text = text.split('{iban}').join(ib);
        }
        if (text.indexOf('{pakistani_name}') !== -1 || text.indexOf('{name}') !== -1) {
            var pn = generatePakistaniName();
            newPayload.name = pn;
            text = text.split('{pakistani_name}').join(pn).split('{name}').join(pn);
        }
        if (text.indexOf('{card_number}') !== -1) {
            var cn = generateCardNumber();
            newPayload.card_number = cn;
            text = text.split('{card_number}').join(cn);
        }
        if (text.indexOf('{amount}') !== -1) {
            var am = generateAmount();
            newPayload.amount = am;
            text = text.split('{amount}').join(am);
        }

        return {
            updatedNarration: text,
            updatedPayload: newPayload
        };
    }

    return {
        renderDynamicFields: renderDynamicFields,
        getFormPayload: getFormPayload,
        updateNarrationOutput: updateNarrationOutput,
        setManualOverride: setManualOverride,
        loadMasterBanks: loadMasterBanks,
        getMasterBanks: getMasterBanks,
        compile: compile,
        generateValueForField: generateValueForField,
        generateSTAN: generateSTAN,
        generateRaastRef: generateRaastRef,
        generateTransId: generateTransId,
        generateATMCode: generateATMCode,
        generateRRN: generateRRN,
        generatePakistaniName: generatePakistaniName,
        generateAccountNumber: generateAccountNumber,
        generateMaskedAccountNumber: generateMaskedAccountNumber,
        generateIBAN: generateIBAN,
        generateMaskedIBAN: generateMaskedIBAN,
        generateAmount: generateAmount,
        generateNumber: generateNumber,
        generateCardNumber: generateCardNumber,
        generateMaskedCardNumber: generateMaskedCardNumber,
        maskAccountNumber: maskAccountNumber,
        maskIBAN: maskIBAN,
        generateBankName: generateBankName,
        parseAndGeneratePattern: parseAndGeneratePattern,
        loadCustomTypes: loadCustomTypes,
        getCustomTypes: getCustomTypes,
        getReadyMadePatterns: getReadyMadePatterns,
        READY_MADE_PATTERNS: READY_MADE_PATTERNS,
        autoPopulateFromTemplate: autoPopulateFromTemplate,
        regenerateAllInNarration: regenerateAllInNarration,
        escapeHtml: escapeHtml
    };
})();
