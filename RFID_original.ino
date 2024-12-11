#include <WiFi.h>
#include <SPI.h>
#include <MFRC522.h>
#include <HTTPClient.h>

//************************************************************************
#define SS_PIN  10  // Chân SS
#define RST_PIN 9   // Chân RST
//************************************************************************
MFRC522 mfrc522(SS_PIN, RST_PIN); // Tạo instance MFRC522
//************************************************************************
const char *ssid = "HOME";
const char *password = "0905563221";
const char* device_token = "7428541a3a9794c1";

String URL = "http://192.168.1.4/rfidattendance/getdata.php"; // IP server
String oldCardID = "";
unsigned long previousMillis = 0;

void setup() {
  Serial.begin(115200);
  SPI.begin(4,5,6,7);            // SPI (MOSI, MISO, SCK, SS)
  mfrc522.PCD_Init();     // Khởi tạo module RFID
  connectToWiFi();        // Kết nối Wi-Fi
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }

  if (millis() - previousMillis >= 15000) {
    previousMillis = millis();
    oldCardID = "";
  }

  if (!mfrc522.PICC_IsNewCardPresent()) {
    return; // Không có thẻ
  }

  if (!mfrc522.PICC_ReadCardSerial()) {
    return; // Đọc thẻ lỗi
  }

  String cardID = getCardID();
  if (cardID != oldCardID) {
    oldCardID = cardID;
    sendCardID(cardID);
  }

  delay(1000);
}

String getCardID() {
  String cardID = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    cardID += mfrc522.uid.uidByte[i];
  }
  return cardID;
}

void sendCardID(String card_uid) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String getData = "?card_uid=" + card_uid + "&device_token=" + device_token;
    String link = URL + getData;

    http.begin(link);
    int httpCode = http.GET();
    String payload = http.getString();

    Serial.println(httpCode);
    Serial.println(payload);

    http.end();
  }
}

void connectToWiFi() {
  Serial.print("Connecting to ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nConnected to Wi-Fi");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}
