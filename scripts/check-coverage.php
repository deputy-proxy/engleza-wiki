<?php

declare(strict_types=1);

$reportPath = $argv[1] ?? null;

if (! is_string($reportPath) || ! is_file($reportPath)) {
    fwrite(STDERR, "Coverage report not found. Run the test suite with --coverage-clover first.\n");
    exit(2);
}

$report = simplexml_load_file($reportPath);
$metrics = $report?->project?->metrics;

if (! $metrics instanceof SimpleXMLElement) {
    fwrite(STDERR, "Coverage report does not contain project metrics.\n");
    exit(2);
}

$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];
$methods = (int) $metrics['methods'];
$coveredMethods = (int) $metrics['coveredmethods'];

$statementCoverage = $statements > 0 ? ($coveredStatements / $statements) * 100 : 0.0;
$methodCoverage = $methods > 0 ? ($coveredMethods / $methods) * 100 : 0.0;

$minimumStatements = 75.0;
$minimumMethods = 60.0;

$summary = sprintf(
    "PHP coverage: **%.2f%% statements** (%d/%d), **%.2f%% methods** (%d/%d). Floors: %.0f%% statements, %.0f%% methods.\n",
    $statementCoverage,
    $coveredStatements,
    $statements,
    $methodCoverage,
    $coveredMethods,
    $methods,
    $minimumStatements,
    $minimumMethods,
);

echo $summary;

$summaryPath = getenv('GITHUB_STEP_SUMMARY');
if (is_string($summaryPath) && $summaryPath !== '') {
    file_put_contents($summaryPath, "## PHP test coverage\n\n".$summary, FILE_APPEND);
}

if ($statementCoverage < $minimumStatements || $methodCoverage < $minimumMethods) {
    fwrite(STDERR, "Coverage is below the documented baseline floors. Add meaningful tests; do not lower the floors to silence this check.\n");
    exit(1);
}
