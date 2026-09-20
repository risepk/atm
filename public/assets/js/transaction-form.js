// public/assets/js/transaction-form.js

$(function() {
    var allTemplates = window.ATM_TEMPLATES || [];
    var currentPresets = [];

    // Load master banks
    $.getJSON('/atm/api/master/banks', function(res) {
        if (res.success) {
            NarrationEngine.loadMasterBanks(res.data);
            if (window.refreshSearchableSelect) {
                $('select.dynamic-field-input[data-key*="bank"]').each(function() {
                    window.refreshSearchableSelect(this);
                });
            }
        }
    });

    // If templates were not pre-rendered into window.ATM_TEMPLATES, fetch via AJAX
    if (!allTemplates || allTemplates.length === 0) {
        $.getJSON('/atm/api/templates/active', function(res) {
            if (res.success) {
                allTemplates = res.data;
                populateTemplateSelect(allTemplates);
            }
        });
    } else {
        // Searchable dropdown initialization
        if (window.initSearchableSelects) {
            window.initSearchableSelects();
        }
        checkInitialTemplate();
    }

    function populateTemplateSelect(templates) {
        var select = $('#template-select');
        select.empty();
        select.append('<option value="">-- Direct Narration (No Template) --</option>');
        templates.forEach(function(t) {
            select.append(`<option value="${t.id}">${t.name} (${t.code})</option>`);
        });

        if (window.refreshSearchableSelect) {
            window.refreshSearchableSelect(select[0]);
        }
        checkInitialTemplate();
    }

    function checkInitialTemplate() {
        var initialTplId = $('#template-select').data('initial');
        if (initialTplId) {
            $('#template-select').val(initialTplId);
            if (window.refreshSearchableSelect) {
                window.refreshSearchableSelect($('#template-select')[0]);
            }
            triggerTemplateLoad(initialTplId, window.INITIAL_PAYLOAD || null);
        }
    }

    // Mutual exclusion: Debit vs Credit
    $('#debit-input').on('input', function() {
        var val = $(this).val().trim();
        if (val && parseFloat(val) > 0) {
            $('#credit-input').val('');
        }
    });

    $('#credit-input').on('input', function() {
        var val = $(this).val().trim();
        if (val && parseFloat(val) > 0) {
            $('#debit-input').val('');
        }
    });

    function triggerTemplateLoad(tplId, payloadValues) {
        var selected = allTemplates.find(function(t) { return parseInt(t.id) === parseInt(tplId); });
        if (selected) {
            $('#template-fields-panel').removeClass('hidden');
            $('#preset-select-container').removeClass('hidden');

            loadPresets(tplId, function() {
                NarrationEngine.renderDynamicFields(selected, 'dynamic-fields-container', payloadValues);
                if (window.IS_EDIT_MODE) {
                    NarrationEngine.setManualOverride(true);
                }
            });
        }
    }

    // Template selection change
    $('#template-select').on('change select2:select', function() {
        var tplId = parseInt($(this).val());
        if (!tplId) {
            $('#template-fields-panel').addClass('hidden');
            $('#preset-select-container').addClass('hidden');
            $('#narration-preview-text').text('(Narration will appear here...)');
            return;
        }
        triggerTemplateLoad(tplId, null);
    });

    function loadPresets(templateId, callback) {
        $.getJSON('/atm/api/templates/' + templateId + '/presets', function(res) {
            var presetSelect = $('#preset-select');
            presetSelect.empty();
            presetSelect.append('<option value="">-- Choose Preset or enter new --</option>');

            if (res.success && res.data && res.data.length > 0) {
                currentPresets = res.data;
                res.data.forEach(function(p) {
                    var scope = p.user_id ? 'Personal' : 'Global';
                    presetSelect.append(`<option value="${p.id}">[${scope}] ${p.preset_name}</option>`);
                });
            }
            if (window.refreshSearchableSelect) {
                window.refreshSearchableSelect(presetSelect[0]);
            }
            if (callback) callback();
        });
    }

    // Preset selection change
    $('#preset-select').on('change select2:select', function() {
        var presetId = parseInt($(this).val());
        var tplId = parseInt($('#template-select').val());
        var selectedTpl = allTemplates.find(function(t) { return parseInt(t.id) === tplId; });

        if (!selectedTpl) return;

        if (presetId) {
            var preset = currentPresets.find(function(p) { return parseInt(p.id) === presetId; });
            if (preset) {
                var vals = typeof preset.field_values === 'string' ? JSON.parse(preset.field_values) : preset.field_values;
                NarrationEngine.renderDynamicFields(selectedTpl, 'dynamic-fields-container', vals);
                window.showToast('Preset loaded: ' + preset.preset_name, 'info');
                return;
            }
        }
        NarrationEngine.renderDynamicFields(selectedTpl, 'dynamic-fields-container', null);
    });

    // Detect direct edits in final narration textarea
    $('#final-narration-textarea').on('input', function() {
        NarrationEngine.setManualOverride(true);
    });

    // Re-sync button
    $('#resync-narration-btn').on('click', function() {
        NarrationEngine.setManualOverride(false);
        NarrationEngine.updateNarrationOutput();
        window.showToast('Narration re-synchronized from fields.', 'info');
    });

    // Save New Preset Modal
    $('#open-save-preset-btn').on('click', function() {
        var tplId = $('#template-select').val();
        if (!tplId) {
            alert('Please select a template first.');
            return;
        }
        $('#save-preset-modal').removeClass('hidden').addClass('flex');
    });

    $('#submit-save-preset-btn').on('click', function() {
        var tplId = parseInt($('#template-select').val());
        var name = $('#preset-modal-name').val().trim();
        var isGlobal = $('#preset-modal-global').is(':checked');
        var fieldValues = NarrationEngine.getFormPayload();

        if (!name) {
            alert('Please enter a preset name.');
            return;
        }

        $.post('/atm/api/presets/save', {
            template_id: tplId,
            preset_name: name,
            is_global: isGlobal ? 1 : 0,
            field_values: JSON.stringify(fieldValues)
        }, function(res) {
            if (res.success) {
                window.showToast('Preset "' + name + '" saved successfully!');
                $('#save-preset-modal').addClass('hidden').removeClass('flex');
                $('#preset-modal-name').val('');
                loadPresets(tplId);
            } else {
                alert(res.message || 'Error saving preset.');
            }
        }, 'json').fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error saving preset.';
            alert(msg);
        });
    });

    // Save / Update Transaction Submission Handler
    function submitTransaction(actionType) {
        var isEdit = (window.IS_EDIT_MODE === true);
        var txId = isEdit ? $('#tx-id').val() : null;
        var accountId = $('#account-select').val();
        var txDate = $('#tx-date-input').val();
        var debit = $('#debit-input').val().trim();
        var credit = $('#credit-input').val().trim();
        var narration = $('#final-narration-textarea').val();
        var refNo = $('#ref-no-input').val();
        var templateId = $('#template-select').val();

        if (!accountId) {
            alert('Please select an account.');
            $('#account-select').focus();
            return;
        }

        if (!txDate) {
            alert('Please enter a transaction date.');
            return;
        }

        var debitNum = parseFloat(debit) || 0;
        var creditNum = parseFloat(credit) || 0;

        if (debitNum <= 0 && creditNum <= 0) {
            alert('Please enter either a Debit or Credit amount greater than zero.');
            $('#debit-input').focus();
            return;
        }

        if (debitNum > 0 && creditNum > 0) {
            alert('Both Debit and Credit cannot be non-zero.');
            return;
        }

        if (!narration || narration.trim() === '') {
            alert('Narration / Description is required.');
            $('#final-narration-textarea').focus();
            return;
        }

        var tplPayload = NarrationEngine.getFormPayload();
        var selectedTpl = allTemplates.find(function(t) { return parseInt(t.id) === parseInt(templateId); });

        var postData = {
            account_id: accountId,
            transaction_date: txDate,
            debit: debit || '0.00',
            credit: credit || '0.00',
            narration: narration.trim(),
            reference_no: refNo,
            template_id: templateId ? parseInt(templateId) : null,
            template_version: selectedTpl ? selectedTpl.version : null,
            template_payload: tplPayload
        };

        var targetUrl = isEdit ? ('/atm/api/transactions/' + txId + '/update') : '/atm/api/transactions/save';
        var $submitBtns = $('#save-tx-btn, #save-new-tx-btn, #update-tx-btn');
        $submitBtns.prop('disabled', true).addClass('opacity-50');

        $.post(targetUrl, postData, function(res) {
            $submitBtns.prop('disabled', false).removeClass('opacity-50');

            if (res.success) {
                var msg = isEdit ? 'Transaction #' + txId + ' updated successfully!' : ('Transaction #' + res.data.transaction_id + ' saved! New Balance: PKR ' + res.data.new_balance);
                window.showToast(msg, 'success');

                if (actionType === 'new') {
                    $('#debit-input').val('');
                    $('#credit-input').val('');
                    $('#ref-no-input').val('');
                    $('#final-narration-textarea').val('');
                    $('#preset-select').val('');
                    NarrationEngine.setManualOverride(false);
                    if (selectedTpl) {
                        NarrationEngine.renderDynamicFields(selectedTpl, 'dynamic-fields-container', null);
                    }
                    $('#debit-input').focus();
                } else {
                    setTimeout(function() {
                        window.location.href = '/atm/transactions';
                    }, 800);
                }
            } else {
                alert(res.message || 'Failed to save transaction.');
            }
        }, 'json').fail(function(xhr) {
            $submitBtns.prop('disabled', false).removeClass('opacity-50');
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error saving transaction.';
            alert('Error: ' + msg);
        });
    }

    $('#save-tx-btn, #update-tx-btn').on('click', function(e) {
        e.preventDefault();
        submitTransaction('close');
    });

    $('#save-new-tx-btn').on('click', function(e) {
        e.preventDefault();
        submitTransaction('new');
    });

    // Keyboard Shortcuts: Ctrl+Enter to save, Alt+N for Save & New
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            submitTransaction('close');
        } else if (e.altKey && (e.key === 'n' || e.key === 'N')) {
            e.preventDefault();
            submitTransaction('new');
        }
    });
});
