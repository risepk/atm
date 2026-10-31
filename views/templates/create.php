<!-- views/templates/create.php -->
<?php $pageTitle = 'Create Narration Template'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-5xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Template Creator -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Create New Narration Template</span>
            </div>
            <div class="flex items-center space-x-1">
                <a href="/atm/templates" class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded" title="Close">✕</a>
            </div>
        </div>

        <form id="template-editor-form" class="p-4 space-y-4 bg-[#f2f5fa]">
            <div class="bg-white border border-[#cbd9e8] rounded p-4 space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#1a385f] border-b pb-1">1. Template Basic Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Template Name *</label>
                        <input type="text" id="tpl-name" name="name" required placeholder="e.g. IBFT Funds Transfer" class="win-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Unique Code *</label>
                        <input type="text" id="tpl-code" name="code" required placeholder="e.g. IBFT_OUT" class="win-input w-full uppercase font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Category</label>
                        <select id="tpl-category" name="category" class="win-input w-full bg-white">
                            <option value="transfer">Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="billing">Billing</option>
                            <option value="fee">Fees / Taxes</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Target Bank</label>
                        <select id="tpl-bank-id" name="bank_id" class="win-input w-full bg-white text-xs">
                            <option value="">-- All Banks (Shared) --</option>
                            <?php if (!empty($banks)): foreach ($banks as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['bank_name']) ?> (<?= e($b['bank_code']) ?>)</option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Pattern Template String *</label>
                    <textarea id="tpl-pattern" name="pattern" rows="2" required placeholder="e.g. IBFT to {receiver_name} - {bank} Acc: {account} Ref: {reference_no} STAN: {stan}" class="win-input w-full font-mono text-xs"></textarea>
                    <div class="flex items-center justify-between text-[11px] text-gray-500 mt-1">
                        <span>Use <code class="bg-gray-100 px-1 py-0.5 rounded font-mono text-blue-900">{field_key}</code> placeholders.</span>
                        <button type="button" id="auto-sync-fields-btn" class="text-blue-700 font-bold hover:underline">Auto-detect fields from pattern &darr;</button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Fields Builder -->
            <div class="bg-white border border-[#cbd9e8] rounded p-4 space-y-3">
                <div class="flex items-center justify-between border-b pb-1">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#1a385f]">2. Dynamic Form Fields</h2>
                        <span class="text-[10px] text-gray-500">Check <strong>Regen</strong> on fields you want re-randomized during rapid entry (F2 / ↻). Customize any pattern or define new types.</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" id="btn-open-custom-modal" class="win-btn win-btn-sm font-bold text-indigo-900 border border-indigo-300 bg-indigo-50 hover:bg-indigo-100">+ New Custom Type</button>
                        <button type="button" id="add-field-btn" class="win-btn win-btn-sm font-bold text-purple-900">+ Add Field</button>
                    </div>
                </div>

                <div id="fields-list" class="space-y-2"></div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <a href="/atm/templates" class="win-btn">Cancel</a>
                <button type="submit" id="save-template-btn" class="win-btn font-bold text-purple-900">Save Template</button>
            </div>
        </form>
    </div>
</main>

