// public/assets/js/searchable-select.js
// Production Select2 Adapter & Helper for ATM Ledger

(function($) {
    'use strict';

    function initSelect2OnElement($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            // Already initialized
            return;
        }

        var placeholder = $el.attr('placeholder') || $el.find('option:first').text() || 'Search or select...';
        var isModal = $el.closest('.modal-backdrop, .modal, [id$="-modal"], #save-preset-modal, #account-modal, #add-bank-modal');
        
        var options = {
            width: '100%',
            placeholder: placeholder,
            allowClear: true
        };

        if (isModal.length > 0) {
            options.dropdownParent = isModal.first();
        }

        $el.select2(options);
    }

    window.initSearchableSelects = function(container) {
        var $scope = container ? $(container) : $(document);
        $scope.find('select.searchable-select').each(function() {
            initSelect2OnElement($(this));
        });
    };

    window.refreshSearchableSelect = function(selectEl) {
        if (!selectEl) return;
        var $el = $(selectEl);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.trigger('change.select2');
        } else {
            initSelect2OnElement($el);
        }
    };

    // Auto initialize on DOM ready
    $(function() {
        window.initSearchableSelects();
    });

})(jQuery);
