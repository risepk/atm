<?php
// app/Services/Statement/StatementRendererFactory.php

require_once __DIR__ . '/StatementRendererInterface.php';
require_once __DIR__ . '/StandardStatementRenderer.php';
require_once __DIR__ . '/HBLStatementRenderer.php';
require_once __DIR__ . '/OnlineStatementRenderer.php';
require_once __DIR__ . '/FaysalStatementRenderer.php';

class StatementRendererFactory {
    /**
     * Map of format codes to renderer classes.
     * HBL layout is fully implemented based on authentic client sample.
     * Faysal Bank layout is fully implemented based on authentic client photo.
     * Online layout is fully implemented for digital e-statements.
     * Meezan, UBL, and MCB remain deferred awaiting sample layouts.
     */
    private static array $renderers = [
        'standard' => StandardStatementRenderer::class,
        'hbl' => HBLStatementRenderer::class,
        'online' => OnlineStatementRenderer::class,
        'faysal' => FaysalStatementRenderer::class,
        'faisal' => FaysalStatementRenderer::class
    ];

    /**
     * List of deferred formats pending client specifications.
     */
    private static array $deferredFormats = [
        'meezan' => 'Meezan Bank Layout (Deferred — awaiting client sample layout)',
        'ubl' => 'United Bank Limited Layout (Deferred — awaiting client sample layout)',
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
