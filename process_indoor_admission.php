<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_admission'])) {
    $_SESSION['temp_adm'] = [
        'is_existing'    => isset($_POST['is_existing']) ? 1 : 0,
        'patient_id'     => isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0,
        'request_id'     => isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0,
        'name'           => mysqli_real_escape_string($conn, $_POST['name']),
        'email'          => mysqli_real_escape_string($conn, $_POST['email']),
        'phone'          => mysqli_real_escape_string($conn, $_POST['phone']),
        'doctor_id'      => (int)$_POST['doctor_id'],
        'bed_id'         => (int)$_POST['bed_id'],
        'admission_fee'  => (float)$_POST['admission_fee'],
        'pay_method'     => $_POST['admission_pay_method'],
        'blood_group'    => mysqli_real_escape_string($conn, $_POST['blood_group'] ?? 'Unknown'),
       
        'guardian_name'  => mysqli_real_escape_string($conn, $_POST['guardian_name']),
        'guardian_phone' => mysqli_real_escape_string($conn, $_POST['guardian_phone']),
        'relation'       => mysqli_real_escape_string($conn, $_POST['relation']),
        'reason'         => mysqli_real_escape_string($conn, $_POST['reason']),
        'address'        => mysqli_real_escape_string($conn, $_POST['address']),
        'admission_date' => $_POST['admission_date'],
        'password'       => $_POST['generated_password'], 
        'type'           => 'NEW_ADMISSION'
    ];

    header("Location: payment_gateway.php");
    exit();
}

if (isset($_POST['confirm_final_payment']) && isset($_SESSION['temp_adm'])) {
    $data = $_SESSION['temp_adm'];
    $hashed_pw = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $conn->begin_transaction();

    try {
        if ($data['is_existing'] == 1) {
            $p_id = $data['patient_id'];
            
            $sql_update_user = "UPDATE users u 
                                JOIN patients p ON u.user_id = p.user_id 
                                SET u.email = '{$data['email']}', u.password = '$hashed_pw' 
                                WHERE p.patient_id = $p_id";
            $conn->query($sql_update_user);

            $sql_update_patient = "UPDATE patients SET 
                                   patient_type = 'Indoor', 
                                   status = 'Indoor', 
                                   phone = '{$data['phone']}', 
                                   address = '{$data['address']}' 
                                   WHERE patient_id = $p_id";
            $conn->query($sql_update_patient);
            $patient_id = $p_id;

        } else {
            $sql_user = "INSERT INTO users (full_name, email, password, role) 
                         VALUES ('{$data['name']}', '{$data['email']}', '$hashed_pw', 'patient')";
            if (!$conn->query($sql_user)) throw new Exception("Users Table Failed: " . $conn->error);
            $user_id = $conn->insert_id;

            $sql_patient = "INSERT INTO patients (user_id, name, email, password, phone, blood_group, patient_type, status, address, age, gender) 
                            VALUES ($user_id, '{$data['name']}', '{$data['email']}', '$hashed_pw', '{$data['phone']}', '{$data['blood_group']}', 'Indoor', 'Indoor', '{$data['address']}', '0', 'Other')";
            
            if (!$conn->query($sql_patient)) throw new Exception("Patients Table Failed: " . $conn->error);
            $patient_id = $conn->insert_id;
        }

        
        $sql_admission = "INSERT INTO admissions (patient_id, doctor_id, bed_id, admission_fee, blood_group, status, admission_date, guardian_name, guardian_phone, relation) 
                          VALUES ($patient_id, {$data['doctor_id']}, {$data['bed_id']}, {$data['admission_fee']}, '{$data['blood_group']}', 'admitted', '{$data['admission_date']}', '{$data['guardian_name']}', '{$data['guardian_phone']}', '{$data['relation']}')";
        
        if (!$conn->query($sql_admission)) throw new Exception("Admission Table Failed: " . $conn->error);
        $admission_id = $conn->insert_id;

        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = {$data['bed_id']}");

        $pay_method = mysqli_real_escape_string($conn, $_POST['pay_method'] ?? 'Online');
        $sql_billing = "INSERT INTO billing (patient_id, admission_id, description, amount, status, billing_date, payment_method) 
                        VALUES ($patient_id, $admission_id, 'Admission Fee', {$data['admission_fee']}, 'paid', NOW(), '$pay_method')";
        $conn->query($sql_billing);

        $conn->commit();
        unset($_SESSION['temp_adm']);
        header("Location: view_indoor_patients.php?status=success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("DATABASE ERROR: " . $e->getMessage());
    }
}
?>