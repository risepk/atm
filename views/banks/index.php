<!-- views/banks/index.php -->
<?php $pageTitle = 'Bank Master Management'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Bank Master -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span>Bank Master Management &mdash; Institution Registry</span>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">_</span>
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-[#c2d7ed] rounded">□</span>
                <span class="w-3 h-3 flex items-center justify-center text-[10px] text-[#2c4c70] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
            </div>
        </div>

        <!-- Window Action Bar -->
        <div class="bg-[#f0f4f9] border-b border-[#cbd9e8] p-2 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-[#1e395b] font-semibold">
                Manage registered banking institutions, bank codes, brand logos, and active statuses.
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" onclick="openAddModal()" class="win-btn font-bold text-purple-900">
                    + Add New Bank
                </button>
            </div>
        </div>

        <!-- Desktop Stats & Search Bar -->
        <?php
        $totalBanks = count($banks);
        $activeBanks = count(array_filter($banks, fn($b) => $b['is_active'] == 1));
        $inactiveBanks = $totalBanks - $activeBanks;
        ?>
        <div class="bg-[#f7fafc] border-b border-[#cbd9e8] p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Total: <strong class="text-gray-900"><?= $totalBanks ?></strong></span>
                <span class="text-green-700">Active: <strong><?= $activeBanks ?></strong></span>
                <span class="text-gray-500">Inactive: <strong><?= $inactiveBanks ?></strong></span>
            </div>

            <div class="flex items-center space-x-2 flex-1 max-w-md justify-end">
                <input type="text" id="bank-search" placeholder="Search by name or code..." class="win-input w-full max-w-xs text-xs">
                <button type="button" onclick="filterStatus('all')" class="status-tab win-btn win-btn-sm font-bold text-purple-900" data-tab="all">All</button>
                <button type="button" onclick="filterStatus('active')" class="status-tab win-btn win-btn-sm text-gray-700" data-tab="active">Active</button>
                <button type="button" onclick="filterStatus('inactive')" class="status-tab win-btn win-btn-sm text-gray-700" data-tab="inactive">Inactive</button>
            </div>
        </div>

        <!-- Banks Grid Table (Desktop Style) -->
        <div class="overflow-x-auto bg-white">
            <table class="w-full win-table border-collapse" id="banks-table">
                <thead>
                    <tr>
                        <th>Bank Institution</th>
                        <th style="width: 130px;">Bank Code</th>
                        <th style="width: 140px;">SWIFT / BIC</th>
                        <th style="width: 100px; text-align: center;">Status</th>
                        <th style="width: 160px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="banks-table-body">
                    <?php foreach ($banks as $b): ?>
                        <tr id="bank-row-<?= $b['id'] ?>" class="hover:bg-gray-50 transition bank-row cursor-pointer" data-name="<?= strtolower(e($b['bank_name'])) ?>" data-code="<?= strtolower(e($b['bank_code'])) ?>" data-active="<?= $b['is_active'] ?>">
                            <td>
                                <div class="flex items-center space-x-2">
                                    <?php if (!empty($b['logo_path'])): ?>
                                        <img src="/atm/public/<?= e($b['logo_path']) ?>" alt="<?= e($b['bank_name']) ?>" class="w-7 h-7 object-contain rounded border border-gray-200 bg-white p-0.5 shadow-2xs flex-shrink-0">
                                    <?php else: ?>
                                        <div class="w-7 h-7 rounded bg-blue-50 border border-blue-200 text-blue-800 flex items-center justify-center font-bold text-[10px] flex-shrink-0" title="No logo">
                                            <?= e(substr($b['bank_code'] ?: $b['bank_name'], 0, 3)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <span class="font-bold text-gray-900 bank-name-cell"><?= e($b['bank_name']) ?></span>
                                        <span class="text-[10px] text-gray-500 font-mono ml-1">(#<?= $b['id'] ?>)</span>
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs text-blue-900 font-bold bank-code-cell">
                                <?= e($b['bank_code']) ?>
                            </td>
                            <td class="font-mono text-xs text-gray-700 bank-swift-cell">
                                <?= e($b['swift_code'] ?? '—') ?>
                            </td>
                            <td class="text-center bank-status-cell">
                                <?php if ($b['is_active'] == 1): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold bg-gray-100 text-gray-700">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center space-x-1 whitespace-nowrap">
                                <button type="button" onclick='openEditModal(<?= json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="win-btn win-btn-sm text-blue-900">
                                    Edit
                                </button>
                                <button type="button" onclick="toggleStatus(<?= $b['id'] ?>, '<?= e($b['bank_name']) ?>', <?= $b['is_active'] ?>)" class="win-btn win-btn-sm <?= ($b['is_active'] == 1) ? 'text-amber-800' : 'text-green-800' ?>">
                                    <?= ($b['is_active'] == 1) ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Desktop Modal: Add / Edit Bank -->
<div id="bank-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4 modal-backdrop">
    <div class="win-window max-w-md w-full shadow-2xl">
        <div class="win-titlebar">
            <span id="bank-modal-title">Bank Setup</span>
            <span onclick="closeModal('bank-modal')" class="w-4 h-4 flex items-center justify-center text-xs font-bold text-[#1e395b] cursor-pointer hover:bg-red-500 hover:text-white rounded">✕</span>
        </div>

        <form id="bank-form" class="p-4 space-y-3 bg-[#f2f5fa]" enctype="multipart/form-data">
            <input type="hidden" id="bank-id" name="id" value="">
            
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Bank Name *</label>
                <input type="text" id="modal-bank-name" name="bank_name" required placeholder="e.g. Habib Bank Limited" class="win-input w-full">
            </div>

            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Bank Code *</label>
                <input type="text" id="modal-bank-code" name="bank_code" placeholder="e.g. HBL" class="win-input w-full uppercase font-mono">
                <span class="text-[10px] text-gray-500 block mt-0.5">Leave blank to auto-generate from bank initials.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">SWIFT / BIC Code</label>
                <input type="text" id="modal-bank-swift" name="swift_code" placeholder="e.g. HABBPKKA" class="win-input w-full uppercase font-mono">
            </div>

            <!-- Logo Upload Field -->
            <div>
                <label class="block text-xs font-bold text-[#1a385f] uppercase mb-0.5">Bank Brand Logo (PNG / JPG / WEBP)</label>
                <div id="modal-current-logo-container" class="hidden mb-2 items-center space-x-2 p-2 bg-white rounded border border-[#cbd9e8]">
                    <img id="modal-logo-preview" src="" alt="Bank Logo" class="w-8 h-8 object-contain rounded border border-gray-200 bg-white p-0.5">
                    <div class="flex-1 text-xs">
                        <label class="inline-flex items-center cursor-pointer text-red-600 font-semibold">
                            <input type="checkbox" id="modal-remove-logo" name="remove_logo" value="1" class="mr-1">
                            Remove logo
                        </label>
                    </div>
                </div>
                <input type="file" id="modal-bank-logo" name="logo" accept="image/png,image/jpeg,image/webp" class="win-input w-full text-xs">
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-[#cbd9e8]">
                <button type="button" onclick="closeModal('bank-modal')" class="win-btn">Cancel</button>
                <button type="submit" id="submit-bank-btn" class="win-btn font-bold text-purple-900">Save Bank</button>
            </div>
        </form>
    </div>
</div>

<script>
var currentFilter = 'all';

function openAddModal() {
    $('#bank-modal-title').text('Add New Master Bank');
    $('#bank-id').val('');
    $('#modal-bank-name').val('');
    $('#modal-bank-code').val('');
    $('#modal-bank-swift').val('');
    $('#modal-bank-logo').val('');
    $('#modal-remove-logo').prop('checked', false);
    $('#modal-current-logo-container').addClass('hidden').removeClass('flex');
    $('#bank-modal').removeClass('hidden').addClass('flex');
}

function openEditModal(bank) {
    $('#bank-modal-title').text('Edit Master Bank #' + bank.id);
    $('#bank-id').val(bank.id);
    $('#modal-bank-name').val(bank.bank_name);
    $('#modal-bank-code').val(bank.bank_code);
    $('#modal-bank-swift').val(bank.swift_code || '');
    $('#modal-bank-logo').val('');
    $('#modal-remove-logo').prop('checked', false);

    if (bank.logo_path) {
        $('#modal-logo-preview').attr('src', '/atm/public/' + bank.logo_path);
        $('#modal-current-logo-container').removeClass('hidden').addClass('flex');
    } else {
        $('#modal-current-logo-container').addClass('hidden').removeClass('flex');
    }

    $('#bank-modal').removeClass('hidden').addClass('flex');
}

function filterStatus(status) {
    currentFilter = status;
    $('.status-tab').removeClass('bg-purple-700 text-white').addClass('bg-gray-100 text-gray-700');
    $(`.status-tab[data-tab="${status}"]`).removeClass('bg-gray-100 text-gray-700').addClass('bg-purple-700 text-white');
    applyFilters();
}

function applyFilters() {
    var search = $('#bank-search').val().toLowerCase().trim();
    $('.bank-row').each(function() {
        var name = $(this).data('name') || '';
        var code = $(this).data('code') || '';
        var active = String($(this).data('active'));

        var matchesSearch = !search || name.indexOf(search) !== -1 || code.indexOf(search) !== -1;
        var matchesStatus = (currentFilter === 'all') || 
                            (currentFilter === 'active' && active === '1') || 
                            (currentFilter === 'inactive' && active === '0');

        if (matchesSearch && matchesStatus) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

$('#bank-search').on('input', applyFilters);

$('#bank-form').on('submit', function(e) {
    e.preventDefault();
    var id = $('#bank-id').val();
    var isEdit = !!id;
    var url = isEdit ? ('/atm/api/master/banks/' + id + '/update') : '/atm/api/master/banks/add';

    var formData = new FormData();
    formData.append('bank_name', $('#modal-bank-name').val().trim());
    formData.append('bank_code', $('#modal-bank-code').val().trim());
    formData.append('swift_code', $('#modal-bank-swift').val().trim());
    if ($('#modal-remove-logo').is(':checked')) {
        formData.append('remove_logo', '1');
    }
    var fileInput = document.getElementById('modal-bank-logo');
    if (fileInput && fileInput.files.length > 0) {
        formData.append('logo', fileInput.files[0]);
    }

    // Include CSRF Token
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        formData.append('_csrf_token', csrfToken);
    }

    $('#submit-bank-btn').prop('disabled', true);

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': csrfToken || ''
        },
        success: function(res) {
            $('#submit-bank-btn').prop('disabled', false);
            if (res.success) {
                window.showToast(res.message, 'success');
                closeModal('bank-modal');
                setTimeout(function() { window.location.reload(); }, 600);
            } else {
                alert(res.message || 'Error saving bank.');
            }
        },
        error: function(xhr) {
            $('#submit-bank-btn').prop('disabled', false);
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error saving bank.';
            alert(msg);
        }
    });
});

function toggleStatus(id, name, currentActive) {
    var action = (currentActive == 1) ? 'deactivate' : 'activate';
    if (!confirm('Are you sure you want to ' + action + ' bank "' + name + '"?')) {
        return;
    }

    $.post('/atm/api/master/banks/' + id + '/toggle-status', {}, function(res) {
        if (res.success) {
            window.showToast(res.message, 'success');
            setTimeout(function() { window.location.reload(); }, 600);
        } else {
            alert(res.message || 'Error updating bank status.');
        }
    }, 'json').fail(function(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error updating bank status.';
        alert(msg);
    });
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
