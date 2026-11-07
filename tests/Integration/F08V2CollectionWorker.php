<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2WorkerGate.php';
require_once __DIR__.'/F08V2Registry.php';

/** Library only: there is deliberately no CLI entrypoint or enabled runner. */
final class F08V2CollectionWorker
{
    public static function collect(array $env, int $payableId, int $employeeId): int
    {
        // Fail closed until synthetic seeding, registry provenance and transaction
        // behaviour have been reviewed and verified on an isolated MySQL schema.
        throw new LogicException('BLOCKED: V2 worker is not yet approved for database writes');

        $config = F08V2Safety::validateConnection($env, 'f08_worker_v2');
        if ($payableId <= 0 || $employeeId <= 0) throw new InvalidArgumentException('Invalid fixture ID');
        $db = Illuminate\Support\Facades\DB::connection();
        if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== $config['schema']
            || $db->getConfig('host') !== '127.0.0.1' || (int) $db->getConfig('port') !== 3306
            || $db->getConfig('username') !== $env['DB_USERNAME']
            || !hash_equals($env['DB_PASSWORD'], (string) $db->getConfig('password'))
            || $db->getConfig('unix_socket') || $db->transactionLevel() !== 0) {
            throw new RuntimeException('Unsafe effective Laravel connection');
        }
        $pdo = $db->getPdo();
        $role = $pdo->query('SELECT CURRENT_ROLE()')->fetchColumn();
        $partial = $pdo->query('SELECT @@GLOBAL.partial_revokes')->fetchColumn();
        if (!in_array((string) $partial, ['0', '1'], true)) throw new RuntimeException('Unknown MySQL grant semantics');
        $state = ['database'=>$pdo->query('SELECT DATABASE()')->fetchColumn(),
            'user'=>$pdo->query('SELECT CURRENT_USER()')->fetchColumn(), 'role'=>$role,
            'partial_revokes'=>(int) $partial, 'grants'=>$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN)];

        // Laravel uses nested savepoints for the service transaction. The outer
        // transaction owns the registry locks and makes the postconditions atomic.
        // On any error, including a deadlock in a nested transaction, fail closed;
        // retry behaviour and lock ordering must be verified on isolated MySQL.
        return $db->transaction(function () use ($pdo, $db, $state, $env, $config, $payableId, $employeeId): int {
            F08V2Registry::lockEntity($pdo, $config['run_id'], 'payable', $payableId);
            F08V2Registry::lockEntity($pdo, $config['run_id'], 'employee', $employeeId);
            $ownerQuery = $pdo->prepare("SELECT run_id FROM acceptance_fixtures WHERE entity_type = 'payable' AND entity_id = ?");
            F08V2WorkerGate::authorize($env, $payableId, static function ($key, $id) use ($state, $ownerQuery) {
                if ($key === 'connection') return $state;
                $ownerQuery->execute([$id]);
                return $ownerQuery->fetchColumn();
            });
            $payable = App\Models\Payable::with('medicalVisit')->findOrFail($payableId);
            F08V2Registry::assertExamPayable($payable->getAttributes());
            $visit = $payable->medicalVisit;
            if (!$visit) throw new RuntimeException('Missing fixture visit');
            F08V2Registry::lockEntity($pdo, $config['run_id'], 'visit', (int) $visit->id);
            F08V2Registry::lockEntity($pdo, $config['run_id'], 'patient', (int) $visit->patient_id);
            if ($visit->appointment_id) F08V2Registry::lockEntity($pdo, $config['run_id'], 'appointment', (int) $visit->appointment_id);
            $employee = App\Models\Employee::with('user')->findOrFail($employeeId);
            if (!$employee->user) throw new RuntimeException('Missing fixture cashier user');
            F08V2Registry::lockEntity($pdo, $config['run_id'], 'user', (int) $employee->user->id);
            Illuminate\Support\Facades\Auth::login($employee->user);
            try {
                $request = Illuminate\Http\Request::create('/f08-v2-test', 'POST');
                $tx = app(App\Services\PaymentCollectionService::class)->collect($request, $payable, [
                    'payment_method'=>'CASH', 'collected_by_employee_id'=>$employeeId,
                ]);
                if ((int) $tx->payable_id !== $payableId || (int) $tx->collected_by_employee_id !== $employeeId
                    || $tx->transaction_result !== 'SUCCESS'
                    || $db->table('payment_transactions')->where('payable_id', $payableId)->where('transaction_result', 'SUCCESS')->count() !== 1
                    || $db->table('audit_logs')->where('entity_type', 'payment_transactions')->where('entity_id', $tx->id)->where('user_id', $employee->user->id)->count() !== 1
                    || $db->table('examination_room_queues')->where('medical_visit_id', $visit->id)->where('queue_type', 'INITIAL_EXAM')->count() > 1) {
                    throw new RuntimeException('Payment fixture postcondition failed');
                }
                return (int) $tx->id;
            } finally {
                Illuminate\Support\Facades\Auth::logout();
            }
        }, 1);
    }
}
