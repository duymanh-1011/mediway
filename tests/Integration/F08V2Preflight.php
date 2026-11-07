<?php
declare(strict_types=1);
require __DIR__.'/F08V2Approval.php';
require __DIR__.'/F08V2ReadOnlyPreflight.php';

// Only process environment is read; Laravel/.env is never loaded here.
$stage='OWNER_APPROVAL_FILE';
try {
    if ($argc!==3 || !is_file($argv[1]) || !is_readable($argv[1])) throw new RuntimeException('Missing owner-reviewed manifest or digest');
    $contents=file_get_contents($argv[1]);
    if ($contents===false) throw new RuntimeException('Cannot read manifest');
    $keys=['F08_V2_SCHEMA','F08_V2_APPROVED_SCHEMA','F08_V2_RUN_ID','F08_V2_DB_USERNAME','F08_V2_DB_PASSWORD',
        'DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DB_URL','DB_SOCKET'];
    $env=[];
    foreach($keys as $key) { $value=getenv($key); if($value!==false) $env[$key]=$value; }
    $stage='CONFIG_AND_OWNER_MANIFEST';
    $manifest=F08V2Approval::validate($contents,$argv[2],$env);
    $stage='MIGRATION_FILE_DIGESTS';
    F08V2Approval::verifyMigrationFiles($manifest,dirname(__DIR__,2).'/database/migrations');
    $config=F08V2Safety::validateConnection($env);
    $stage='MYSQL_CONNECT';
    $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname='.$config['schema'].';charset=utf8mb4',
        $env['DB_USERNAME'],$env['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>5,PDO::ATTR_EMULATE_PREPARES=>false]);
    F08V2ReadOnlyPreflight::inspect($pdo,$env,$manifest['tables'],array_keys($manifest['migrations']),$manifest['foreign_keys'],static function(string $name) use (&$stage): void { $stage=$name; });
    echo "PREFLIGHT_READONLY_PASS; registry provenance and worker grants remain UNVERIFIED\n";
} catch (Throwable $e) {
    // SQL errors can contain secrets. Do not echo connection exceptions or manifest data.
    fwrite(STDERR,"BLOCKED at {$stage}; inspect configuration privately.\n");
    exit(1);
}
