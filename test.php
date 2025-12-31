<?php
// test_data.php - The "Lumina" Brain (Universal + Context Aware + TILT FIXED)

// --- 1. CONFIGURATION ---
$hostname = "localhost";
$username = "root";
$password = "";
$database = "sensor_db";
$apiKey   = "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";

// File paths for memory
$historyFile = 'chat_history.json';
$contextFile = 'last_sensor_state.txt';

// Timezone for accurate "Morning/Night" logic
date_default_timezone_set('Africa/Accra');

// --- 2. CONNECT TO DATABASE ---
$conn = mysqli_connect($hostname, $username, $password, $database);
if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

// --- 3. CHECK FOR DATA ---
if (isset($_POST["temp"])) {
    
    // A. Capture Variables (ADDED TILT HERE)
    $t  = $_POST["temp"];
    $p  = $_POST["press"];
    $l  = $_POST["amb_light"];
    $px = $_POST["prox"];
    $ax = $_POST["accx"];
    $ay = $_POST["accy"];
    $az = $_POST["accz"];
    $tx = $_POST["t_x"]; // Captured Tilt X
    $ty = $_POST["t_y"]; // Captured Tilt Y
    $tz = $_POST["t_z"]; // Captured Tilt Z
    
    // B. Context Memory (Read Previous State)
    $prevData = "None (First Start)";
    if (file_exists($contextFile)) {
        $prevData = file_get_contents($contextFile);
    }

    // C. Save Current State (For Next Time)
    // We save Tilt history too now
    $currentSimple = "Accel: [$ax,$ay,$az], Tilt: [$tx,$ty,$tz], Light: $l";
    file_put_contents($contextFile, $currentSimple);

    // D. Format Data for the AI (ADDED TILT TO STRING)
    // Now the AI sees the full picture: Time + Sensors + Tilt + History
    $currentTime = date("H:i");
    $combinedData = "TIME: $currentTime. " .
                    "CURRENT SENSORS: [Temp: $t C, Press: $p Bar, Light: $l Lux, Accel: [$ax,$ay,$az], Tilt: [$tx,$ty,$tz]]. " .
                    "PREVIOUS SENSORS: [$prevData].";

    // --- 4. LOG TO SQL ---
    $stmt = $conn->prepare("INSERT INTO readings (reading) VALUES (?)");
    $stmt->bind_param("s", $combinedData);
    $stmt->execute();
    $stmt->close();

    // --- 5. PREPARE INTELLIGENCE ---
    
    // Load History
    if (file_exists($historyFile)) {
        $conversation = json_decode(file_get_contents($historyFile), true);
    } else {
        $conversation = [];
    }

    // DEFINE THE SYSTEM PROMPT (Updated with Tilt Logic)
    if (empty($conversation)) {
        $conversation[] = [
            "role" => "system",
            "content" => "IDENTITY: You are 'Lumina', a context-aware ambient guardian for a home in Ghana.

                          INPUT DATA:
                          - You receive CURRENT and PREVIOUS sensor readings.
                          - 'Tilt' tells you body orientation (Standing vs Lying Down).
                          - You know the Time [HH:MM].

                          --- INTELLIGENT RULES ---

                          1. LIGHTING CONTEXT (Circadian Rhythms):
                             - MORNING (06:00 - 11:00): If Light is LOW (<100), say 'Good morning! Open the blinds to wake up.'
                             - AFTERNOON (12:00 - 16:00): 
                               * If Light is LOW (<150): 'It's gloomy inside. Let some sunlight in.'
                               * If Light is HIGH (>1000) AND Temp > 30C: 'Sun is harsh. Close blinds to keep cool.'
                             - NIGHT (20:00 - 06:00): If Light is HIGH (>200), say 'It's late. Dim the lights for better sleep.'

                          2. FALL DETECTION (Impact + Orientation):
                             - Compare 'CURRENT Accel' with 'PREVIOUS Accel'.
                             - CRITICAL FALL: If (Current Accel > 20) AND (Current Tilt_Z is near 0 or 180), say: 'Fall detected! User is horizontal. Are you okay?'
                             - JUST A JUMP: If (Current Accel > 20) BUT (Current Tilt_Z is near 90), ignore (User is still standing).

                          3. GENERAL HEALTH:
                             - If Pressure < 995 mbar, warn of incoming storm.
                             - If Temp > 33C, warn of heat stress.

                          OUTPUT RULES:
                          - Max 15 words (Strict limit for OLED).
                          - No emojis.
                          - Tone: Helpful but urgent for safety risks."
        ];
    }

    // Add User Message
    $conversation[] = [
        "role" => "user",
        "content" => $combinedData
    ];

    // Sliding Window (Keep memory small)
    if (count($conversation) > 7) {
        $systemMsg = $conversation[0];
        $recent = array_slice($conversation, -6);
        $conversation = array_merge([$systemMsg], $recent);
    }

    // --- 6. CALL THE AI ---
    $data = [
        "model" => "nvidia/nemotron-3-nano-30b-a3b:free",
        "messages" => $conversation
    ];

    $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey,
        "HTTP-Referer: http://localhost",
        "X-Title: Lumina_IoT"
    ]);

    $response = curl_exec($ch);
    
    if(curl_errno($ch)){
        echo "Network Error";
        exit();
    }
    curl_close($ch);

    // --- 7. PROCESS & REPLY ---
    $json_response = json_decode($response, true);
    
    if (isset($json_response['choices'][0]['message']['content'])) {
        $aiReply = $json_response['choices'][0]['message']['content'];
        
        // Save to History
        $conversation[] = ["role" => "assistant", "content" => $aiReply];
        file_put_contents($historyFile, json_encode($conversation, JSON_PRETTY_PRINT));
        
        // OUTPUT ONLY THE AI REPLY
        echo $aiReply;
    } else {
        echo "Thinking...";
    }

} else {
    echo "Lumina Server Ready. Waiting for sensors.";
}
?>
