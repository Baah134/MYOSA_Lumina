UPDATED EXPORT CSV
<?php
// export_csv.php - Downloads your logs to Excel

// 1. Configuration (Must match test_data.php)
$hostname = "localhost";
$username = "root";
$password = "";
$database = "sensor_db"; // UPDATED from 'myosa'

// 2. Connect
$conn = mysqli_connect($hostname, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 3. Set Headers to force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="lumina_sensor_logs.csv"');

// 4. Open Output Stream
$output = fopen('php://output', 'w');

// 5. Write Column Headers
// The 'Sensor Reading' column will contain the long text string (Temp, Pressure, etc.)
fputcsv($output, array('ID', 'Sensor Reading (Context)', 'Timestamp'));

// 6. Fetch Data
// UPDATED table name to 'readings'
$query = "SELECT id, reading, Date FROM readings ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// 7. Write Rows
while($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
