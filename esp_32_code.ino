/* Library Inclusion */
#include <myosa.h>
#include <WiFi.h>
#include <HTTPClient.h>

MYOSA myosa;

// UPDATE THIS IP ADDRESS TO YOUR LAPTOP'S CURRENT IP
String URL = "http://YOUR_LOCAL_IP/myosa_project/test_data.php"; 
const char* SSID = "YOUR_WIFI_SSID";
const char* PASSWORD = "YOUR_WIFI_PASSWORD";

void setup() {
  Serial.begin(115200);
  Wire.begin();
  Wire.setClock(100000);
  WiFi.mode(WIFI_STA);
  WiFi.begin(SSID, PASSWORD);
  
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED){
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected!");
  Serial.println(myosa.begin());
}

void loop() {
  // 1. READ SENSORS
  float temp = myosa.Th.getTempC();
  float pressure = myosa.Pr.getPressureBar();
  int light = myosa.Lpg.getAmbientLight();
  float proximity = myosa.Lpg.getProximity();
  float acc_x = myosa.Ag.getAccelX();
  float acc_y = myosa.Ag.getAccelY();
  float acc_z = myosa.Ag.getAccelZ();
  float tilt_x = myosa.Ag.getTiltX();
  float tilt_y = myosa.Ag.getTiltY();
  float tilt_z = myosa.Ag.getTiltZ();
    
  // 2. PREPARE DATA
  String postData1 = "temp=" + String(temp) + "&press=" + String(pressure) + "&amb_light=" + String(light) + "&prox=" + String(proximity);
  String postData2 = "&accx=" + String(acc_x) +"&accy=" + String(acc_y) +"&accz=" + String(acc_z) + "&t_x=" + String(tilt_x) +"&t_y=" + String(tilt_y) +"&t_z=" + String(tilt_z);
  String postData = postData1 + postData2; 
  
  if(WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      http.begin(URL);
      
      // CRITICAL FIX: Header MUST be here
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      
      int httpCode = http.POST(postData);
      
      if (httpCode > 0) {
        String payload = http.getString();
        Serial.println("Server Response: " + payload);
      } else {
        Serial.print("Error: "); Serial.println(httpCode);
      }
      http.end();
  }
  delay(5000);
}