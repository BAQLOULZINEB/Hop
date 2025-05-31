#include <Arduino.h>
#include <WiFi.h>
#include <WebSocketsClient.h>

// WiFi credentials
const char* ssid = "Fibre_MarocTelecom-AA45";
const char* password = "QKjKCCM24K";
const char* websocket_server = "192.168.1.7:8080"; // e.g., "192.168.1.100"
const int websocket_port = 8080;

WebSocketsClient webSocket;

// --- Moniteur cardiaque AD8232 avec ESP32 ---
// 📌 Branchements recommandés :
// AD8232   | ESP32
// ---------|-------
// LO-      | GPIO32
// LO+      | GPIO33
// OUTPUT   | GPIO34 (entrée analogique recommandée)
// 3.3V     | 3V3
// GND      | GND

// --- Capteur de température LM35 ---
// 📌 Branchements :
// LM35     | ESP32
// ---------|-------
// +        | 3V3
// -        | GND
// OUT      | GPIO35 (entrée analogique)

const int TEMP_SENSOR_PIN = 35; // Pin pour le capteur LM35

void setup() {
  Serial.begin(115200);
  pinMode(32, INPUT); // LO- sur GPIO32
  pinMode(33, INPUT); // LO+ sur GPIO33
  pinMode(TEMP_SENSOR_PIN, INPUT); // Configuration du pin pour le LM35

  // Connect to WiFi
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected to WiFi");

  // Initialize WebSocket
  webSocket.begin(websocket_server, websocket_port, "/");
  webSocket.onEvent(webSocketEvent);
  webSocket.setReconnectInterval(5000);
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch(type) {
    case WStype_DISCONNECTED:
      Serial.println("WebSocket Disconnected!");
      break;
    case WStype_CONNECTED:
      Serial.println("WebSocket Connected!");
      break;
  }
}

void loop() {
  webSocket.loop();

  // Lecture de la température
  int tempRaw = analogRead(TEMP_SENSOR_PIN);
  float tempC = (tempRaw * 3.3 / 4095.0) * 100.0; // Conversion en Celsius
  Serial.print("Raw Temp: ");
  Serial.print(tempRaw);
  Serial.print(", Temp C: ");
  Serial.println(tempC);
  
  // Lecture du signal cardiaque
  int signal;
  if ((digitalRead(32) == 1) || (digitalRead(33) == 1)) {
    signal = -500; // Indicateur pour électrode déconnectée
  } else {
    signal = analogRead(34); // Signal cardiaque
  }
  
  // Create JSON string with sensor data
  String jsonData = "{\"temperature\":" + String(tempC) + 
                    ",\"heartRate\":" + String(signal) + "}";
  Serial.print("Sending: ");
  Serial.println(jsonData);
  
  // Send data via WebSocket
  webSocket.sendTXT(jsonData);
  
  delay(1000); // Attente d'une seconde entre les lectures
}