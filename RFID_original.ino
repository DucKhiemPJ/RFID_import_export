#include <WiFi.h>
#include <SPI.h>
#include <MFRC522.h>
#include <HTTPClient.h>

//************************************************************************
#define SS_PIN  10  // Chân SS
#define RST_PIN 9   // Chân RST
#define BUZZER_PIN 3 // Chân GPIO điều khiển còi
//************************************************************************
MFRC522 mfrc522(SS_PIN, RST_PIN); // Tạo instance MFRC522
//************************************************************************
const char *ssid = "HOME";
const char *password = "0905563221";
const char* device_token = "6b132ce979d9aba0";

String URL = "http://192.168.1.4/rfidio/getdata.php"; // IP server
String oldCardID = "";
unsigned long previousMillis = 0;

void setup() {
  Serial.begin(115200);
  SPI.begin(6, 5, 4);  // SPI (sck, miso, mosi, SS)
  connectToWiFi();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Wi-Fi connection failed! Check credentials.");
    while (1);  // Dừng nếu không kết nối
  }

  mfrc522.PCD_Init();          // Khởi tạo module RFID
  pinMode(BUZZER_PIN, OUTPUT); // Thiết lập chân còi là OUTPUT
  digitalWrite(BUZZER_PIN, LOW);
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
  Serial.print("Card ID: ");
  Serial.println(cardID);
  if (cardID != oldCardID) {
    oldCardID = cardID;
    sendCardID(cardID);
  }
}

String getCardID() {
  char cardID[20];
  sprintf(cardID, "%02X%02X%02X%02X", mfrc522.uid.uidByte[0], mfrc522.uid.uidByte[1], 
          mfrc522.uid.uidByte[2], mfrc522.uid.uidByte[3]);
  return String(cardID);
}

void sendCardID(String card_uid) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String getData = "?card_uid=" + card_uid + "&device_token=" + device_token;
    String link = URL + getData;
    Serial.print("Link sent: ");
    Serial.println(link);
    http.begin(link);  // Bắt đầu kết nối HTTP
    int httpCode = http.GET();  // Gửi GET request
    String payload = http.getString();  // Nhận phản hồi từ server

    Serial.print("HTTP Response Code: ");
    Serial.println(httpCode);

    if (httpCode > 0) {
      if (httpCode == HTTP_CODE_OK) {
        Serial.println("Data sent successfully!");
        Serial.println(payload);

        if (payload.indexOf("Login successful") >= 0 || payload.indexOf("New card successfully registered") >= 0) {
          Serial.println("Action successful! Triggering buzzer...");
          digitalWrite(BUZZER_PIN, HIGH);
          delay(500);
          digitalWrite(BUZZER_PIN, LOW);
        }
      } else {
        Serial.println("Error response: ");
        Serial.println(payload);
      }
    } else {
      Serial.println("Error on sending request.");
    }

    http.end();
  } else {
    Serial.println("Not connected to Wi-Fi.");
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
