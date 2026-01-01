lumina.md
---
publishDate: "2025-12-29"
title: Lumina ,The Hybrid AI Ambient Sentinel
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
  <img src="/lumina-cover.png" width="800"><br/>
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

## Installation & Setup Guide

Since Lumina uses a hybrid local-cloud architecture, you need to set up the local server before powering on the device.

### **1. Database Configuration**
The system requires a local MySQL database to store sensor history.
1.  Install **XAMPP** (or any WAMP stack).
2.  Start **Apache** and **MySQL** from the XAMPP Control Panel.
3.  Open your browser and go to `http://localhost/phpmyadmin`.
4.  Create a new database named **`sensor_db`**.
5.  Run the following SQL command to create the required table:
    ```sql
    CREATE TABLE readings (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reading TEXT NOT NULL,
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ```

### **2. Backend Deployment**
1.  Navigate to your XAMPP installation folder (usually `C:\xampp\htdocs\`).
2.  Create a new folder named **`myosa_project`**.
3.  Copy your PHP files (`test_data.php`, `dashboard.php`,`export_csv.php`) into this folder.
    * *Note: Ensure your `test_data.php` has your valid OpenRouter API Key.*

### **3. Hardware Assembly & Firmware**
1.  **Wiring:** Connect sensors (BMP280, MPU6050, ADPS9960) via I2C (SDA -> GPIO 21, SCL -> GPIO 22).
2.  **IP Config:** Open the C++ code and update `String URL` with your laptop's local IP address (e.g., `192.168.1.5`).
3.  **Upload:** Flash the code to the ESP32 using Arduino IDE.

###
1. test.php allows you to upload sensor data code and communicate with the LLM.
2. dashboard.php displays the live UI
3. export.csv allows you to download your csv data
---

## Usage Instructions

To run the system once installed:

1.  **Start the Local Server:**
    Ensure XAMPP is running Apache and MySQL.

2.  **Power the ESP32:**
    Connect the ESP32 to a power source. It will automatically connect to WiFi and begin broadcasting sensor packets.

3.  **View the Dashboard:**
    Open a browser and navigate to the local dashboard address to view live telemetry and AI insights.
    ```plaintext
    http://localhost/myosa_project/dashboard.php
    ```

---

## Tech Stack

* **Hardware:** ESP32 Microcontroller, Accelerometer (MPU6050), Pressure/Temp (BMP280), Light (BH1750), OLED Display.
* **Firmware:** C++ (Arduino Framework).
* **Backend:** PHP, MySQL (XAMPP Local Server).
* **AI Model:** Nvidia Nemotron-3-Nano-30b (via OpenRouter API).
* **Frontend:** HTML/CSS, Chart.js for real-time graphing.

---

## Requirements / Installation

**Hardware Dependencies:**
* ESP32 Dev Module
* Sensors (MPU6050, BMP280, ADPS9960)

**Software Dependencies:**
```bash
# For the AI Server interaction (if running Python backend)
pip install openai requests

# Local Server Setup
Download and install XAMPP (Apache + MySQL)

---
##  Contribution Notes

We are actively looking for contributors to help expand Lumina's capabilities. We welcome pull requests and feature suggestions!

**Key Roadmap Items:**
* **Voice Feedback Loop:** Integration of an I2S Speaker (MAX98357A) to allow Lumina to verbally announce alerts (Text-to-Speech) for visually impaired users.
* **Voice Command Interface:** Adding an INMP441 Microphone so users can query the AI naturally without a dashboard.
* **TinyML Integration:** Replacing the current threshold-based fall detection with a trained TensorFlow Lite model (via Edge Impulse) for higher accuracy.
* **Smart Home Bridging:** Adding MQTT support to allow Lumina to directly control smart bulbs and thermostats based on its environmental analysis.
