#include <WiFi.h>
#include <HTTPClient.h>
#include <DHTesp.h>

#define SEND_INTERVAL 250      // Send data every 250ms (4 times per second)

// WiFi and server config
const char* ssid = "Fibre_MarocTelecom-AA45";
const char* password = "QKjKCCM24K";
const char* serverName = "http://192.168.1.7:8080/hospital_management_v1/backend/api/update.php";
String patientID = ""; // Patient ID is now always set at session start

// Hardware pins
const int DHTPIN = 17;    // DHT11 data pin
const int HR_PIN = 34;    // Heart rate analog input (AD8232)
const int LO_MINUS = 32;  // AD8232 LO- pin
const int LO_PLUS = 33;   // AD8232 LO+ pin

DHTesp dht;
unsigned long lastSendTime = 0;
unsigned long sessionStart = 0;
const unsigned long sessionDuration = 60000; // 1 minute in milliseconds

void setup() {
  Serial.begin(115200);
  delay(1000); // Wait for serial to initialize

  // Always prompt for Patient ID at session start
  patientID = "";
  while (patientID.length() == 0) {
    Serial.println("----------------------------------------");
    Serial.println("Please enter the Patient ID and press Enter:");
    while (Serial.available() == 0) {
      delay(100); // Wait for user input
    }
    String input = Serial.readStringUntil('\n');
    input.trim();
    if (input.length() > 0 && input.toInt() > 0) {
      patientID = input;
      Serial.print("Patient ID '");
      Serial.print(patientID);
      Serial.println("' has been set for this session.");
      Serial.println("----------------------------------------");
    } else {
      Serial.println("Invalid ID. Please enter a numeric ID greater than 0.");
    }
  }
  sessionStart = millis();

  // Configure pins for AD8232
  pinMode(LO_MINUS, INPUT);
  pinMode(LO_PLUS, INPUT);
  // No LM35, so no TEMP_SENSOR_PIN setup

  // Initialize DHT11 sensor
  dht.setup(DHTPIN, DHTesp::DHT11);
  Serial.println("DHT11 sensor initialized");

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  unsigned long startTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 15000) {
    delay(500);
    Serial.print(".");
  }
  if(WiFi.status() != WL_CONNECTED) {
    Serial.println("\nWiFi connection failed");
  } else {
    Serial.println("\nWiFi connected");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
  }
}

void loop() {
  // Check if session duration has elapsed
  if (millis() - sessionStart > sessionDuration) {
    Serial.println("Session finished. Please enter a new patient ID for the next session.");
    delay(1000);
    ESP.restart();
  }

  // Send data at a fixed high frequency for the waveform graph
  if (millis() - lastSendTime >= SEND_INTERVAL) {
    // Read sensor data
    TempAndHumidity data = dht.getTempAndHumidity();
    float currentTemperature = data.temperature;
    int currentHeartSignal;
    if ((digitalRead(LO_MINUS) == 1) || (digitalRead(LO_PLUS) == 1)) {
      currentHeartSignal = 0; // Send 0 if leads are off for a flat line
    } else {
      currentHeartSignal = analogRead(HR_PIN); // Raw heart signal
    }

    // Send the data
    if (WiFi.status() == WL_CONNECTED) {
      sendData(currentTemperature, currentHeartSignal);
    } else {
      Serial.println("WiFi disconnected. Attempting to reconnect...");
      WiFi.begin(ssid, password);
    }
    lastSendTime = millis();
  }
}

void sendData(float temp, int heartRate) {
  HTTPClient http;
  http.begin(serverName);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  
  String postData = "temperature=" + String(temp) + 
                   "&heartRate=" + String(heartRate) + 
                   "&patientID=" + String(patientID);
  
  int httpCode = http.POST(postData);
  
  if (httpCode > 0) {
    Serial.printf("Waveform point sent - HTTP code: %d\n", httpCode);
  } else {
    Serial.printf("HTTP error: %s\n", http.errorToString(httpCode).c_str());
  }
  
  http.end();
}