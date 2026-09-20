<!-- views/templates/edit.php -->
<?php $pageTitle = 'Edit Template: ' . e($template['name']); require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-5xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Template Editor -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Edit Narration Template: <?= e($template['name']) ?> (v<?= $template['version'] ?>)</span>
            </div>
            <div class="flex items-center space-x-1">
                <a href="/atm/templates" class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded" title="Close">✕</a>
            </div>
        </div>

        <form id="template-editor-form" class="p-4 space-y-4 bg-[#f2f5fa]">
            <input type="hidden" id="tpl-id" name="id" value="<?= $template['id'] ?>">

            <div class="bg-white border border-[#cbd9e8] rounded p-4 space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#1a385f] border-b pb-1">1. Template Basic Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Template Name *</label>
                        <input type="text" id="tpl-name" name="name" value="<?= e($template['name']) ?>" required class="win-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Unique Code *</label>
                        <input type="text" id="tpl-code" name="code" value="<?= e($template['code']) ?>" required class="win-input w-full uppercase font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Category</label>
                        <select id="tpl-category" name="category" class="win-input w-full bg-white">
                            <option value="transfer" <?= $template['category'] === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                            <option value="cash" <?= $template['category'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="billing" <?= $template['category'] === 'billing' ? 'selected' : '' ?>>Billing</option>
                            <option value="fee" <?= $template['category'] === 'fee' ? 'selected' : '' ?>>Fees / Taxes</option>
                            <option value="general" <?= $template['category'] === 'general' ? 'selected' : '' ?>>General</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Pattern Template String *</label>
                    <textarea id="tpl-pattern" name="pattern" rows="2" required class="win-input w-full font-mono text-xs"><?= e($template['pattern']) ?></textarea>
                    <div class="flex items-center justify-between text-[11px] text-gray-500 mt-1">
                        <span>Use <code class="bg-gray-100 px-1 py-0.5 rounded font-mono text-blue-900">{field_key}</code> placeholders.</span>
                        <button type="button" id="auto-sync-fields-btn" class="text-blue-700 font-bold hover:underline">Auto-detect fields from pattern &darr;</button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Fields Builder -->
            <div class="bg-white border border-[#cbd9e8] rounded p-4 space-y-3">
                <div class="flex items-center justify-between border-b pb-1">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#1a385f]">2. Dynamic Form Fields</h2>
                    <button type="button" id="add-field-btn" class="win-btn win-btn-sm font-bold text-purple-900">+ Add Field</button>
                </div>

                <div id="fields-list" class="space-y-2"></div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <a href="/atm/templates" class="win-btn">Cancel</a>
                <button type="submit" id="save-template-btn" class="win-btn font-bold text-purple-900">Update Template</button>
            </div>
        </form>
    </div>
</main>

<script>
var existingFields = <?= json_encode($template['fields'] ?? []) ?>;
var fieldIndex = 0;

function addFieldRow(data = {}) {
    var idx = fieldIndex++;
    var row = $(`
        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-6 gap-3 items-center field-row" data-index="${idx}">
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-600">Field Key *</label>
                <input type="text" class="field-key w-full px-2 py-1 text-xs font-mono border rounded" placeholder="e.g. sender_name" value="${data.field_key || ''}" required>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-600">Field Label *</label>
                <input type="text" class="field-label w-full px-2 py-1 text-xs border rounded" placeholder="e.g. Sender Name" value="${data.field_label || ''}" required>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-600">Type</label>
                <select class="field-type w-full px-2 py-1 text-xs border rounded bg-white">
                    <option value="text" ${data.field_type === 'text' ? 'selected' : ''}>Text</option>
                    <option value="number" ${data.field_type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="amount" ${data.field_type === 'amount' ? 'selected' : ''}>Amount</option>
                    <option value="bank" ${data.field_type === 'bank' ? 'selected' : ''}>Bank Dropdown</option>
                    <option value="account" ${data.field_type === 'account' ? 'selected' : ''}>Account / IBAN</option>
                    <option value="stan" ${data.field_type === 'stan' ? 'selected' : ''}>STAN (6-digit)</option>
                    <option value="transaction_id" ${data.field_type === 'transaction_id' ? 'selected' : ''}>Transaction ID</option>
                    <option value="reference_no" ${data.field_type === 'reference_no' ? 'selected' : ''}>Reference No</option>
                    <option value="card_number" ${data.field_type === 'card_number' ? 'selected' : ''}>Card Number</option>
                    <option value="dropdown" ${data.field_type === 'dropdown' ? 'selected' : ''}>Dropdown Options</option>
                    <option value="textarea" ${data.field_type === 'textarea' ? 'selected' : ''}>Textarea</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-600">Options / Placeholder</label>
                <input type="text" class="field-options w-full px-2 py-1 text-xs border rounded" placeholder="Options or hint" value="${data.options_source || data.placeholder || ''}">
            </div>
            <div class="flex items-center space-x-2 pt-3">
                <input type="checkbox" class="field-required h-4 w-4 text-purple-600" ${data.is_required !== 0 ? 'checked' : ''}>
                <span class="text-xs text-gray-700">Required</span>
            </div>
            <div class="text-right pt-2">
                <button type="button" class="remove-field-btn text-red-600 hover:text-red-900 text-xs font-bold">Remove</button>
            </div>
        </div>
    `);

    row.find('.remove-field-btn').on('click', function() {
        row.remove();
    });

    $('#fields-list').append(row);
}

$(function() {
    if (existingFields && existingFields.length > 0) {
        existingFields.forEach(function(f) { addFieldRow(f); });
    } else {
        addFieldRow();
    }
});

$('#add-field-btn').on('click', function() {
    addFieldRow();
});

function inferFieldType(key) {
    var lower = key.toLowerCase();
    if (lower.includes('stan')) return 'stan';
    if (lower.includes('bank')) return 'bank';
    if (lower.includes('iban') || lower.includes('account') || lower.includes('acc_no') || lower.includes('acc')) return 'account';
    if (lower.includes('trans_id') || lower.includes('tid') || lower.includes('tx_id') || lower.includes('trx')) return 'transaction_id';
    if (lower.includes('ref') || lower.includes('reference') || lower.includes('rrn')) return 'reference_no';
    if (lower.includes('amount') || lower.includes('amt') || lower.includes('fee') || lower.includes('balance') || lower.includes('tax')) return 'amount';
    if (lower.includes('card')) return 'card_number';
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
                is_required: $(this).find('.field-required').is(':checked') ? 1 : 0
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
            addFieldRow({
                field_key: key,
                field_label: label,
                field_type: inferredType,
                is_required: 1
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

        if (k && l) {
            fields.push({
                field_key: k,
                field_label: l,
                field_type: t,
                placeholder: opt,
                options_source: opt,
                is_required: req
            });
        }
    });

    var postData = {
        id: $('#tpl-id').val(),
        name: $('#tpl-name').val().trim(),
        code: $('#tpl-code').val().trim(),
        category: $('#tpl-category').val(),
        pattern: $('#tpl-pattern').val().trim(),
        fields: fields
    };

    $('#save-template-btn').prop('disabled', true);

    $.post('/atm/api/templates/save', postData, function(res) {
        $('#save-template-btn').prop('disabled', false);
        if (res.success) {
            window.showToast('Template updated successfully!', 'success');
            setTimeout(function() { window.location.href = '/atm/templates'; }, 700);
        } else {
            alert(res.message || 'Error updating template.');
        }
    }, 'json').fail(function(xhr) {
        $('#save-template-btn').prop('disabled', false);
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Server error updating template.');
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
