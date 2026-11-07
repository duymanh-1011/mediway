<?php
declare(strict_types=1);
require __DIR__.'/F08V2InstancePolicy.php';
require __DIR__.'/F08V2Approval.php';

// REVIEW PRE-FLIGHT ONLY: connects to a separately reviewed instance and sends
// SELECT/SHOW only. No CREATE DATABASE, migration, grant or fixture execution.
$stage='OWNER_DOCUMENT';
try {
    if ($argc!==3 || !is_file($argv[1]) || !preg_match('/^[a-f0-9]{64}$/D',$argv[2])) throw new RuntimeException('Missing reviewed document');
    $raw=file_get_contents($argv[1]);
    if ($raw===false || !hash_equals($argv[2],hash('sha256',$raw))) throw new RuntimeException('Document mismatch');
    $plan=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    if (!is_array($plan) || ($plan['purpose'] ?? null)!=='F08_V2_INDEPENDENT_INSTANCE_REVIEW') throw new RuntimeException('Wrong review purpose');
    if (empty(getenv('F08_V2_METADATA_USER')) || getenv('F08_V2_METADATA_PASSWORD')===false
        || getenv('F08_V2_METADATA_USER')!=='f08_v2_metadata') throw new RuntimeException('Metadata credentials required');
    // Validate immutable migration source before opening a connection.
    $stage='MIGRATION_HASH';
    if (!is_array($plan['migrations'] ?? null)) throw new RuntimeException('Missing migration manifest');
    F08V2Approval::verifyMigrationFiles($plan,dirname(__DIR__,2).'/database/migrations');
    if (($plan['host'] ?? null)!=='127.0.0.1' || ($plan['port'] ?? null)!==3307) throw new RuntimeException('Wrong endpoint');
    $stage='METADATA_CONNECTION';
    $pdo=new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4',getenv('F08_V2_METADATA_USER'),getenv('F08_V2_METADATA_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>5]);
    $datadir=$pdo->query('SELECT @@datadir')->fetchColumn();
    $real=is_string($datadir)?realpath($datadir):false;
    $planned=isset($plan['datadir'])?realpath((string)$plan['datadir']):false;
    if ($real===false || $planned===false) throw new RuntimeException('Cannot resolve directories');
    $plan['real_datadir']=$planned;
    $state=['uuid'=>$pdo->query('SELECT @@server_uuid')->fetchColumn(),'port'=>(int)$pdo->query('SELECT @@port')->fetchColumn(),
        'current_user'=>$pdo->query('SELECT CURRENT_USER()')->fetchColumn(),'role'=>$pdo->query('SELECT CURRENT_ROLE()')->fetchColumn(),
        'real_datadir'=>$real,'schemas'=>$pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN),
        'grants'=>$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN)];
    $stage='INSTANCE_POLICY';
    F08V2InstancePolicy::validate($plan,$state);
    echo "READ_ONLY_INSTANCE_CHECK_PASS; owner approval, migration scope and post-provisioning remain required\n";
} catch (Throwable) {
    fwrite(STDERR,"BLOCKED at {$stage}; inspect review configuration privately. No provisioning was executed.\n");
    exit(1);
}
