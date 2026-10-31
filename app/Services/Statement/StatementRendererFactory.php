<?php
// app/Services/Statement/StatementRendererFactory.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/StandardStatementRenderer.php';
require_once __DIR__ . '/HBLStatementRenderer.php';
require_once __DIR__ . '/OnlineStatementRenderer.php';
require_once __DIR__ . '/FaysalStatementRenderer.php';
require_once __DIR__ . '/AlliedStatementRenderer.php';
require_once __DIR__ . '/MeezanStatementRenderer.php';
require_once __DIR__ . '/HabibMetroStatementRenderer.php';
require_once __DIR__ . '/BankIslamiStatementRenderer.php';
require_once __DIR__ . '/UBLStatementRenderer.php';

class StatementRendererFactory {
    /**
     * Map of format codes to renderer classes.
     * HBL layout is fully implemented based on authentic client sample.
     * Faysal Bank layout is fully implemented based on authentic client photo.
     * Allied Bank layout is fully implemented based on authentic client scan.
     * Meezan Bank layout is fully implemented based on authentic client statement.
     * Habib Metropolitan Bank layout is fully implemented based on authentic client statement.
     * BankIslami Pakistan layout is fully implemented based on authentic client statement.
     * UBL Ameen Islamic Banking layout is fully implemented based on authentic client statement.
     * Online layout is fully implemented for digital e-statements.
     * MCB remains deferred awaiting sample layout.
     */
    private static array $renderers = [
        'standard' => StandardStatementRenderer::class,
        'hbl' => HBLStatementRenderer::class,
        'online' => OnlineStatementRenderer::class,
        'faysal' => FaysalStatementRenderer::class,
        'faisal' => FaysalStatementRenderer::class,
        'allied' => AlliedStatementRenderer::class,
        'abl' => AlliedStatementRenderer::class,
        'meezan' => MeezanStatementRenderer::class,
        'mezn' => MeezanStatementRenderer::class,
        'habibmetro' => HabibMetroStatementRenderer::class,
        'hmb' => HabibMetroStatementRenderer::class,
        'metro' => HabibMetroStatementRenderer::class,
        'habib_metro' => HabibMetroStatementRenderer::class,
        'bankislami' => BankIslamiStatementRenderer::class,
        'islami' => BankIslamiStatementRenderer::class,
        'bipl' => BankIslamiStatementRenderer::class,
        'ubl' => UBLStatementRenderer::class,
        'ubl_ameen' => UBLStatementRenderer::class,
        'ameen' => UBLStatementRenderer::class
    ];

    /**
     * List of deferred formats pending client specifications.
     */
    private static array $deferredFormats = [
        'mcb' => 'MCB Bank Layout (Deferred — awaiting client sample layout)'
    ];

    public static function create(string $formatCode = 'standard'): StatementRendererInterface {
        $formatCode = strtolower(trim($formatCode));

        if (isset(self::$renderers[$formatCode])) {
            $class = self::$renderers[$formatCode];
            return new $class();
        }

        // For deferred or unknown formats, fall back gracefully to the Standard renderer
        // while preserving system stability and BCMath financial calculations.
        return new StandardStatementRenderer();
    }

    public static function isDeferred(string $formatCode): bool {
        return isset(self::$deferredFormats[strtolower(trim($formatCode))]);
    }

    public static function getDeferredReason(string $formatCode): ?string {
        return self::$deferredFormats[strtolower(trim($formatCode))] ?? null;
    }

    public static function registerRenderer(string $formatCode, string $rendererClass): void {
        if (!is_subclass_of($rendererClass, StatementRendererInterface::class)) {
            throw new InvalidArgumentException("Class {$rendererClass} must implement StatementRendererInterface.");
        }
        self::$renderers[strtolower(trim($formatCode))] = $rendererClass;
    }
}
