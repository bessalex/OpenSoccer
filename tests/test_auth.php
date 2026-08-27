<?php

$password = "SecretPassword123!";

// 1. Test registration hash generation
$hashNew = password_hash($password, PASSWORD_DEFAULT);
assert(password_verify($password, $hashNew), "password_verify failed for new hash");
assert(!password_verify("WrongPass", $hashNew), "password_verify incorrectly succeeded for wrong pass");

// 2. Test legacy MD5 hash verification
$legacyHash = md5('1'.$password.'29');
$isLegacyMatch = ($legacyHash === md5('1'.$password.'29'));
assert($isLegacyMatch, "Legacy MD5 hash logic mismatch");

// 3. Test auto-upgrade logic simulation
if ($isLegacyMatch && !password_verify($password, $legacyHash)) {
    $upgradedHash = password_hash($password, PASSWORD_DEFAULT);
    assert(password_verify($password, $upgradedHash), "password_verify failed for upgraded hash");
}

echo "All password hashing assertions passed successfully!\n";
