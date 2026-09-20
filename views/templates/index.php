<!-- views/templates/index.php -->
<?php $pageTitle = 'Template Management'; require __DIR__ . '/../layouts/header.php'; require __DIR__ . '/../layouts/navigation.php'; ?>

<main class="flex-1 max-w-7xl w-full mx-auto px-2 sm:px-4 py-4">
    <!-- Desktop Window Frame for Narration Templates -->
    <div class="win-window mb-6">
        <div class="win-titlebar">
            <div class="flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span>Narration Templates &mdash; Pattern &amp; Field Configurator</span>
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
                Configure database-driven narration patterns and dynamic token fields.
            </div>
            <div class="flex items-center space-x-2">
                <a href="/atm/templates/create" class="win-btn font-bold text-purple-900">
                    + Add New Template
                </a>
            </div>
        </div>

        <!-- Templates Grid Table (Desktop Style) -->
        <div class="overflow-x-auto bg-white">
            <table class="w-full win-table border-collapse">
                <thead>
                    <tr>
                        <th style="width: 140px;">Code</th>
                        <th style="width: 200px;">Template Name</th>
                        <th>Pattern</th>
                        <th style="width: 70px; text-align: center;">Version</th>
                        <th style="width: 80px; text-align: center;">Fields</th>
                        <th style="width: 90px; text-align: center;">Status</th>
                        <th style="width: 120px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="templates-table-body">
                    <?php foreach ($templates as $tpl): ?>
                        <tr class="hover:bg-gray-50 transition cursor-pointer">
                            <td class="font-mono font-bold text-blue-900 text-xs">
                                <?= e($tpl['code']) ?>
                            </td>
                            <td>
                                <div class="font-bold text-gray-900"><?= e($tpl['name']) ?></div>
                                <div class="text-[10px] text-gray-500 capitalize"><?= e($tpl['category']) ?></div>
                            </td>
                            <td class="text-xs font-mono text-gray-700 max-w-md truncate" title="<?= e($tpl['pattern']) ?>">
                                <?= e($tpl['pattern']) ?>
                            </td>
                            <td class="text-center font-mono text-xs text-gray-700 font-bold">
                                v<?= $tpl['version'] ?>
                            </td>
                            <td class="text-center text-xs font-bold text-blue-800">
                                <?= $tpl['fields_count'] ?> fields
                            </td>
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded uppercase <?= $tpl['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= $tpl['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <a href="/atm/templates/<?= $tpl['id'] ?>/edit" class="win-btn win-btn-sm text-blue-900 font-bold">
                                    Edit &amp; Fields
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
