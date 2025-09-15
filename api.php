<?php
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// =====================
// Database Connection
// =====================
$host = "";
$db = "";
$user = "";
$pass = "";

$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)));
}

// ---------------------
// 1. Handle POST /api/customer (create customer)
// ---------------------
if ($method === 'POST' && $uri === '/api/customer') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    } elseif (!isset($data['first_name'], $data['last_name'], $data['date_of_birth'], $data['email'])) {
        respond(['error' => 'Missing required fields'], 400);
    }

    $sql = "INSERT INTO customer (first_name, last_name, date_of_birth, email, phone)
            VALUES (:first_name, :last_name, :dob, :email, :phone)";

    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':first_name' => sanitize($data['first_name']),
            ':last_name' => sanitize($data['last_name']),
            ':dob' => $data['date_of_birth'],
            ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
            ':phone' => $data['phone'] ?? null
        ]);
        respond([
            "message" => "Customer created successfully",
            'customer_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        respond(['error' => 'Customer creation failed: ' . $e->getMessage()], 500);
    }
    exit;
}

// Split URI into parts
$segments = explode('/', trim($uri, '/'));

// ---------------------
// 2. Handle POST /api/customer/{id}/vehicle (add vehicle for customer)
// ---------------------
if ($method === 'POST'
    && count($segments) === 4
    && $segments[0] === 'api'
    && $segments[1] === 'customer'
    && is_numeric($segments[2])
    && $segments[3] === 'vehicle') {

    $customerId = (int)$segments[2];
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    } elseif (!isset($data['model_name'], $data['year'], $data['value'])) {
        respond(['error' => 'Missing required fields'], 400);
    }

    $sql = "INSERT INTO vehicle (customer_id, model_name, year, value)
            VALUES (:customer_id, :model_name, :year, :value)";

    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':customer_id' => $customerId,
            ':model_name' => sanitize($data['model_name']),
            ':year' => (int)$data['year'],
            ':value' => (float)$data['value']
        ]);
        respond(['vehicle_id' => $pdo->lastInsertId()]);
        respond([
            "message" => "Customer vehicle created successfully",
            'vehicle_id' => $pdo->lastInsertId()
        ]);


    } catch (PDOException $e) {
        respond(['error' => 'Vehicle creation failed: ' . $e->getMessage()], 500);
    }
    exit;
}

// ---------------------
// 3. Generate Quote GET /api/quote/{customer_id}/{vehicle_id}
// ---------------------
if ($method === 'GET'
    && count($segments) === 4
    && $segments[0] === 'api'
    && $segments[1] === 'quote'
    && is_numeric($segments[2])
    && is_numeric($segments[3])) {

    $customerId = (int)$segments[2];
    $vehicleId = (int)$segments[3];

    // Fetch customer DOB
    $stmt = $pdo->prepare("SELECT date_of_birth FROM customer WHERE id = :id");
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch();
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }

    // Fetch vehicle details
    $stmt = $pdo->prepare("SELECT year, value FROM vehicle WHERE id = :vid AND customer_id = :cid");
    $stmt->execute([':vid' => $vehicleId, ':cid' => $customerId]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }

    // ---------------------
    // Quote calculation
    // ---------------------
    $quote = 100.0; // Base quote
    try {
        $dob = new DateTime($customer['date_of_birth']);
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid customer date of birth - ' . $e->getMessage()]);
        exit;
    }
    $today = new DateTime();
    $age = $today->diff($dob)->y; // calculate customer age in years

    if ($age < 25) {
        $quote *= 1.20; // +20% uplift if customer is under 25 years old
    }

    if ((int)$vehicle['year'] < 2010) {
        $quote *= 1.15; // +15% uplift if vehicle year is prior to 2010
    }

    if ((float)$vehicle['value'] > 30000) {
        $quote *= 1.10; // +10% uplift if vehicle value exceeds £30k
    }

    $quote = round($quote, 2);
    $now = date("Y-m-d H:i:s");

    // ---------------------
    // Store in quote table
    // ---------------------
    $stmt = $pdo->prepare("INSERT INTO quote 
        (customer_id, vehicle_id, quote_amount, valid_from, valid_until, created_at)
        VALUES (:cid, :vid, :amount, :valid_from, :valid_until, :created_at)");

    $validFrom = new DateTime();
    $validUntil = (new DateTime())->add(new DateInterval('P30D')); // Quote is valid for 30 days

    $stmt->execute([
        ':cid' => $customerId,
        ':vid' => $vehicleId,
        ':amount' => $quote,
        ':valid_from' => $validFrom->format('Y-m-d'),
        ':valid_until' => $validUntil->format('Y-m-d'),
        ':created_at' => $now
    ]);

    echo json_encode([
        'quote_id' => $pdo->lastInsertId(),
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
        'quote_amount' => $quote,
        'valid_from' => $validFrom->format('Y-m-d'),
        'valid_until' => $validUntil->format('Y-m-d'),
        'generated_at' => $now
    ]);
    exit;
}

// ---------------------
// Fallback for others
// ---------------------
http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);
