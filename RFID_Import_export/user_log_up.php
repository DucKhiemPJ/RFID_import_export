<?php  
session_start();
?>
<div class="table-responsive" style="max-height: 500px;"> 
  <table class="table">
    <thead class="table-primary">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Serial Number</th>
        <th>Card UID</th>
        <th>Device Dep</th>
        <th>Date</th>
        <th>Time In</th>
        <th>Time Out</th>
      </tr>
    </thead>
    <tbody class="table-secondary">
      <?php
        // Kết nối cơ sở dữ liệu
        require 'connectDB.php';
        
        // Build dynamic conditions
        $conditions = ["1=1"]; // Default true condition
        $params = [];

        // Xử lý các tham số tìm kiếm từ form
        if (isset($_POST['log_date'])) {
            // Start Date
            if (!empty($_POST['date_sel_start']) && $_POST['date_sel_start'] != 0) {
                $conditions[] = "checkindate >= ?";
                $params[] = $_POST['date_sel_start'];
            }
            // End Date
            if (!empty($_POST['date_sel_end']) && $_POST['date_sel_end'] != 0) {
                $conditions[] = "checkindate <= ?";
                $params[] = $_POST['date_sel_end'];
            }
            // Time In
            if (isset($_POST['time_sel']) && $_POST['time_sel'] == "Time_in") {
                if (!empty($_POST['time_sel_start']) && $_POST['time_sel_start'] != 0) {
                    $conditions[] = "timein >= ?";
                    $params[] = $_POST['time_sel_start'];
                }
                if (!empty($_POST['time_sel_end']) && $_POST['time_sel_end'] != 0) {
                    $conditions[] = "timein <= ?";
                    $params[] = $_POST['time_sel_end'];
                }
            }
            // Time Out
            if (isset($_POST['time_sel']) && $_POST['time_sel'] == "Time_out") {
                if (!empty($_POST['time_sel_start']) && $_POST['time_sel_start'] != 0) {
                    $conditions[] = "timeout >= ?";
                    $params[] = $_POST['time_sel_start'];
                }
                if (!empty($_POST['time_sel_end']) && $_POST['time_sel_end'] != 0) {
                    $conditions[] = "timeout <= ?";
                    $params[] = $_POST['time_sel_end'];
                }
            }
            // Card UID
            if (!empty($_POST['card_sel']) && $_POST['card_sel'] != 0) {
                $conditions[] = "card_uid = ?";
                $params[] = $_POST['card_sel'];
            }
            // Device UID
            if (!empty($_POST['dev_uid']) && $_POST['dev_uid'] != 0) {
                $conditions[] = "device_uid = ?";
                $params[] = $_POST['dev_uid'];
            }
        }

        // Construct SQL query
        $sql = "SELECT * FROM users_logs WHERE " . implode(' AND ', $conditions) . " ORDER BY id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            // Bind parameters
            if (!empty($params)) {
                $types = str_repeat('s', count($params)); // Assume all parameters are strings
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
        ?>
                  <tr>
                  <td><?php echo htmlspecialchars($row['id']); ?></td>
                  <td><?php echo htmlspecialchars($row['username']); ?></td>
                  <td><?php echo htmlspecialchars($row['serialnumber']); ?></td>
                  <td><?php echo htmlspecialchars($row['card_uid']); ?></td>
                  <td><?php echo htmlspecialchars($row['device_dep']); ?></td>
                  <td><?php echo htmlspecialchars($row['checkindate']); ?></td>
                  <td><?php echo htmlspecialchars($row['timein']); ?></td>
                  <td><?php echo htmlspecialchars($row['timeout']); ?></td>
                  </tr>
        <?php
                }
            } else {
                echo "<tr><td colspan='8'>No records found</td></tr>";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo '<p class="error">SQL Error</p>';
        }

      ?>
    </tbody>
  </table>
</div>
