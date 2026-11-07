<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2Safety.php';

/** Minimal transaction API; a MySQL implementation must use one connection. */
interface F08V2FixtureStore
{
    public function transaction(callable $work): mixed;
    public function insert(string $table, array $values): int;
    public function register(string $run, string $type, int $id, string $time): void;
}

/** Synthetic records only. No CLI entrypoint; V2 runner remains disabled. */
final class F08V2SyntheticFixture
{
    public static function validatePaid(array $payable, array $transaction, array $audit, int $payableId, int $transactionId, int $employeeId, int $userId): void
    {
        $snapshot = json_decode((string) ($audit['new_values'] ?? ''), true);
        if (($payable['amount'] ?? null) !== ($transaction['amount'] ?? null)
            || ($transaction['payment_method'] ?? null) !== 'CASH'
            || ($transaction['transaction_result'] ?? null) !== 'SUCCESS'
            || ($transaction['payable_id'] ?? null) !== $payableId
            || ($transaction['collected_by_employee_id'] ?? null) !== $employeeId
            || ($audit['user_id'] ?? null) !== $userId
            || ($audit['entity_type'] ?? null) !== 'payment_transactions'
            || ($audit['entity_id'] ?? null) !== $transactionId
            || ($audit['action'] ?? null) !== 'CREATE'
            || ($audit['role_name_snapshot'] ?? null) !== 'Employee'
            || ($audit['entity_business_code'] ?? null) !== ($transaction['transaction_code'] ?? null)
            || !is_array($snapshot) || ($snapshot['payable_id'] ?? null) !== $payableId
            || ($snapshot['amount'] ?? null) !== ($transaction['amount'] ?? null)
            || ($snapshot['payment_method'] ?? null) !== 'CASH'
            || ($snapshot['transaction_result'] ?? null) !== 'SUCCESS'
            || ($snapshot['collected_by_employee_id'] ?? null) !== $employeeId) {
            throw new RuntimeException('Inconsistent synthetic PAID scenario');
        }
    }

