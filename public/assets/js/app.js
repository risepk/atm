// public/assets/js/app.js

// Configure jQuery AJAX with CSRF token
$(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }

    // Global toast system
    window.showToast = function(message, type = 'success') {
        var bgColor = type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-600' : 'bg-blue-600');
        var toast = $(`
            <div class="fixed bottom-5 right-5 z-50 flex items-center px-4 py-3 text-white rounded-lg shadow-xl ${bgColor} transition-opacity duration-300">
                <span class="mr-2">${type === 'success' ? '✓' : (type === 'error' ? '⚠' : 'ℹ')}</span>
                <span>${message}</span>
            </div>
        `);
        $('body').append(toast);
        setTimeout(function() {
            toast.fadeOut(300, function() { $(this).remove(); });
        }, 3500);
    };

    // Global modal helpers
    window.openModal = function(modalId) {
        $('#' + modalId).removeClass('hidden').addClass('flex');
    };
    window.closeModal = function(modalId) {
        $('#' + modalId).addClass('hidden').removeClass('flex');
    };

    // Auto close on backdrop click
    $(document).on('click', '.modal-backdrop', function(e) {
        if (e.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });
});
