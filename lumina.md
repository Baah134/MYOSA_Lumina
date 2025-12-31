lumina.md
---
publishDate: 2025-12-29
title: Lumina: The Hybrid AI Ambient Sentinel
excerpt: An intelligent IoT system that combines local environmental telemetry with privacy-focused cloud AI to create a safer, healthier living space.
image: lumina-cover.jpg
tags:
  - iot
  - esp32
  - artificial-intelligence
  - healthcare
  - smart-home
---

> "Lumina doesn't just measure the room; it understands how the environment affects the human inside it."

---

## Overview
Current smart home devices are "passive"—they show you numbers but don't explain what they mean. Lumina is an **Active Guardian**. It combines a **Wearable Node** (Activity Tracking) with a **Stationary Hub** (Environmental Sensing) to monitor both the user and their surroundings.

Lumina operates on a **Hybrid Edge-Cloud Architecture**. It uses a local server to aggregate and visualize sensor data within the user's private network, ensuring user sovereignty over historical records. For complex analysis, it employs a **Privacy-Filtered Gateway** that strips personal identifiers before sending raw telemetry to the **Nvidia Nemotron LLM** (via OpenRouter). This allows the system to offer "Big Tech" intelligence while maintaining strict data privacy.

**Key features:**
* **AI-Driven Context:** Converts raw sensor data (e.g., "1003 mbar", "6 Lux") into human-readable advice (e.g., "Storm approaching, secure the windows").
* **Fall & Activity Detection:** Uses accelerometer data to detect falls or sleep restlessness.
* **Privacy-First Design:** Anonymizes telemetry before external processing; no audio or video is ever recorded.
* **Holistic Monitoring:** Simultaneously tracks Light, Pressure, Temperature, and Motion.

---

## Demo / Examples

### **Images**

<p align="center">
  <img src="/lumina-cover.jpg" width="800"><br/>
  <i>Figure 1: The Lumina Device and Dashboard Setup</i>
</p>

<p align="center">
  <img src="/lumina-dashboard.jpg" width="800"><br/>
  <i>Figure 2: Real-time Data Visualization and Hybrid AI Chat Interface</i>
</p>

### **Videos**

<p align="center">
  <video controls width="100%">
  <source src="/lumina-demo.mp4" type="video/mp4">
  </video>
  <br/><i>Video 1: Live demonstration of sensor data triggering AI responses</i>
</p>

---

## Features (Detailed)

### **1. Environmental Telemetry**
The stationary hub continuously monitors the "health" of the room.
* **Barometric Pressure:** Predicts incoming storms and weather changes (e.g., sudden drops < 1000 mbar).
* **Ambient Light:** Detects if the lighting is sufficient for the user's current activity (Reading vs. Sleeping).
* **Temperature:** Monitors for heat stress risks using accurate environmental sensors.

### **2. Activity & Safety Monitoring**
The accelerometer module (simulating a wearable tag) tracks the user's physical state.
* **Fall Detection:** Identifies sudden high-G impacts followed by inactivity.
* **Sleep/Rest Analysis:** Tracks micro-movements to determine if a patient is restless or sleeping soundly.

### **3. Hybrid AI Analysis**
Unlike standard smart devices that stream everything to the cloud 24/7, Lumina operates on a **"Need-to-Know" basis**.
* **Local Logic First:** Basic thresholds (e.g., "Too hot") are handled locally by the ESP32 and PHP server for instant reaction.
* **Cloud Intelligence:** Complex queries (e.g., "Is this combination of pressure drop and restlessness dangerous?") are routed to the **Nvidia Nemotron LLM**.
* **Anonymization:** The system sends only raw sensor integers. No user metadata, audio, or images are ever transmitted to the AI provider.

---

## Usage Instructions

To run the system locally:

1.  **Start the Local Server:**
    Ensure XAMPP is running Apache and MySQL to handle the local database and PHP logic.

2.  **Power the ESP32:**
    Connect the ESP32 to a power source. It will automatically connect to the local WiFi and begin broadcasting sensor packets.

3.  **View the Dashboard:**
    Open a browser and navigate to the local dashboard address to view live telemetry and AI insights.
    ```plaintext
    http://localhost/myosa/dashboard.php
    ```
4. **PHP Code to upload data**
        ```php
    UPDATED PHP
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
?>p
    ```

---

## Tech Stack

* **Hardware:** ESP32 Microcontroller, Accelerometer, Pressure/Temperature Sensor,Light Sensor, OLED Display.
* **Firmware:** C++ (Arduino Framework).
* **Backend:** PHP, MySQL (XAMPP Local Server).
* **AI Model:** Nvidia Nemotron-3-Nano-30b (via OpenRouter API).
* **Frontend:** HTML/CSS, Chart.js for real-time graphing.

---

## Requirements / Installation

**Hardware Dependencies:**
* ESP32 Dev Module
* Sensors (MPU6050, BMP280, BH1750)

**Software Dependencies:**
```bash
# For the AI Server interaction (if running Python backend)
pip install openai requests

# Local Server Setup
Download and install XAMPP (Apache + MySQL)
---

## Contributions

We are actively looking for contributors to help expand Lumina's capabilities. Here are the key features we plan to implement next: 
Voice Feedback Loop: Integrating an I2S Speaker (MAX98357A) to allow Lumina to verbally announce alerts (Text-to-Speech) for visually impaired users. 
Voice Command Interface: Adding an INMP441 Microphone so users can query the AI naturally without a dashboard. 
TinyML Integration: Replacing the current threshold-based fall detection with a trained TensorFlow Lite model (via Edge Impulse) for higher accuracy. S
mart Home Bridging: Adding MQTT support to allow Lumina to directly control smart bulbs and thermostats based on its environmental analysis
