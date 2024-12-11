<?php  
// Kết nối đến cơ sở dữ liệu
require 'connectDB.php';

// Thêm người dùng
if (isset($_POST['Add'])) {
    $user_id = $_POST['user_id'];
    $Uname = $_POST['name'];
    $Number = $_POST['number'];
    $Email = $_POST['email'];
    $dev_uid = $_POST['dev_uid'];
    $Gender = $_POST['gender'];

    // Kiểm tra xem có người dùng nào được chọn hay không
    $sql = "SELECT add_card FROM users WHERE id=?";
    $result = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($result, $sql)) {
        echo "SQL_Error: " . mysqli_error($conn);
        exit();
    } else {
        mysqli_stmt_bind_param($result, "i", $user_id);
        mysqli_stmt_execute($result);
        $resultl = mysqli_stmt_get_result($result);
        if ($row = mysqli_fetch_assoc($resultl)) {
            if ($row['add_card'] == 0) {
                if (!empty($Uname) && !empty($Number) && !empty($Email)) {
                    // Kiểm tra nếu đã có người dùng nào có Serial Number giống
                    $sql = "SELECT serialnumber FROM users WHERE serialnumber=? AND id NOT LIKE ?";
                    $result = mysqli_stmt_init($conn);
                    if (!mysqli_stmt_prepare($result, $sql)) {
                        echo "SQL_Error: " . mysqli_error($conn);
                        exit();
                    } else {
                        mysqli_stmt_bind_param($result, "di", $Number, $user_id);
                        mysqli_stmt_execute($result);
                        $resultl = mysqli_stmt_get_result($result);
                        if (!$row = mysqli_fetch_assoc($resultl)) {
                            $sql = "SELECT device_dep FROM devices WHERE device_uid=?";
                            $result = mysqli_stmt_init($conn);
                            if (!mysqli_stmt_prepare($result, $sql)) {
                                echo "SQL_Error: " . mysqli_error($conn);
                                exit();
                            } else {
                                mysqli_stmt_bind_param($result, "s", $dev_uid);
                                mysqli_stmt_execute($result);
                                $resultl = mysqli_stmt_get_result($result);
                                if ($row = mysqli_fetch_assoc($resultl)) {
                                    $dev_name = $row['device_dep'];
                                } else {
                                    $dev_name = "All";
                                }
                            }
                            $sql = "UPDATE users SET username=?, serialnumber=?, gender=?, email=?, user_date=CURDATE(), device_uid=?, device_dep=?, add_card=1 WHERE id=?";
                            $result = mysqli_stmt_init($conn);
                            if (!mysqli_stmt_prepare($result, $sql)) {
                                echo "SQL_Error_select_Fingerprint: " . mysqli_error($conn);
                                exit();
                            } else {
                                mysqli_stmt_bind_param($result, "sdssssi", $Uname, $Number, $Gender, $Email, $dev_uid, $dev_name, $user_id);
                                mysqli_stmt_execute($result);
                                echo 1;
                                exit();
                            }
                        } else {
                            echo "The serial number is already taken!";
                            exit();
                        }
                    }
                } else {
                    echo "Empty Fields";
                    exit();
                }
            } else {
                echo "This User already exists";
                exit();
            }
        } else {
            echo "There's no selected Card!";
            exit();
        }
    }
}

