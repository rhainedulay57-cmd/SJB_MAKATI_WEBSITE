<?php
    header('Content-Type: application/json');

    $conn = new mysqli("localhost", "root", "", "sjb_databases");

    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit();
    }

    $service = isset($_POST['service']) ? implode(", ", $_POST['service']) : '';
    $date_filled = $_POST['date_filled'] ?? '';
    $mass_type = isset($_POST['mass_type']) ? implode(", ", $_POST['mass_type']) : '';
    $attendees = $_POST['attendees'] ?? '';
    $intention = $_POST['intention'] ?? '';
    $pref_sched = $_POST['pref_sched'] ?? '';
    $pref_time = $_POST['pref_time'] ?? '';
    $alter_sched = $_POST['alter_sched'] ?? '';
    $alter_time = $_POST['alter_time'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $company_owner = $_POST['company_owner'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $department = $_POST['department'] ?? '';
    $mobile_no = $_POST['mobile_no'] ?? '';
    $email = $_POST['email'] ?? '';

    $sql = "INSERT INTO `mass_blessing` (service, date_filled, mass_type, attendees, intention, pref_sched, pref_time, alter_sched, alter_time, company_name, company_owner, address, contact_person, department, mobile_no, email, status)
            VALUES ('$service', '$date_filled', '$mass_type', '$attendees', '$intention', '$pref_sched', '$pref_time', '$alter_sched', '$alter_time', '$company_name', '$company_owner', '$address', '$contact_person', '$department', '$mobile_no', '$email', 'pending')";

    if ($conn->query($sql) === TRUE) {
        $request_id = $conn->insert_id;
        echo json_encode(["success" => true, "request_id" => $request_id, "message" => "Your mass or blessing request has been submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }

    $conn->close();
?>