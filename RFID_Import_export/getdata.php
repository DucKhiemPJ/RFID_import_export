<?php  
// Kết nối cơ sở dữ liệu
require 'connectDB.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$d = date("Y-m-d");
$t = date("H:i:sa");

// Xác thực và lọc tham số GET
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if (isset($_GET['card_uid']) && isset($_GET['device_token'])) {
    $card_uid = sanitize_input($_GET['card_uid']);
    $device_uid = sanitize_input($_GET['device_token']);

    // Kiểm tra thông tin thiết bị
    if ($device_info = get_device_info($conn, $device_uid)) {
        $device_mode = $device_info['device_mode'];
        $device_dep = $device_info['device_dep'];

        if ($device_mode == 1) {
            // Chế độ đăng nhập / đăng xuất
            if ($user_info = get_user_info($conn, $card_uid)) {
                handle_user_login_logout($conn, $user_info, $card_uid, $device_uid, $device_dep, $d, $t);
            } else {
                echo "Not found!";
            }
        } elseif ($device_mode == 0) {
            // Chế độ đăng ký thẻ mới
            if ($user_info = get_user_info($conn, $card_uid)) {
                handle_existing_card($conn, $card_uid, $device_uid, $device_dep);
            } else {
                handle_new_card($conn, $card_uid, $device_uid, $device_dep);
            }
        }
    } else {
        echo "Invalid Device!";
    }
} else {
    echo "Invalid Input!";
}

// Lấy thông tin thiết bị từ cơ sở dữ liệu
function get_device_info($conn, $device_uid) {
    $sql = "SELECT * FROM devices WHERE device_uid=?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $device_uid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    } else {
        error_log("SQL_Error_Select_device");
        return false;
    }
}

// Lấy thông tin người dùng từ cơ sở dữ liệu
function get_user_info($conn, $card_uid) {
    $sql = "SELECT * FROM users WHERE card_uid=?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $card_uid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    } else {
        error_log("SQL_Error_Select_card");
        return false;
    }
}

// Xử lý đăng nhập và đăng xuất của người dùng
function handle_user_login_logout($conn, $user_info, $card_uid, $device_uid, $device_dep, $d, $t) {
    if ($user_info['add_card'] == 1) {
        if ($user_info['device_uid'] == $device_uid || $user_info['device_uid'] == 0) {
            $Uname = $user_info['username'];
            $Number = $user_info['serialnumber'];

            $sql = "SELECT * FROM users_logs WHERE card_uid=? AND checkindate=? AND card_out=0";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $card_uid, $d);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                // Đăng nhập
                if (!mysqli_fetch_assoc($result)) {
                    insert_user_log($conn, $Uname, $Number, $card_uid, $device_uid, $device_dep, $d, $t, "00:00:00");
                    echo "login " . $Uname;
                }
                // Đăng xuất
                else {
                    update_user_log($conn, $card_uid, $d, $t);
                    echo "logout " . $Uname;
                }
            } else {
                error_log("SQL_Error_Select_logs");
            }
        } else {
            echo "Not Allowed!";
        }
    } else {
        echo "Not registered!";
    }
}

// Hàm chèn log người dùng
function insert_user_log($conn, $Uname, $Number, $card_uid, $device_uid, $device_dep, $d, $t, $timeout) {
    $sql = "INSERT INTO users_logs (username, serialnumber, card_uid, device_uid, device_dep, checkindate, timein, timeout) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sdssssss", $Uname, $Number, $card_uid, $device_uid, $device_dep, $d, $t, $timeout);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("SQL Error in INSERT: " . mysqli_error($conn));
        } else {
            error_log("INSERT successful for card_uid: $card_uid on date: $d at time: $t");
        }
    } else {
        error_log("SQL Error in INSERT prepare: " . mysqli_error($conn));
    }
}


// Hàm cập nhật log khi đăng xuất
function update_user_log($conn, $card_uid, $d, $t) {
    $sql = "UPDATE users_logs SET timeout=?, card_out=1 WHERE card_uid=? AND checkindate=? AND card_out=0";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $t, $card_uid, $d);
        mysqli_stmt_execute($stmt);
    } else {
        error_log("SQL_Error_insert_logout");
    }
}

// Hàm xử lý thẻ đã tồn tại
function handle_existing_card($conn, $card_uid, $device_uid, $device_dep) {
    $sql = "SELECT card_select FROM users WHERE card_select=1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_fetch_assoc($result)) {
            update_card_select($conn, $card_uid);
        } else {
            update_card_select($conn, $card_uid);
        }
    } else {
        error_log("SQL_Error_Select_card_select");
    }
}

// Hàm xử lý thẻ mới
function handle_new_card($conn, $card_uid, $device_uid, $device_dep) {
    $sql = "UPDATE users SET card_select=0";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $sql = "INSERT INTO users (card_uid, card_select, device_uid, device_dep, user_date) VALUES (?, 1, ?, ?, CURDATE())";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $card_uid, $device_uid, $device_dep);
            mysqli_stmt_execute($stmt);
            echo "successful";
        } else {
            error_log("SQL_Error_Select_add_card");
        }
    } else {
        error_log("SQL_Error_insert_default_card_select");
    }
}

// Cập nhật trạng thái thẻ được chọn
function update_card_select($conn, $card_uid) {
    $sql = "UPDATE users SET card_select=1 WHERE card_uid=?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $card_uid);
        mysqli_stmt_execute($stmt);
        echo "available";
    } else {
        error_log("SQL_Error_update_card_select");
    }
}
?>
