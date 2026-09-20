<?php
// app/Services/Statement/StatementRendererInterface.php

interface StatementRendererInterface {
    /**
     * Render statement HTML markup for in-app display.
     */
    public function renderHtml(array $statementData): string;

    /**
     * Render printable statement HTML document suitable for browser printing / PDF export.
     */
    public function renderPrint(array $statementData): string;

    /**
     * Return the unique format code supported by this renderer.
     */
    public function getSupportedFormatCode(): string;

    /**
     * Return the human-readable format display name.
     */
    public function getFormatName(): string;
}
