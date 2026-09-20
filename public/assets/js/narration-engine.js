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

    function generateValueForField(field) {
        var type = (field.field_type || '').toLowerCase();
        var key = (field.field_key || '').toLowerCase();
        var rule = field.generator_rule;
        if (typeof rule === 'string') {
            try { rule = JSON.parse(rule); } catch(e) { rule = {}; }
        }
        rule = rule || {};
        var ruleType = (rule.type || '').toLowerCase();

        if (type === 'stan' || ruleType === 'stan' || key === 'stan' || key.indexOf('stan') !== -1 || key.indexOf('trace') !== -1) {
            return generateSTAN(rule);
        } else if (type === 'atm_code' || type === 'atm' || ruleType === 'atm' || ruleType === 'atm_code' || key.indexOf('atm') !== -1 || key.indexOf('terminal') !== -1) {
            return generateATMCode(field.default_value || rule);
        } else if (type === 'transaction_id' || ruleType === 'transaction_id' || ruleType === 'alphanumeric' || key.indexOf('trans_id') !== -1 || key.indexOf('transaction_id') !== -1 || key.indexOf('txid') !== -1 || key.indexOf('ft_id') !== -1) {
            return generateTransId(rule);
        } else if (type === 'reference_no' || ruleType === 'bank_ref' || ruleType === 'reference_no' || key.indexOf('reference_no') !== -1 || key.indexOf('ref_no') !== -1 || key.indexOf('bank_ref') !== -1 || key.indexOf('raast_ref') !== -1) {
            return generateRaastRef(rule);
        } else if (type === 'rrn' || ruleType === 'rrn' || key.indexOf('rrn') !== -1) {
            return generateRRN(rule);
        } else if (type === 'card_number' || key.indexOf('card') !== -1) {
            return '5120-XXXX-XXXX-' + Math.floor(1000 + Math.random() * 9000);
        } else if (key.indexOf('invoice') !== -1 || key.indexOf('bill') !== -1) {
            return 'INV-' + Math.floor(100000 + Math.random() * 900000);
        } else if (key.indexOf('branch') !== -1) {
            return 'BR-' + String(Math.floor(10 + Math.random() * 990)).padStart(4, '0');
        } else if (ruleType === 'numeric') {
            var len = rule.length || 6;
            var num = '';
            for (var i = 0; i < len; i++) num += Math.floor(Math.random() * 10);
            return (rule.prefix || '') + num + (rule.suffix || '');
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
                               ['stan', 'transaction_id', 'reference_no'].includes(field.field_type);

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
            var val = field.default_value || '';

            var hasGenerator = (field.generator_rule && Object.keys(field.generator_rule).length > 0) || 
                               ['stan', 'transaction_id', 'reference_no', 'atm_code', 'atm', 'rrn'].includes(type) ||
                               ['stan', 'rrn', 'ref', 'reference_no', 'transaction_id', 'atm', 'atm_code', 'atm_id', 'terminal'].some(function(x) { return lKey.indexOf(x) !== -1; });

            if (hasGenerator) {
                val = generateValueForField(field);
            } else if (lKey.indexOf('receiver_bank') !== -1 || lKey.indexOf('beneficiary_bank') !== -1) {
                val = (account && account.bank_name && account.bank_name.indexOf('Meezan') !== -1) 
                    ? 'Habib Bank Limited' 
                    : (masterBanks.length > 1 ? masterBanks[1].bank_name : 'Meezan Bank Ltd');
            } else if (lKey.indexOf('bank') !== -1 || type === 'bank') {
                val = (account && account.bank_name) ? account.bank_name : (masterBanks.length > 0 ? masterBanks[0].bank_name : 'HBL');
            } else if (lKey.indexOf('receiver_account') !== -1 || lKey.indexOf('beneficiary_account') !== -1) {
                val = '0417-79011691-01';
            } else if (lKey.indexOf('account') !== -1 || type === 'account') {
                val = (account && account.account_number) ? account.account_number : (account ? (account.account_identifier || '0101-01029384-01') : '0101-01029384-01');
            } else if (lKey.indexOf('iban') !== -1 || type === 'iban') {
                val = (account && account.iban) ? account.iban : 'PK36BAHL0000000102938401';
            } else if (lKey.indexOf('receiver_name') !== -1 || lKey.indexOf('beneficiary_name') !== -1) {
                val = 'Usman Khan';
            } else if (lKey.indexOf('title') !== -1 || lKey.indexOf('name') !== -1) {
                val = (account && account.account_title) ? account.account_title : 'Muhammad Ali';
            } else if (lKey.indexOf('code') !== -1) {
                val = field.default_value || 'PK';
            } else if (lKey.indexOf('card') !== -1) {
                val = '5120-XXXX-XXXX-' + Math.floor(1000 + Math.random() * 9000);
            } else if (lKey.indexOf('invoice') !== -1 || lKey.indexOf('bill') !== -1) {
                val = 'INV-' + Math.floor(100000 + Math.random() * 900000);
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
                    payload[k] = (account && account.bank_name) ? account.bank_name : 'HBL';
                } else if (lKey.indexOf('receiver_account') !== -1 || lKey.indexOf('beneficiary_account') !== -1) {
                    payload[k] = '0417-79011691-01';
                } else if (lKey.indexOf('account') !== -1) {
                    payload[k] = (account && account.account_number) ? account.account_number : '0101-01029384-01';
                } else if (lKey.indexOf('iban') !== -1) {
                    payload[k] = 'PK36BAHL0000000102938401';
                } else if (lKey.indexOf('receiver_name') !== -1 || lKey.indexOf('beneficiary_name') !== -1) {
                    payload[k] = 'Usman Khan';
                } else if (lKey.indexOf('title') !== -1 || lKey.indexOf('name') !== -1) {
                    payload[k] = (account && account.account_title) ? account.account_title : 'Muhammad Ali';
                } else if (lKey.indexOf('card') !== -1) {
                    payload[k] = '5120-XXXX-XXXX-' + Math.floor(1000 + Math.random() * 9000);
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

        // 1. Template fields regeneration
        if (template && template.fields) {
            template.fields.forEach(function(field) {
                var key = field.field_key;
                var type = (field.field_type || '').toLowerCase();
                var lKey = (key || '').toLowerCase();
                var hasGenerator = (field.generator_rule && Object.keys(field.generator_rule).length > 0) || 
                                   ['stan', 'transaction_id', 'reference_no', 'atm_code', 'atm', 'rrn'].includes(type) ||
                                   ['stan', 'rrn', 'ref', 'reference_no', 'transaction_id', 'atm', 'atm_code', 'atm_id', 'terminal'].some(function(x) { return lKey.indexOf(x) !== -1; });

                if (hasGenerator) {
                    var oldVal = newPayload[key];
                    var newVal = generateValueForField(field);
                    var attempts = 0;
                    while (newVal === oldVal && attempts < 5) {
                        newVal = generateValueForField(field);
                        attempts++;
                    }
                    newPayload[key] = newVal;

                    if (oldVal && text.indexOf(oldVal) !== -1) {
                        text = text.split(oldVal).join(newVal);
                    }
                }
            });
        }

        // 2. Generic fallback regex replacement for free-text or tokens in narration:
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
        autoPopulateFromTemplate: autoPopulateFromTemplate,
        regenerateAllInNarration: regenerateAllInNarration,
        escapeHtml: escapeHtml
    };
})();
