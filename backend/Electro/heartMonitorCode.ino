#include <Arduino.h>

// --- Moniteur cardiaque AD8232 avec ESP32 ---
// 📌 Branchements recommandés :
// AD8232   | ESP32
// ---------|-------
// LO-      | GPIO32
// LO+      | GPIO33
// OUTPUT   | GPIO34 (entrée analogique recommandée)
// 3.3V     | 3V3
// GND      | GND

void setup() {
  Serial.begin(9600);
  pinMode(32, INPUT); // LO- sur GPIO32
  pinMode(33, INPUT); // LO+ sur GPIO33
}

void loop() {
  if ((digitalRead(32) == 1) || (digitalRead(33) == 1)) {
    Serial.println("!"); // Electrode déconnectée
  } else {
    int signal = analogRead(34); // Signal cardiaque
    Serial.println(signal);
  }
  delay(10);
}