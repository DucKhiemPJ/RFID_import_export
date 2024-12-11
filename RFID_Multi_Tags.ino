#include <WiFi.h>
#include <SPI.h>
#include <MFRC522.h>
#include <HTTPClient.h>

//************************************************************************
#define SS_PIN 10
#define RST_PIN 9
//************************************************************************
MFRC522 mfrc522(SS_PIN, RST_PIN); // Tạo instance MFRC522
//************************************************************************
const char *ssid = "HOME";
const char *password = "0905563221";
const char* device_token = "7428541a3a9794c1";

String URL = "http://192.168.1.4/rfidattendance/getdata.php"; // IP server
unsigned long previousMillis = 0;

#define INITIAL_FRAME_SIZE 4 // Kích thước khung ban đầu
int frameSize = INITIAL_FRAME_SIZE;

void setup() {
  Serial.begin(115200);
  SPI.begin(4, 5, 6, 7);       // SPI (MOSI, MISO, SCK, SS)
  mfrc522.PCD_Init();          // Khởi tạo module RFID
  connectToWiFi();             // Kết nối Wi-Fi
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }

  performDFSA(); // Thực hiện DFSA
  delay(5000);
}

void performDFSA() {
  bool allTagsIdentified = false;
  while (!allTagsIdentified) {
    Serial.println("Starting DFSA Frame...");
    int *slots = new int[frameSize](); // Mảng lưu trạng thái của từng slot
    int collisions = 0;

    // Quét và phân bổ thẻ vào các slot
    while (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
      int slot = random(0, frameSize); // Chọn slot ngẫu nhiên
      slots[slot]++;
    }

    // Duyệt qua từng slot để xử lý
    for (int i = 0; i < frameSize; i++) {
      if (slots[i] == 1) { // Nhận diện thành công
        String cardID = getCardID();
        sendCardID(cardID);
        Serial.println("Tag identified in slot: " + String(i));
      } else if (slots[i] > 1) { // Xung đột
        collisions++;
        Serial.println("Collision in slot: " + String(i));
      }
    }

    // Điều chỉnh kích thước khung
    if (collisions > 0) {
      frameSize = min(frameSize * 2, 64); // Tăng khung (tối đa 64)
    } else {
      frameSize = max(frameSize / 2, 4);  // Giảm khung (tối thiểu 4)
    }

    Serial.println("Updated frame size: " + String(frameSize));
    allTagsIdentified = (collisions == 0);

    delete[] slots; // Giải phóng bộ nhớ
    delay(1000);
  }
}

String getCardID() {
  String cardID = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    cardID += String(mfrc522.uid.uidByte[i], HEX);
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
