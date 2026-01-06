<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = (int)$_SESSION['patient_id'];

// Fetch all prescriptions. 
// Note: If your DB has a 'doctor_id', we join with 'users' to get the doctor's name properly.
$sql = "SELECT p.*, u.full_name as dr_name 
        FROM prescriptions p 
        LEFT JOIN doctors d ON p.doctor_id = d.doctor_id
        LEFT JOIN users u ON d.user_id = u.user_id
        WHERE p.patient_id = $patient_id 
        ORDER BY p.date_prescribed DESC";

$pres_result = $conn->query($sql);
?>

<style>
    :root {
        --rx-blue: #1a237e;
        --rx-light: #f8f9fa;
    }
    body { background-color: #f0f2f5; }
    
    .prescription-paper {
        background: white;
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    
    .prescription-header {
        border-bottom: 2px solid var(--rx-blue);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .rx-symbol {
        font-family: "Times New Roman", Times, serif;
        font-size: 45px;
        font-weight: bold;
        color: var(--rx-blue);
        line-height: 1;
    }

    .medicine-row {
        background: var(--rx-light);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 5px solid var(--rx-blue);
    }

    .doctor-sig {
        font-family: 'Dancing Script', cursive; /* Optional: adds a handwritten feel */
        font-size: 20px;
        color: #555;
    }

    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 120px;
        color: rgba(26, 35, 126, 0.03);
        pointer-events: none;
        user-select: none;
        font-weight: bold;
    }
    
    @media print {
        .no-print { display: none; }
        body { background: white; }
        .prescription-paper { box-shadow: none; border: 1px solid #ddd; }
    }
</style>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 class="fw-bold text-dark"><i class="fas fa-file-medical me-2 text-primary"></i> Pharmacy Records</h3>
        <div>
            <button onclick="window.print()" class="btn btn-dark shadow-sm me-2">
                <i class="fas fa-print me-1"></i> Print Rx
            </button>
            <a href="patient_dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    <?php if ($pres_result && $pres_result->num_rows > 0): ?>
        <div class="row justify-content-center">
            <?php while($p = $pres_result->fetch_assoc()): ?>
                <div class="col-lg-8 mb-5">
                    <div class="prescription-paper p-4 p-md-5">
                        <div class="watermark">HMS</div>
                        
                        <div class="prescription-header d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="fw-bold text-primary mb-0">HOSPITAL MANAGEMENT SYSTEM</h4>
                                <p class="small text-muted mb-0">24/7 Medical Care & Diagnostic Center</p>
                                <p class="small text-muted">Contact: +880 1234 567890</p>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0 fw-bold">Prescription Slip</h5>
                                <p class="small text-muted">Date: <?= date('d M, Y', strtotime($p['date_prescribed'])) ?></p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <small class="text-uppercase text-muted d-block">Doctor</small>
                                <strong class="text-dark">Dr. <?= htmlspecialchars($p['dr_name'] ?? $p['doctor_name']) ?></strong>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-uppercase text-muted d-block">Ref ID</small>
                                <strong class="text-dark">#RX-<?= $p['prescription_id'] ?></strong>
                            </div>
                        </div>

                        <div class="rx-symbol mb-3">℞</div>

                        <div class="medicine-row shadow-sm">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h5 class="fw-bold mb-1 text-navy"><?= htmlspecialchars($p['medicine_name']) ?></h5>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-info-circle me-1 small"></i> 
                                        <?= htmlspecialchars($p['instructions']) ?>
                                    </p>
                                </div>
                                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                    <span class="badge bg-primary px-3 py-2 mb-1"><?= htmlspecialchars($p['dosage']) ?></span>
                                    <div class="small fw-bold text-dark">Duration: <?= htmlspecialchars($p['duration']) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5 pt-4">
                            <div class="col-6 mt-4">
                                <p class="small text-muted mb-0">This prescription is valid for the mentioned duration.</p>
                            </div>
                            <div class="col-6 text-center">
                                <div class="doctor-sig mb-0">Dr. <?= htmlspecialchars($p['dr_name'] ?? $p['doctor_name']) ?></div>
                                <hr class="mt-0 mb-1 w-75 mx-auto">
                                <small class="text-muted">Authorized Signature</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-pills fa-4x text-light mb-3"></i>
            <h4 class="text-muted">No medications found in your history.</h4>
            <p class="text-muted">New prescriptions will appear here once updated by your doctor.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>