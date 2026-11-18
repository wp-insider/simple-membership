<?php
/**
 * Runs every standalone test script in tests/security/ with the current PHP binary.
 * Usage: php tests/bin/run-security-tests.php
 *
 * A script fails when it exits with an error code, prints a FAIL line, a PHP error or warning,
 * or prints no PASS line at all (a script that exits early prints nothing but still returns exit code 0).
 */
$scripts = glob( dirname( __DIR__ ) . '/security/*.php' );
sort( $scripts );
if ( empty( $scripts ) ) {
	echo "No test scripts found in tests/security/.\n";
	exit( 1 );
}

$failed = array();
foreach ( $scripts as $script ) {
	$name   = basename( $script );
	$output = array();
	exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ) . ' 2>&1', $output, $exit_code );
	$text = implode( "\n", $output );

	$pass_count = preg_match_all( '/^PASS\b/m', $text );
	$has_error  = preg_match( '/^(FAIL\b|PHP (Fatal error|Parse error|Warning)|Fatal error|Parse error|Warning:)/m', $text );

	if ( 0 === $exit_code && $pass_count > 0 && ! $has_error ) {
		echo "OK    {$name} ({$pass_count} checks)\n";
		continue;
	}

	$failed[] = $name;
	$reason   = 0 !== $exit_code ? "exit code {$exit_code}" : ( $has_error ? 'error or FAIL output' : 'no PASS output' );
	echo "FAIL  {$name} ({$reason})\n";
	echo '      ' . str_replace( "\n", "\n      ", $text ) . "\n";
}

echo "\n" . ( count( $scripts ) - count( $failed ) ) . ' of ' . count( $scripts ) . " test scripts passed (PHP " . PHP_VERSION . ").\n";
exit( empty( $failed ) ? 0 : 1 );
