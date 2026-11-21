<?php
// The releases of the Exment official plugins (ISS00419, ISS00421 "the rules of the signatures").
//
// The secret key is only on the computer of the person who signs (never on
// GitHub or a server). The CI only makes the zip and the record without the
// signature; the record is signed by hand, here:
//
//   php tools/release.php key release-2026-1 <key-folder>/release-2026-1.key   (once: makes the key, prints the public key for Exment)
//   php tools/release.php sign releases/ExmentITSupport-1.0.6.json <key-folder>/release-2026-1.key
//
// and by the CI:
//
//   php tools/release.php make <zip> <out.json> "<exment_requires>" ["<php_requires>"] [--security "<note>"] [--security-min <version>]
//
// Exment shows and installs only the records with a signature of a key it trusts.

function canon(array $record): string
{
    unset($record['signature']);
    foreach ($record as $k => $v) {
        if (!is_string($v) && !is_int($v) && !is_bool($v) && $v !== null) {
            throw new InvalidArgumentException("$k: only string, int, bool or null");
        }
    }
    ksort($record, SORT_STRING);
    return json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function config_of_zip(string $zipPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        fail("not a zip: $zipPath");
    }
    $found = null;
    $depth = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false || basename($name) !== 'config.json') {
            continue;
        }
        $d = substr_count(str_replace('\\', '/', $name), '/');
        if ($depth === null || $d < $depth) {
            $depth = $d;
            $found = $name;
        }
    }
    $config = $found !== null ? json_decode((string) $zip->getFromName($found), true) : null;
    $zip->close();
    if (!is_array($config) || empty($config['uuid']) || empty($config['version']) || empty($config['plugin_name'])) {
        fail('config.json with plugin_name, uuid and version is needed');
    }
    return $config;
}

function write_json(string $path, array $record): void
{
    file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
}

$command = $argv[1] ?? '';

if ($command === 'key') {
    [$keyId, $keyFile] = [$argv[2] ?? '', $argv[3] ?? ''];
    if (preg_match('/\Arelease-\d{4}-\d+\z/', $keyId) !== 1 || $keyFile === '') {
        fail('usage: php tools/release.php key release-<year>-<n> <key file>');
    }
    if (file_exists($keyFile)) {
        fail("$keyFile is there already: not overwritten");
    }
    $pair = sodium_crypto_sign_keypair();
    file_put_contents($keyFile, json_encode(['key_id' => $keyId, 'secret_key' => base64_encode(sodium_crypto_sign_secretkey($pair))]) . "\n");
    @chmod($keyFile, 0600);
    echo "key file:   $keyFile (keep it on this computer and on an offline copy; never in a repository or a message)\n";
    echo "key_id:     $keyId\n";
    echo "public key: " . base64_encode(sodium_crypto_sign_publickey($pair)) . "  (this goes into Exment: ReleaseVerifier::TRUSTED_KEYS)\n";
    exit(0);
}

if ($command === 'make') {
    $args = array_slice($argv, 2);
    $security = null;
    $securityMin = null;
    foreach (['--security' => 'security', '--security-min' => 'securityMin'] as $flag => $var) {
        $i = array_search($flag, $args, true);
        if ($i !== false) {
            $$var = $args[$i + 1] ?? '';
            array_splice($args, $i, 2);
        }
    }
    [$zipPath, $outPath, $exmentRequires] = [$args[0] ?? '', $args[1] ?? '', $args[2] ?? ''];
    $phpRequires = $args[3] ?? '>=8.2';
    if ($zipPath === '' || $outPath === '' || $exmentRequires === '') {
        fail('usage: php tools/release.php make <zip> <out.json> "<exment_requires>" ["<php_requires>"] [--security "<note>"] [--security-min <version>]');
    }
    $config = config_of_zip($zipPath);
    $record = [
        'format' => 'exment-plugin-release/1',
        'uuid' => strtolower((string) $config['uuid']),
        'name' => (string) $config['plugin_name'],
        'version' => (string) $config['version'],
        'exment_requires' => $exmentRequires,
        'php_requires' => $phpRequires,
        'sha256' => hash_file('sha256', $zipPath),
        'size' => filesize($zipPath),
        'released_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    if ($security !== null) {
        $record['security'] = true;
        $record['security_note'] = mb_substr($security, 0, 500);
        $record['security_min'] = $securityMin ?: $record['version'];
    } elseif ($securityMin !== null && $securityMin !== '') {
        // a fix in a version before: the first safe version carries over
        $record['security'] = false;
        $record['security_min'] = $securityMin;
    }
    write_json($outPath, $record);
    echo "made {$record['name']} {$record['version']} (not signed)\n";
    exit(0);
}

if ($command === 'sign') {
    [$jsonPath, $keyFile] = [$argv[2] ?? '', $argv[3] ?? ''];
    if ($jsonPath === '' || $keyFile === '') {
        fail('usage: php tools/release.php sign <release.json> <key file>');
    }
    $key = json_decode((string) @file_get_contents($keyFile), true);
    $secret = is_array($key) ? base64_decode((string) ($key['secret_key'] ?? ''), true) : false;
    $keyId = is_array($key) ? (string) ($key['key_id'] ?? '') : '';
    if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES || preg_match('/\Arelease-\d{4}-\d+\z/', $keyId) !== 1) {
        fail("not a key file: $keyFile");
    }
    $record = json_decode((string) @file_get_contents($jsonPath), true);
    if (!is_array($record) || ($record['format'] ?? '') !== 'exment-plugin-release/1') {
        fail("not a release record: $jsonPath");
    }
    // the zip next to it must be the one of the record
    $zipPath = substr($jsonPath, 0, -5) . '.zip';
    if (!is_file($zipPath) || !hash_equals((string) $record['sha256'], hash_file('sha256', $zipPath)) || filesize($zipPath) !== $record['size']) {
        fail("the zip does not match the record: $zipPath");
    }
    $config = config_of_zip($zipPath);
    if (strtolower((string) $config['uuid']) !== $record['uuid'] || (string) $config['version'] !== $record['version']) {
        fail('the config.json of the zip does not match the record');
    }
    $record['key_id'] = $keyId;
    $record['signature'] = base64_encode(sodium_crypto_sign_detached(canon($record), $secret));
    if (!sodium_crypto_sign_verify_detached(base64_decode($record['signature'], true), canon($record), sodium_crypto_sign_publickey_from_secretkey($secret))) {
        fail('the signature does not verify');
    }
    sodium_memzero($secret);
    write_json($jsonPath, $record);
    echo "signed {$record['name']} {$record['version']} with $keyId\n";
    exit(0);
}

fail("usage: php tools/release.php key|make|sign ...");