<!-- Modal: New Custom Type -->
<div id="modal-custom-type" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="win-window w-full max-w-lg mx-4 bg-[#f0f4f9] shadow-2xl border border-[#7ea3cc] rounded overflow-hidden">
        <div class="win-titlebar flex justify-between items-center px-3 py-1.5 bg-gradient-to-r from-[#d9e6f5] to-[#c2d7ed] border-b border-[#a6c0de]">
            <span class="font-bold text-xs text-[#1e395b] flex items-center gap-1.5">
                ⚡ Define New Custom Banking Pattern / Type
            </span>
            <button type="button" id="btn-close-custom-modal" class="text-xs font-bold text-gray-600 hover:text-red-700">✕</button>
        </div>
        <form id="form-new-custom-type" class="p-4 space-y-3 bg-white">
            <div class="text-[11px] text-gray-600 bg-blue-50 border border-blue-200 rounded p-2">
                Define a reusable banking type with fixed constants (e.g. prefix, branch code, mask) and changeable tokens. It will immediately appear in your Type dropdown!
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Type Name *</label>
                <input type="text" id="ct-name" required placeholder="e.g. Meezan Bank FT Reference" class="win-input w-full text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Unique Type Code *</label>
                <input type="text" id="ct-code" required placeholder="e.g. meezan_ft" class="win-input w-full text-xs font-mono lowercase">
                <span class="text-[10px] text-gray-500">Alphanumeric with underscores, e.g. alfalah_raast</span>
            </div>
            <div>
                <div class="flex items-center justify-between mb-0.5">
                    <label class="block text-xs font-bold text-gray-700 uppercase">Pattern / Mask *</label>
                    <span class="text-[10px] text-blue-700 font-mono font-bold">#=0-9, @=A-Z, [DATE]=Date</span>
                </div>
                <input type="text" id="ct-pattern" required placeholder="e.g. FT26@@###### or BAHLPKKA########" class="win-input w-full text-xs font-mono">
                <div class="flex flex-wrap items-center gap-1 mt-1.5">
                    <span class="text-[10px] text-gray-500 font-bold">Insert token:</span>
                    <button type="button" class="btn-token-insert px-1.5 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 rounded text-[10px] font-mono font-bold" data-val="#"># Digit (0-9)</button>
                    <button type="button" class="btn-token-insert px-1.5 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 rounded text-[10px] font-mono font-bold" data-val="@">@ Letter (A-Z)</button>
                    <button type="button" class="btn-token-insert px-1.5 py-0.5 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 rounded text-[10px] font-mono font-bold" data-val="[DATE]">[DATE]</button>
                    <button type="button" class="btn-token-insert px-1.5 py-0.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded text-[10px] font-mono font-bold" data-val="XXXX">XXXX Mask</button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Description (Optional)</label>
                <input type="text" id="ct-desc" placeholder="e.g. Meezan Bank FT Reference with FT26 prefix" class="win-input w-full text-xs">
            </div>
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-200">
                <button type="button" id="btn-cancel-custom-modal" class="win-btn text-xs">Cancel</button>
                <button type="submit" id="btn-save-custom-type" class="win-btn font-bold text-purple-900 text-xs">Save Custom Type</button>
            </div>
        </form>
    </div>
</div>

