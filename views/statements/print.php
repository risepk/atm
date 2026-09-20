<!-- views/statements/print.php -->
<?php
require_once __DIR__ . '/../../app/Services/Statement/StatementRendererFactory.php';

$formatCode = $statement['format']['format_code'] ?? 'standard';
$renderer = StatementRendererFactory::create($formatCode);
echo $renderer->renderPrint($statement);