    public static function create(array $env, F08V2FixtureStore $db, string $day): array
    {
        $config = F08V2Safety::validateConnection($env);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day, new DateTimeZone('Asia/Ho_Chi_Minh'));
        if (!$date || $date->format('Y-m-d') !== $day) throw new InvalidArgumentException('Invalid fixture day');
        $run = $config['run_id'];
        $tag = substr($run, 0, 16);
        $phonePrefix = '0'.str_pad((string) (hexdec(substr($run, 0, 8)) % 100000000), 8, '0', STR_PAD_LEFT);
        $at = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s');
        $timestamps = ['created_at'=>$at, 'updated_at'=>$at];
        return $db->transaction(function () use ($db, $run, $tag, $phonePrefix, $date, $timestamps, $at): array {
            // The store inserts the run and all registry rows on this same transaction/connection.
            $db->insert('acceptance_runs', ['run_id'=>$run, 'status'=>'ACTIVE', 'source'=>'f08_v2_synthetic', 'created_at'=>$at]);
            $insert = static function (string $table, string $type, array $data) use ($db, $run, $timestamps, $at): int {
                $id = $db->insert($table, $data + $timestamps);
                if ($id <= 0) throw new RuntimeException('Fixture insert did not return an ID');
                $db->register($run, $type, $id, $at);
                return $id;
            };
            $role = $insert('roles','role',['name'=>'Employee','description'=>'F08 synthetic '.$run,'status'=>'ACTIVE']);
            $doctorRole = $insert('roles','role',['name'=>'Doctor','description'=>'F08 synthetic '.$run,'status'=>'ACTIVE']);
            $specialty = $insert('specialties','specialty',['specialty_code'=>'S'.$tag,'name'=>'F08 synthetic '.$run,'status'=>'ACTIVE']);
            $room = $insert('examination_rooms','room',['room_code'=>'R'.$tag,'name'=>'F08 synthetic '.$run,'status'=>'ACTIVE','specialty_id'=>$specialty]);
            $cashiers = [];
            foreach (['A','B'] as $suffix) {
                $user = $insert('users','user',['business_code'=>'U'.$tag.$suffix,'phone'=>$phonePrefix.($suffix==='A' ? '1' : '2'),'password'=>password_hash($run.$suffix, PASSWORD_BCRYPT),'role_id'=>$role,'status'=>'ACTIVE']);
                $employee = $insert('employees','employee',['user_id'=>$user,'employee_code'=>'E'.$tag.$suffix,'full_name'=>'F08 synthetic cashier '.$suffix.' '.$run,'status'=>'ACTIVE']);
                $insert('employee_details','employee_detail',['employee_id'=>$employee,'employee_type'=>'CASHIER']);
                $cashiers[$suffix] = ['user_id'=>$user,'employee_id'=>$employee];
            }
            $patient = $insert('patients','patient',['user_id'=>null,'patient_code'=>'P'.$tag,'full_name'=>'F08 synthetic patient '.$run,'status'=>'ACTIVE']);
            $doctorUser = $insert('users','user',['business_code'=>'U'.$tag.'D','phone'=>$phonePrefix.'3','password'=>password_hash($run.'D', PASSWORD_BCRYPT),'role_id'=>$doctorRole,'status'=>'ACTIVE']);
            $doctor = $insert('doctors','doctor',['user_id'=>$doctorUser,'doctor_code'=>'D'.$tag,'full_name'=>'F08 synthetic doctor '.$run,'status'=>'ACTIVE','specialty_id'=>$specialty]);
            $appointmentStatus = $insert('appointment_statuses','appointment_status',['name'=>'CHECKED_IN','process_order'=>1,'allows_transition'=>1,'is_active'=>1]);
            $visitStatus = $insert('visit_statuses','visit_status',['name'=>'WAITING','process_order'=>1,'allows_transition'=>1,'is_active'=>1]);
            $insert('queue_statuses','queue_status',['name'=>'WAITING','process_order'=>1,'allows_transition'=>1,'is_active'=>1]);
            $statuses=[];
            foreach (['UNPAID','PAID','CANCELLED'] as $index=>$name) {
                $statuses[$name] = $insert('payment_statuses','payment_status',['name'=>$name,'allows_service_execution'=>$name==='PAID' ? 1 : 0,'process_order'=>$index+1,'is_active'=>1]);
            }
            $fixtures=[];
            foreach (['unpaid_today'=>['UNPAID',0], 'paid_today'=>['PAID',0], 'cancelled_today'=>['CANCELLED',0], 'unpaid_future'=>['UNPAID',1], 'unpaid_past'=>['UNPAID',-1]] as $label=>[$status,$offset]) {
                $fixtureDay = $date->modify(($offset >= 0 ? '+' : '').$offset.' days')->format('Y-m-d');
                $suffix = substr(hash('sha256', $run.$label), 0, 8);
                $start = match ($label) { 'paid_today'=>'09:00:00', 'cancelled_today'=>'10:00:00', default=>'08:00:00' };
                $end = match ($label) { 'paid_today'=>'10:00:00', 'cancelled_today'=>'11:00:00', default=>'09:00:00' };
                $schedule = $insert('doctor_schedules','schedule',['doctor_id'=>$doctor,'specialty_id'=>$specialty,'examination_room_id'=>$room,'work_date'=>$fixtureDay,'start_time'=>$start,'end_time'=>$end,'status'=>'ACTIVE']);
                $slot = $insert('appointment_time_slots','slot',['doctor_schedule_id'=>$schedule,'start_time'=>$start,'end_time'=>$end,'max_patients'=>1,'status'=>'ACTIVE']);
                $appointment = $insert('appointments','appointment',['appointment_code'=>'A'.$tag.$suffix,'patient_id'=>$patient,'doctor_id'=>$doctor,'specialty_id'=>$specialty,'examination_room_id'=>$room,'appointment_date'=>$fixtureDay,'appointment_time_slot_id'=>$slot,'appointment_status_id'=>$appointmentStatus,'checkin_token'=>'F08-'.$run.'-'.$suffix,'booked_at'=>$fixtureDay.' 07:00:00']);
                $visit = $insert('medical_visits','visit',['visit_code'=>'V'.$tag.$suffix,'appointment_id'=>$appointment,'patient_id'=>$patient,'doctor_id'=>$doctor,'specialty_id'=>$specialty,'examination_room_id'=>$room,'visit_status_id'=>$visitStatus,'checked_in_at'=>$fixtureDay.' '.$start,'reception_source'=>'COUNTER']);
                $payableData = ['payable_code'=>'P'.$tag.$suffix,'medical_visit_id'=>$visit,'service_order_item_id'=>null,'payment_type'=>'EXAMINATION_FEE','description'=>'F08 synthetic '.$run,'amount'=>'150000.00','payment_status_id'=>$statuses[$status],'generated_at'=>$at,'paid_at'=>$status==='PAID' ? $at : null];
                $payable = $insert('payables','payable',$payableData);
                $transaction = null; $audit = null;
                if ($status==='PAID') {
                    $transactionData = ['transaction_code'=>'T'.$tag.$suffix,'payable_id'=>$payable,'amount'=>'150000.00','payment_method'=>'CASH','transaction_result'=>'SUCCESS','started_at'=>$at,'paid_at'=>$at,'confirmed_at'=>$at,'collected_by_employee_id'=>$cashiers['A']['employee_id']];
                    $transaction = $insert('payment_transactions','payment_transaction',$transactionData);
                    $auditData = ['user_id'=>$cashiers['A']['user_id'], 'role_name_snapshot'=>'Employee',
                        'action'=>'CREATE', 'entity_type'=>'payment_transactions', 'entity_id'=>$transaction,
                        'entity_business_code'=>'T'.$tag.$suffix, 'old_values'=>'{}',
                        'new_values'=>json_encode(['payable_id'=>$payable,'amount'=>'150000.00','payment_method'=>'CASH','transaction_result'=>'SUCCESS','collected_by_employee_id'=>$cashiers['A']['employee_id']], JSON_THROW_ON_ERROR),
                        'description'=>'F08 synthetic '.$run, 'performed_at'=>$at, 'created_at'=>$at];
                    self::validatePaid($payableData, $transactionData, $auditData, $payable, $transaction, $cashiers['A']['employee_id'], $cashiers['A']['user_id']);
                    $audit = $db->insert('audit_logs', $auditData);
                    if ($audit <= 0) throw new RuntimeException('Fixture audit insert did not return an ID');
                    $db->register($run, 'audit_log', $audit, $at);
                }
                $fixtures[$label] = ['appointment_id'=>$appointment,'medical_visit_id'=>$visit,'payable_id'=>$payable,'transaction_id'=>$transaction,'audit_id'=>$audit];
            }
            return ['run_id'=>$run,'user_id'=>$cashiers['A']['user_id'],'employee_id'=>$cashiers['A']['employee_id'],
                'patient_id'=>$patient,'appointment_id'=>$fixtures['unpaid_today']['appointment_id'],
                'medical_visit_id'=>$fixtures['unpaid_today']['medical_visit_id'],'payable_id'=>$fixtures['unpaid_today']['payable_id'],
                'cashiers'=>$cashiers,'cases'=>$fixtures];
        });
    }
}
