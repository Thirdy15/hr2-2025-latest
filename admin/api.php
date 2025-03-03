<?php  
header("Content-Type: application/json");

// Expected API Key  
$valid_api_key = "HhRbqeHykx8iyMj1DvERoeQHJggwDgWh";

// Get API Key from headers  
$headers = getallheaders();  
$received_api_key = isset($headers['Authorization']) ? trim(str_replace("Bearer", "", $headers['Authorization'])) : null;

// Validate API Key  
if ($received_api_key !== $valid_api_key) {  
            http_response_code(403);  
            echo json_encode(["error" => "Unauthorized"]);  
            exit;  
}

// Get raw JSON input  
$rawData = file_get_contents("php://input");  
file_put_contents("debug_log.txt", "Raw Input: " . $rawData); // Debugging

$data = json_decode($rawData, true);

// Validate request data  
if (!$data || !isset($data['employee_id'], $data['first_name'], $data['last_name'], $data['email'], $data['role'], $data['password'])) {  
            http_response_code(400);  
            echo json_encode(["error" => "Invalid request data"]);  
            exit;  
}

// Database connection  
$host = "localhost";  
$dbname = "hr2_hr2";  
$username = "hr2_hr2";  
$password = "r%w3inyI6%^qh0yr";

try {  
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [  
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  
            ]);  
} catch (PDOException $e) {  
            http_response_code(500);  
            echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);  
            exit;  
}

// Insert user into database  
try {  
            $stmt = $pdo->prepare("INSERT INTO employee_register (employee_id, first_name, last_name, email, role, password) VALUES (?, ?, ?, ?, ?, ?)");  
            $stmt->execute([  
                        $data['employee_id'],  
                        $data['first_name'],  
                        $data['last_name'],  
                        $data['email'],  
                        $data['role'],  
                        password_hash($data['password'], PASSWORD_BCRYPT)  
            ]);

          http_response_code(201);  
            echo json_encode(["message" => "User created successfully"]);  
} catch (PDOException $e) {  
            http_response_code(500);  
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);  
}  
?>