<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2Safety.php';

/** A separately reviewed file and independently supplied digest are required. */
final class F08V2Approval
{
    public static function validate(string $contents, string $digest, array $env): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D',$digest) || !hash_equals($digest,hash('sha256',$contents))) throw new RuntimeException('Approval digest mismatch');
        $manifest=json_decode($contents,true,512,JSON_THROW_ON_ERROR);
        $config=F08V2Safety::validateConnection($env);
        if (!is_array($manifest) || ($manifest['purpose'] ?? null)!=='F08_V2_READONLY_PREFLIGHT'
            || ($manifest['schema'] ?? null)!==$config['schema'] || ($manifest['run_id'] ?? null)!==$config['run_id']
            || ($manifest['fixture_account'] ?? null)!=='f08_runner_v2' || ($manifest['worker_account'] ?? null)!=='f08_worker_v2'
            || !is_string($manifest['reviewed_by'] ?? null) || trim($manifest['reviewed_by'])==='') throw new RuntimeException('Invalid approval identity');
        foreach (['tables','foreign_keys'] as $key) {
            if (!isset($manifest[$key]) || !is_array($manifest[$key]) || !array_is_list($manifest[$key]) || !$manifest[$key]
                || count($manifest[$key])!==count(array_unique($manifest[$key],SORT_REGULAR))) throw new RuntimeException('Invalid manifest list');
            foreach ($manifest[$key] as $entry) {
                $pattern=$key==='tables'?'/^[a-z0-9_]+$/D':'/^[a-z0-9_]+\.[a-z0-9_]+>[a-z0-9_]+\.[a-z0-9_]+$/D';
                if (!is_string($entry) || !preg_match($pattern,$entry)) throw new RuntimeException('Invalid manifest item');
            }
        }
        if (!isset($manifest['migrations']) || !is_array($manifest['migrations']) || !$manifest['migrations']) throw new RuntimeException('Missing migration manifest');
        foreach ($manifest['migrations'] as $name=>$hash) {
            if (!is_string($name) || !preg_match('/^[a-z0-9_]+$/D',$name) || !is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D',$hash)) throw new RuntimeException('Invalid migration digest');
        }
        return $manifest;
    }

    public static function verifyMigrationFiles(array $manifest, string $directory): void
    {
        $files=glob(rtrim($directory,'/\\').'/*.php');
        if ($files===false) throw new RuntimeException('Cannot inspect migration directory');
        $actual=[];
        foreach ($files as $file) {
            $name=pathinfo($file,PATHINFO_FILENAME);
            $digest=hash_file('sha256',$file);
            if ($digest===false) throw new RuntimeException('Cannot hash migration');
            $actual[$name]=$digest;
        }
        $expected=$manifest['migrations']; ksort($actual); ksort($expected);
        if ($actual!==$expected) throw new RuntimeException('Migration files differ from approved manifest');
    }
}
