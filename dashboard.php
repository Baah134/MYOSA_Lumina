UPDATED DASHBOARD
<?php
// dashboard.php - The "Active Guardian" Visualization Interface

// 1. DATABASE CONFIGURATION
$hostname = "localhost";
$username = "root";
$password = "";
$database = "sensor_db";

// 2. FETCH SENSOR HISTORY (Last 20 Readings)
$conn = mysqli_connect($hostname, $username, $password, $database);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// We fetch the latest 20 to keep the graphs readable
$sql = "SELECT reading, reg_date FROM readings ORDER BY id DESC LIMIT 20";
$result = mysqli_query($conn, $sql);

$sensorData = [];
while($row = mysqli_fetch_assoc($result)) {
    // We reverse the array so the graph draws from Left (Old) to Right (New)
    array_unshift($sensorData, $row);
}

// 3. FETCH LATEST AI THOUGHT (Lumina's Voice)
$ai_message = "Lumina is initializing...";
$historyFile = 'chat_history.json';

if (file_exists($historyFile)) {
    $chatData = json_decode(file_get_contents($historyFile), true);
    // Find the very last message from the 'assistant'
    if (!empty($chatData)) {
        // Loop backwards to find last assistant msg
        for ($i = count($chatData) - 1; $i >= 0; $i--) {
            if ($chatData[$i]['role'] === 'assistant') {
                $ai_message = $chatData[$i]['content'];
                break;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumina | Universal Guardian</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-main: #e0e0e0;
            --accent: #00d4ff;
            --alert: #ff4757;
            --safe: #2ed573;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        /* HEADER SECTION */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header-left h1 { margin: 0; font-size: 1.8rem; }
        .header-left span { font-weight: 300; font-size: 0.8em; color: #888; }
        
        .header-right { display: flex; gap: 10px; align-items: center; }

        .btn-download {
            background-color: #333;
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        .btn-download:hover { background-color: var(--accent); color: #000; }

        .status-badge {
            background-color: var(--safe);
            color: #000;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            letter-spacing: 1px;
        }
        
        /* AI CONTEXT BOX */
        .ai-box {
            background: linear-gradient(135deg, #1e1e1e 0%, #252525 100%);
            border-left: 5px solid var(--accent);
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .ai-icon { font-size: 2em; }
        .ai-content div:first-child { font-size: 0.8em; color: #888; text-transform: uppercase; }
        .ai-voice { font-size: 1.3em; color: var(--accent); font-style: italic; margin-top: 5px; }

        /* GRAPHS GRID */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .chart-card {
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        h3 { margin-top: 0; color: #aaa; font-size: 1rem; border-bottom: 1px solid #333; padding-bottom: 10px; }

        /* Responsive */
        @media (max-width: 768px) {
            .grid-container { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }

        /* Animation for Alert */
        @keyframes blink { 50% { opacity: 0.5; } }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>Lumina <span>Active Guardian</span></h1>
        </div>
        <div class="header-right">
            <a href="export_csv.php" class="btn-download">📥 Download Log</a>
            <div id="safety-badge" class="status-badge">SYSTEM SECURE</div>
        </div>
    </div>

    <div class="ai-box">
        <div class="ai-icon">🤖</div>
        <div class="ai-content">
            <div>Latest Guardian Insight</div>
            <div class="ai-voice">"<?php echo $ai_message; ?>"</div>
        </div>
    </div>

    <div class="grid-container">
        
        <div class="chart-card">
            <h3>Environmental Comfort</h3>
            <canvas id="comfortChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>Circadian Rhythm (Light)</h3>
            <canvas id="lightChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>Activity & Falls (Accelerometer)</h3>
            <canvas id="accelChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>User Orientation (Tilt)</h3>
            <canvas id="tiltChart"></canvas>
        </div>

    </div>

    <script>
        // --- A. THE DATA TRANSLATOR ---
        // Grab PHP data
        const rawData = <?php echo json_encode($sensorData); ?>;

        // Arrays for Chart.js
        const labels = [];
        const temps = [];
        const press = [];
        const lights = [];
        const accX = [], accY = [], accZ = [];
        const tiltX = [], tiltY = [], tiltZ = [];

        // --- B. PARSING LOGIC (Regex) ---
        rawData.forEach(item => {
            const str = item.reading;
            
            // 1. Time
            const timeMatch = str.match(/TIME: (\d{2}:\d{2})/);
            labels.push(timeMatch ? timeMatch[1] : "Now");

            // 2. Temp
            const t = str.match(/Temp:\s*([-\d.]+)/);
            temps.push(t ? t[1] : 0);

            // 3. Pressure
            const p = str.match(/(?:Press|Pressure):\s*([-\d.]+)/);
            press.push(p ? p[1] : 0);

            // 4. Light
            const l = str.match(/Light:\s*([-\d.]+)/);
            lights.push(l ? l[1] : 0);

            // 5. Accel [x,y,z]
            const acc = str.match(/Accel:\s*\[([-\d.]+),([-\d.]+),([-\d.]+)/);
            if(acc) {
                accX.push(acc[1]); accY.push(acc[2]); accZ.push(acc[3]);
            } else {
                accX.push(0); accY.push(0); accZ.push(0);
            }

            // 6. Tilt [x,y,z]
            const tilt = str.match(/Tilt:\s*\[([-\d.]+),([-\d.]+),([-\d.]+)/);
            if(tilt) {
                tiltX.push(tilt[1]); tiltY.push(tilt[2]); tiltZ.push(tilt[3]);
            } else {
                tiltX.push(0); tiltY.push(0); tiltZ.push(0);
            }
        });

        // --- C. CHART CONFIGURATION ---
        
        // 1. COMFORT
        new Chart(document.getElementById('comfortChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Temp (°C)', data: temps, borderColor: '#ff6b6b', yAxisID: 'y' },
                    { label: 'Pressure (Bar)', data: press, borderColor: '#54a0ff', yAxisID: 'y1' }
                ]
            },
            options: {
                scales: {
                    y: { type: 'linear', display: true, position: 'left' },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });

        // 2. LIGHT
        new Chart(document.getElementById('lightChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Light (Lux)',
                    data: lights,
                    borderColor: '#feca57',
                    backgroundColor: 'rgba(254, 202, 87, 0.2)',
                    fill: true
                }]
            }
        });

        // 3. ACTIVITY (ACCEL)
        new Chart(document.getElementById('accelChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'X', data: accX, borderColor: '#ff9f43', borderWidth: 1 },
                    { label: 'Y', data: accY, borderColor: '#ee5253', borderWidth: 1 },
                    { label: 'Z', data: accZ, borderColor: '#0abde3', borderWidth: 1 }
                ]
            },
            options: { elements: { point: { radius: 0 } } }
        });

        // 4. POSTURE (TILT)
        new Chart(document.getElementById('tiltChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Tilt X', data: tiltX, borderColor: '#c8d6e5', borderWidth: 1 },
                    { label: 'Tilt Z', data: tiltZ, borderColor: '#1dd1a1', borderWidth: 2 }
                ]
            }
        });

        // --- D. SAFETY LOGIC (Traffic Light) ---
        const lastAccX = Math.abs(accX[accX.length-1]);
        const badge = document.getElementById('safety-badge');
        
        // Threshold: If X-Accel > 15 (Severe Impact)
        if (lastAccX > 15) {
            badge.innerText = "⚠️ FALL DETECTED";
            badge.style.backgroundColor = "#ff4757"; // Red
            badge.style.animation = "blink 1s infinite";
        } else {
            badge.innerText = "SYSTEM SECURE";
            badge.style.backgroundColor = "#2ed573"; // Green
            badge.style.animation = "none";
        }

        // --- E. AUTO-REFRESH ---
        // Reload every 5 seconds to update graphs and AI
        setTimeout(() => {
            window.location.reload();
        }, 5000);

    </script>
</body>
</html>