<script>
var fieldIndex = 0;
var customTypesList = <?= json_encode($customTypes ?? []) ?>;
var customTypesMap = {};
if (Array.isArray(customTypesList)) {
    customTypesList.forEach(function(ct) {
        if (ct && ct.type_code) customTypesMap[ct.type_code.toLowerCase()] = ct;
    });
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

if (Array.isArray(customTypesList)) {
    customTypesList.forEach(function(ct) {
        if (ct && ct.type_code) READY_MADE_PATTERNS[ct.type_code.toLowerCase()] = ct.pattern;
    });
}

function renderTypeOptions(selectedType) {
    var readyMade = [
        { val: 'pakistani_name', label: 'Pakistani Name', pat: '[Pakistani Name]' },
        { val: 'account', label: 'Account Number', pat: '0417-########-01' },
        { val: 'masked_account', label: 'Masked Account No', pat: '0417-XXXX####-01' },
        { val: 'iban', label: 'IBAN', pat: 'PK##HABB################' },
        { val: 'masked_iban', label: 'Masked IBAN', pat: 'PK##HABBXXXXXXXXXXXX####' },
        { val: 'card_number', label: 'Card Number', pat: '5120-####-####-####' },
        { val: 'masked_card', label: 'Masked Card Number', pat: '5120-XXXX-XXXX-####' },
        { val: 'stan', label: 'STAN (6-digit)', pat: '######' },
        { val: 'transaction_id', label: 'Transaction ID', pat: 'FT26@@######' },
        { val: 'reference_no', label: 'Reference No', pat: 'BAHLPKKA########' },
        { val: 'atm_code', label: 'ATM Code', pat: 'ATM-###-@@@' },
        { val: 'number', label: 'Number', pat: '######' },
        { val: 'amount', label: 'Amount', pat: '##,###.00' }
    ];

    var standard = [
        { val: 'text', label: 'Text' },
        { val: 'bank', label: 'Bank Dropdown' },
        { val: 'dropdown', label: 'Dropdown Options' },
        { val: 'textarea', label: 'Textarea' }
    ];

    var html = '';
    html += '<optgroup label="Ready-Made Banking Patterns">';
    readyMade.forEach(function(item) {
        var sel = (selectedType === item.val) ? 'selected' : '';
        html += `<option value="${item.val}" ${sel}>${item.label} (${item.pat})</option>`;
    });
    html += '</optgroup>';

    html += '<optgroup label="Custom & Mask Patterns">';
    var customSel = (selectedType === 'custom_pattern') ? 'selected' : '';
    html += `<option value="custom_pattern" ${customSel}>⚡ Custom Pattern / Mask (User-Defined)</option>`;
    if (customTypesList && customTypesList.length > 0) {
        customTypesList.forEach(function(ct) {
            var s = (selectedType === ct.type_code.toLowerCase()) ? 'selected' : '';
            html += `<option value="${ct.type_code.toLowerCase()}" ${s}>⭐ ${ct.type_name} (${ct.pattern})</option>`;
        });
    }
    html += '</optgroup>';

    html += '<optgroup label="Standard Inputs">';
    standard.forEach(function(item) {
        var sel = (selectedType === item.val) ? 'selected' : '';
        html += `<option value="${item.val}" ${sel}>${item.label}</option>`;
    });
    html += '</optgroup>';

    return html;
}

function addFieldRow(data = {}) {
    var idx = fieldIndex++;
    var isRegen = (data.is_regeneratable === 1 || data.is_regeneratable === true || data.is_regeneratable === '1');
    var isReq = (data.is_required !== 0 && data.is_required !== '0');
    var currentType = data.field_type || 'text';
    var defaultPat = READY_MADE_PATTERNS[currentType] || '';
    var initialOptVal = data.options_source || data.placeholder || defaultPat;

    var row = $(`
        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-12 gap-2 items-center field-row" data-index="${idx}">
            <div class="md:col-span-2">
                <label class="block text-[10px] uppercase font-bold text-gray-600">Field Key *</label>
                <input type="text" class="field-key w-full px-2 py-1 text-xs font-mono border rounded" placeholder="e.g. receiver_name" value="${data.field_key || ''}" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] uppercase font-bold text-gray-600">Field Label *</label>
                <input type="text" class="field-label w-full px-2 py-1 text-xs border rounded" placeholder="e.g. Receiver Name" value="${data.field_label || ''}" required>
            </div>
            <div class="md:col-span-3">
                <label class="block text-[10px] uppercase font-bold text-gray-600">Type / Ready-Made Pattern</label>
                <select class="field-type w-full px-2 py-1 text-xs border rounded bg-white">
                    ${renderTypeOptions(currentType)}
                </select>
            </div>
            <div class="md:col-span-3">
                <div class="flex items-center justify-between">
                    <label class="block text-[10px] uppercase font-bold text-gray-600">Pattern / Mask</label>
                    <span class="text-[9px] text-blue-700 font-mono" title="# = Digit (0-9), @ = Letter (A-Z), [DATE] = Date">#=0-9 @=A-Z [DATE]</span>
                </div>
                <input type="text" class="field-options w-full px-2 py-1 text-xs font-mono border rounded" placeholder="Pattern (e.g. FT26@@###### or hint)" value="${initialOptVal}">
            </div>
            <div class="md:col-span-1 flex items-center space-x-1 pt-3">
                <label class="flex items-center space-x-0.5 cursor-pointer" title="Required field">
                    <input type="checkbox" class="field-required h-4 w-4 text-purple-600" ${isReq ? 'checked' : ''}>
                    <span class="text-[11px] text-gray-700">Req</span>
                </label>
                <label class="flex items-center space-x-0.5 cursor-pointer" title="Auto-regenerate fresh value during rapid entry / refresh">
                    <input type="checkbox" class="field-regenerate h-4 w-4 text-blue-600" ${isRegen ? 'checked' : ''}>
                    <span class="text-[11px] font-bold text-blue-900">Reg</span>
                </label>
            </div>
            <div class="md:col-span-1 text-right pt-2">
                <button type="button" class="remove-field-btn text-red-600 hover:text-red-900 text-xs font-bold">Remove</button>
            </div>
        </div>
    `);

    row.find('.field-type').on('change', function() {
        var t = $(this).val();
        var currentOpt = row.find('.field-options').val().trim();
        var allDefaults = Object.values(READY_MADE_PATTERNS);
        if (!currentOpt || allDefaults.includes(currentOpt)) {
            if (READY_MADE_PATTERNS[t]) {
                row.find('.field-options').val(READY_MADE_PATTERNS[t]);
            } else if (t === 'custom_pattern') {
                row.find('.field-options').val('').focus();
            }
        }
    });

    row.find('.remove-field-btn').on('click', function() {
        row.remove();
    });

    $('#fields-list').append(row);
}

$('#add-field-btn').on('click', function() {
    addFieldRow();
});

// Custom Type Modal Handlers
$('#btn-open-custom-modal').on('click', function() {
    $('#modal-custom-type').removeClass('hidden');
    $('#ct-name').focus();
});

$('#btn-close-custom-modal, #btn-cancel-custom-modal').on('click', function() {
    $('#modal-custom-type').addClass('hidden');
});

$('.btn-token-insert').on('click', function() {
    var tok = $(this).data('val');
    var $inp = $('#ct-pattern');
    $inp.val($inp.val() + tok).focus();
});

$('#ct-name').on('input', function() {
    if (!$('#ct-code').data('manual')) {
        var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        $('#ct-code').val(slug);
    }
});

$('#ct-code').on('input', function() {
    $(this).data('manual', true);
});

$('#form-new-custom-type').on('submit', function(e) {
    e.preventDefault();
    var name = $('#ct-name').val().trim();
    var code = $('#ct-code').val().trim();
    var pat = $('#ct-pattern').val().trim();
    var desc = $('#ct-desc').val().trim();

    if (!name || !code || !pat) return;

    $('#btn-save-custom-type').prop('disabled', true);
    $.post('/atm/api/custom-types/save', {
        type_name: name,
        type_code: code,
        pattern: pat,
        description: desc
    }, function(res) {
        $('#btn-save-custom-type').prop('disabled', false);
        if (res && res.success && res.data) {
            var newType = res.data;
            customTypesList.push(newType);
            customTypesMap[newType.type_code.toLowerCase()] = newType;
            READY_MADE_PATTERNS[newType.type_code.toLowerCase()] = newType.pattern;

            // Update all .field-type selects dynamically
            $('.field-row').each(function() {
                var $sel = $(this).find('.field-type');
                var currentVal = $sel.val();
                $sel.html(renderTypeOptions(currentVal));
            });

            $('#modal-custom-type').addClass('hidden');
            $('#form-new-custom-type')[0].reset();
            $('#ct-code').removeData('manual');

            if (window.showToast) {
                window.showToast('Custom type "' + newType.type_name + '" saved and ready to use!', 'success');
            } else {
                alert('Custom type "' + newType.type_name + '" saved and ready to use!');
            }
        } else {
            alert(res.message || 'Error saving custom type.');
        }
    }, 'json').fail(function(xhr) {
        $('#btn-save-custom-type').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Server error saving custom type.');
    });
});

function inferFieldType(key) {
    var lower = key.toLowerCase();
    var isMask = (lower.includes('mask') || lower.includes('hidden') || lower.includes('xxxx'));
    if (lower.includes('stan')) return 'stan';
    if (lower.includes('bank')) return 'bank';
    if (lower.includes('iban')) return isMask ? 'masked_iban' : 'iban';
    if (lower.includes('account') || lower.includes('acc_no') || lower.includes('acc') || lower.includes('ac')) return isMask ? 'masked_account' : 'account';
    if (lower.includes('trans_id') || lower.includes('tid') || lower.includes('tx_id') || lower.includes('trx')) return 'transaction_id';
    if (lower.includes('ref') || lower.includes('reference') || lower.includes('rrn')) return 'reference_no';
    if (lower.includes('amount') || lower.includes('amt') || lower.includes('fee') || lower.includes('balance') || lower.includes('tax')) return 'amount';
    if (lower.includes('card')) return isMask ? 'masked_card' : 'card_number';
    if (lower.includes('name') || lower.includes('sender') || lower.includes('receiver') || lower.includes('beneficiary') || lower.includes('title')) return 'pakistani_name';
    if (lower.includes('number') || lower.includes('num') || lower.includes('count')) return 'number';
    if (isMask) return 'masked_account';
    if (lower.includes('date') || lower.includes('time')) return 'text';
    if (lower.includes('desc') || lower.includes('remarks') || lower.includes('note')) return 'textarea';
    return 'text';
}

function humanizeLabel(key) {
    return key.split('_').map(function(w) {
        return w.charAt(0).toUpperCase() + w.slice(1);
    }).join(' ');
}

function syncFieldsFromPattern() {
    var pattern = $('#tpl-pattern').val();
    var matches = pattern.match(/\{([a-zA-Z0-9_]+)\}/g);
    if (!matches || matches.length === 0) {
        alert('No {placeholders} found in pattern string.');
        return;
    }

    var tokenKeys = [];
    matches.forEach(function(m) {
        var k = m.replace(/[{}]/g, '').trim();
        if (k && !tokenKeys.includes(k)) {
            tokenKeys.push(k);
        }
    });

    var existingByMap = {};
    $('.field-row').each(function() {
        var k = $(this).find('.field-key').val().trim();
        if (k) {
            existingByMap[k] = {
                field_key: k,
                field_label: $(this).find('.field-label').val().trim(),
                field_type: $(this).find('.field-type').val(),
                placeholder: $(this).find('.field-options').val().trim(),
                options_source: $(this).find('.field-options').val().trim(),
                is_required: $(this).find('.field-required').is(':checked') ? 1 : 0,
                is_regeneratable: $(this).find('.field-regenerate').is(':checked') ? 1 : 0
            };
        }
    });

    $('#fields-list').empty();

    var addedCount = 0;
    var retainedCount = 0;

    tokenKeys.forEach(function(key) {
        if (existingByMap[key]) {
            addFieldRow(existingByMap[key]);
            retainedCount++;
        } else {
            var inferredType = inferFieldType(key);
            var label = humanizeLabel(key);
            var defaultRegen = ['stan', 'transaction_id', 'reference_no', 'card_number', 'masked_card', 'pakistani_name', 'account', 'masked_account', 'iban', 'masked_iban', 'amount', 'number', 'atm_code'].includes(inferredType) ? 1 : 0;
            addFieldRow({
                field_key: key,
                field_label: label,
                field_type: inferredType,
                options_source: READY_MADE_PATTERNS[inferredType] || '',
                is_required: 1,
                is_regeneratable: defaultRegen
            });
            addedCount++;
        }
    });

    if (window.showToast) {
        window.showToast(`Fields synchronized: ${addedCount} added, ${retainedCount} retained.`, 'info');
    } else {
        alert(`Fields synchronized: ${addedCount} added, ${retainedCount} retained.`);
    }
}

// Auto-detect placeholders in pattern
$('#auto-sync-fields-btn').on('click', function() {
    syncFieldsFromPattern();
});

$('#template-editor-form').on('submit', function(e) {
    e.preventDefault();
    var fields = [];
    $('.field-row').each(function() {
        var k = $(this).find('.field-key').val().trim();
        var l = $(this).find('.field-label').val().trim();
        var t = $(this).find('.field-type').val();
        var opt = $(this).find('.field-options').val().trim();
        var req = $(this).find('.field-required').is(':checked') ? 1 : 0;
        var regen = $(this).find('.field-regenerate').is(':checked') ? 1 : 0;

        if (k && l) {
            var rule = null;
            if (t === 'custom_pattern' || customTypesMap[t] || (opt && (opt.includes('#') || opt.includes('@') || opt.includes('[')))) {
                rule = { pattern: opt, type: t };
            }
            fields.push({
                field_key: k,
                field_label: l,
                field_type: t,
                placeholder: opt,
                options_source: opt,
                generator_rule: rule,
                is_required: req,
                is_regeneratable: regen
            });
        }
    });

    var postData = {
        name: $('#tpl-name').val().trim(),
        code: $('#tpl-code').val().trim(),
        category: $('#tpl-category').val(),
        bank_id: $('#tpl-bank-id').val() || null,
        pattern: $('#tpl-pattern').val().trim(),
        fields: fields
    };

    $('#save-template-btn').prop('disabled', true);

    $.post('/atm/api/templates/save', postData, function(res) {
        $('#save-template-btn').prop('disabled', false);
        if (res.success) {
            if (window.showToast) window.showToast('Template created successfully!', 'success');
            setTimeout(function() { window.location.href = '/atm/templates'; }, 700);
        } else {
            alert(res.message || 'Error saving template.');
        }
    }, 'json').fail(function(xhr) {
        $('#save-template-btn').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Server error saving template.');
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
