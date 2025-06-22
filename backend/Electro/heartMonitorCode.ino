#include <EEPROM.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <DHTesp.h>

#define EEPROM_SIZE 32         // Allocate 32 bytes for storing the patient ID
#define SEND_INTERVAL 250      // Send data every 250ms (4 times per second)

// WiFi and server config
const char* ssid = "Fibre_MarocTelecom-AA45";
const char* password = "QKjKCCM24K";
const char* serverName = "http://192.168.1.9/hospital_management_v1/backend/api/update.php";
String patientID = ""; // Patient ID is now dynamic

// Hardware pins
const int DHTPIN = 17;    // DHT11 data pin
const int HR_PIN = 34;    // Heart rate analog input (AD8232)
const int LO_MINUS = 32;  // AD8232 LO- pin
const int LO_PLUS = 33;   // AD8232 LO+ pin

DHTesp dht;
unsigned long lastSendTime = 0;

void setup() {
  Serial.begin(115200);
  delay(1000); // Wait for serial to initialize

  // Initialize EEPROM
  EEPROM.begin(EEPROM_SIZE);

  // Read Patient ID from EEPROM
  patientID = "";
  for (int i = 0; i < EEPROM_SIZE; ++i) {
    char c = EEPROM.read(i);
    if (c == 0) break; // Stop at null terminator
    patientID += c;
  }
  patientID.trim();

  // If no patient ID is stored, prompt the user via Serial Monitor
  while (patientID.length() == 0) {
    Serial.println("----------------------------------------");
    Serial.println("DEVICE NOT ASSIGNED");
    Serial.println("Please enter the Patient ID and press Enter:");
    
    while (Serial.available() == 0) {
      delay(100); // Wait for user input
    }
    
    String input = Serial.readStringUntil('\n');
    input.trim();
    
    if (input.length() > 0 && input.toInt() > 0) {
      patientID = input;
      // Save the new ID to EEPROM
      for (int i = 0; i < patientID.length(); ++i) {
        EEPROM.write(i, patientID[i]);
      }
      EEPROM.write(patientID.length(), 0); // Add null terminator
      EEPROM.commit();
      
      Serial.print("Patient ID '");
      Serial.print(patientID);
      Serial.println("' has been saved.");
      Serial.println("The device will now start sending data.");
      Serial.println("----------------------------------------");
    } else {
       Serial.println("Invalid ID. Please enter a numeric ID greater than 0.");
    }
  }

  Serial.print("Device assigned to Patient ID: ");
  Serial.println(patientID);
  Serial.println("Type 'reset' and press Enter to assign a new patient.");
  
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
  // Check for a reset command from the Serial Monitor
  if (Serial.available() > 0) {
    String command = Serial.readStringUntil('\n');
    command.trim();
    if (command == "reset") {
      Serial.println("****************************************");
      Serial.println("Resetting Patient ID...");
      for (int i = 0; i < EEPROM_SIZE; i++) {
        EEPROM.write(i, 0); // Clear EEPROM
      }
      EEPROM.commit();
      Serial.println("Patient ID has been cleared.");
      Serial.println("Please restart the device to set a new ID.");
      Serial.println("****************************************");
      ESP.restart();
    }
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