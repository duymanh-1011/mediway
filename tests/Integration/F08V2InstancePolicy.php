<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2Safety.php';

/** Offline policy; approval is a human process, not established by this code. */
final class F08V2InstancePolicy
{
    public static function validate(array $plan, array $state): void
    {
        $schema=$plan['schema'] ?? '';
        if (!is_string($schema) || !preg_match('/^f08_acceptance_[a-f0-9]{16}$/D',$schema)
            || ($plan['host'] ?? null)!=='127.0.0.1' || ($plan['port'] ?? null)!==3307
            || ($plan['metadata_user'] ?? null)!=='f08_v2_metadata'
            || !is_string($plan['owner_review_reference'] ?? null) || trim($plan['owner_review_reference'])===''
            || !preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D',(string)($plan['server_uuid'] ?? ''))
            || ($state['uuid'] ?? null)!==$plan['server_uuid'] || ($state['port'] ?? null)!==3307
            || ($state['current_user'] ?? null)!=='f08_v2_metadata@localhost'
            || !array_key_exists('role', $state) || !in_array($state['role'],[null,'NONE'],true)) throw new RuntimeException('Unsafe or unreviewed instance identity');
        $data=$state['real_datadir'] ?? null; $planned=$plan['real_datadir'] ?? null;
        $normalized=is_string($data) ? rtrim(strtolower(str_replace('\\','/',$data)),'/') : '';
        if (!is_string($data) || !is_string($planned) || $data==='' || strcasecmp($data,$planned)!==0
            || $normalized==='c:/laragon/data' || str_starts_with($normalized, 'c:/laragon/data/')) throw new RuntimeException('Unsafe data directory');
        $schemas=$state['schemas'] ?? null;
        if (!is_array($schemas)) throw new RuntimeException('Schema visibility missing');
        foreach ($schemas as $name) {
            if (!is_string($name) || !in_array($name,['mysql','information_schema','performance_schema','sys'],true)) throw new RuntimeException('Unexpected schema on isolated instance');
        }
        $grants=$state['grants'] ?? null; $canList=false;
        if (!is_array($grants) || !$grants) throw new RuntimeException('Missing metadata grants');
        $account='`f08_v2_metadata`@`localhost`';
        foreach ($grants as $grant) {
            if ($grant==='GRANT USAGE ON *.* TO '.$account) continue;
            if ($grant==='GRANT SHOW DATABASES ON *.* TO '.$account) { $canList=true; continue; }
            throw new RuntimeException('Metadata account is not read-only');
        }
        if (!$canList) throw new RuntimeException('Cannot prove schema-list visibility');
    }
}