// Cập nhật người dùng đã tồn tại
if (isset($_POST['Update'])) {
    $user_id = $_POST['user_id'];
    $Uname = $_POST['name'];
    $Number = $_POST['number'];
    $Email = $_POST['email'];
    $dev_uid = $_POST['dev_uid'];
    $Gender = $_POST['gender'];

    // Kiểm tra nếu người dùng đã được chọn
    $sql = "SELECT add_card FROM users WHERE id=?";
    $result = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($result, $sql)) {
        echo "SQL_Error: " . mysqli_error($conn);
        exit();
    } else {
        mysqli_stmt_bind_param($result, "i", $user_id);
        mysqli_stmt_execute($result);
        $resultl = mysqli_stmt_get_result($result);
        if ($row = mysqli_fetch_assoc($resultl)) {
            if ($row['add_card'] == 0) {
                echo "First, You need to add the User!";
                exit();
            } else {
                if (empty($Uname) && empty($Number) && empty($Email)) {
                    echo "Empty Fields";
                    exit();
                } else {
                    // Kiểm tra nếu đã có người dùng nào có Serial Number giống
                    $sql = "SELECT serialnumber FROM users WHERE serialnumber=? AND id NOT LIKE ?";
                    $result = mysqli_stmt_init($conn);
                    if (!mysqli_stmt_prepare($result, $sql)) {
                        echo "SQL_Error: " . mysqli_error($conn);
                        exit();
                    } else {
                        mysqli_stmt_bind_param($result, "di", $Number, $user_id);
                        mysqli_stmt_execute($result);
                        $resultl = mysqli_stmt_get_result($result);
                        if (!$row = mysqli_fetch_assoc($resultl)) {
                            $sql = "SELECT device_dep FROM devices WHERE device_uid=?";
                            $result = mysqli_stmt_init($conn);
                            if (!mysqli_stmt_prepare($result, $sql)) {
                                echo "SQL_Error: " . mysqli_error($conn);
                                exit();
                            } else {
                                mysqli_stmt_bind_param($result, "s", $dev_uid);
                                mysqli_stmt_execute($result);
                                $resultl = mysqli_stmt_get_result($result);
                                if ($row = mysqli_fetch_assoc($resultl)) {
                                    $dev_name = $row['device_dep'];
                                } else {
                                    $dev_name = "All";
                                }
                            }

                            if (!empty($Uname) && !empty($Email)) {
                                $sql = "UPDATE users SET username=?, serialnumber=?, gender=?, email=?, device_uid=?, device_dep=? WHERE id=?";
                                $result = mysqli_stmt_init($conn);
                                if (!mysqli_stmt_prepare($result, $sql)) {
                                    echo "SQL_Error_select_Card: " . mysqli_error($conn);
                                    exit();
                                } else {
                                    mysqli_stmt_bind_param($result, "sdssssi", $Uname, $Number, $Gender, $Email, $dev_uid, $dev_name, $user_id);
                                    mysqli_stmt_execute($result);
                                    echo 1;
                                    exit();
                                }
                            }
                        } else {
                            echo "The serial number is already taken!";
                            exit();
                        }
                    }
                }
            }
        } else {
            echo "There's no selected User to be updated!";
            exit();
        }
    }
}

// Chọn người dùng (thẻ)
if (isset($_GET['select'])) {
    $card_uid = $_GET['card_uid'];

    $sql = "SELECT * FROM users WHERE card_uid=?";
    $result = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($result, $sql)) {
        echo "SQL_Error_Select: " . mysqli_error($conn);
        exit();
    } else {
        mysqli_stmt_bind_param($result, "s", $card_uid);
        mysqli_stmt_execute($result);
        $resultl = mysqli_stmt_get_result($result);
        //echo "User Fingerprint selected";
        //exit();
        // Trả về dữ liệu dưới dạng JSON
        header('Content-Type: application/json');
        $data = array();
        if ($row = mysqli_fetch_assoc($resultl)) {
            foreach ($resultl as $row){
                $data[] = $row;}
        }
        $resultl->close();
        $conn->close();
        print json_encode($data);  // Trả về JSON
    }
}

// Xóa người dùng
if (isset($_POST['delete'])) {
    $user_id = $_POST['user_id'];

    if (empty($user_id)) {
        echo "There's no selected user to remove";
        exit();
    } else {
        $sql = "DELETE FROM users WHERE id=?";
        $result = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($result, $sql)) {
            echo "SQL_Error_delete: " . mysqli_error($conn);
            exit();
        } else {
            mysqli_stmt_bind_param($result, "i", $user_id);
            mysqli_stmt_execute($result);
            echo 1;
            exit();
        }
    }
}
?>
