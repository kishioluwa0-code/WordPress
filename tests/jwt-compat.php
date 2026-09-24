<?php

declare(strict_types=1);

require dirname(__DIR__) . '/project/school-management-pro/includes/vendor/autoload.php';

$test_key = str_repeat('k', 32);
$token = Firebase\JWT\JWT::encode(
	array('iss' => 'edutech-security-test', 'exp' => time() + 60),
	$test_key,
	'HS256'
);

if (!is_string($token) || substr_count($token, '.') !== 2) {
    fwrite(STDERR, "JWT compatibility test failed.\n");
    exit(1);
}

echo "JWT compatibility test passed.\n";
