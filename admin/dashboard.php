<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';

// Get client IP
$user_ip = $_SERVER['REMOTE_ADDR'];

// Check if IP exists in DB and is active
$stmt = $conn->prepare("SELECT id FROM allowed_ips WHERE ip_address = ? AND status = 1 LIMIT 1");
$stmt->bind_param("s", $user_ip);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    // IP not allowed
    header("Location: denied.php");
    exit;
}

// Fetch current admin details
$auth_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, status, created_at FROM auth_users WHERE auth_id = ?");
$stmt->bind_param("i", $auth_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch dashboard stats
$users_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$active_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'")->fetch_assoc()['total'];
$content_count = $conn->query("SELECT COUNT(*) as total FROM content")->fetch_assoc()['total'];
$views_count = $conn->query("SELECT COUNT(*) as total FROM watch_history")->fetch_assoc()['total'];
$paid_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE subscription_status = 'paid'")->fetch_assoc()['total'];
$revenue = $conn->query("SELECT SUM(price) as total FROM subscription_history WHERE change_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?? 0.00;

// Fetch content distribution by main category
$result = $conn->query("SELECT mc.name as main_category_name, COUNT(c.content_id) as count 
                       FROM content c 
                       JOIN categories cat ON c.category_id = cat.category_id 
                       JOIN main_categories mc ON cat.main_category_id = mc.category_id 
                       GROUP BY mc.category_id, mc.name");
$main_categories = [];
$main_category_counts = [];
while ($row = $result->fetch_assoc()) {
    $main_categories[] = $row['main_category_name'];
    $main_category_counts[] = $row['count'];
}

// Fetch content distribution by language
$result = $conn->query("SELECT l.name as language_name, COUNT(c.content_id) as count 
                       FROM content c 
                       JOIN languages l ON c.language_id = l.language_id 
                       GROUP BY c.language_id, l.name");
$languages = [];
$language_counts = [];
while ($row = $result->fetch_assoc()) {
    $languages[] = $row['language_name'];
    $language_counts[] = $row['count'];
}

// Fetch subscription status distribution
$result = $conn->query("SELECT subscription_status, COUNT(*) as count FROM users GROUP BY subscription_status");
$subscription_labels = [];
$subscription_data = [];
while ($row = $result->fetch_assoc()) {
    $subscription_labels[] = ucfirst($row['subscription_status']);
    $subscription_data[] = $row['count'];
}

// Determine greeting based on IST time (11:20 PM)
$current_hour = (int)date('H');
$greeting = 'Good Evening'; // 6 PM - 12 AM
if ($current_hour >= 0 && $current_hour < 12) $greeting = 'Good Morning';
elseif ($current_hour >= 12 && $current_hour < 17) $greeting = 'Good Afternoon';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <title>OTT Admin Dashboard</title>
    <style>
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        #contentChartContainer, #languageChartContainer, #subscriptionChartContainer { height: 350px; }
        .dashboard-header {
            background-color: #1a73e8;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-header .login-info {
            text-align: right;
        }
        .table-responsive { overflow-x: auto; }
        .table-striped tbody tr:nth-child(odd) { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="content p-4">
        <div class="dashboard-header">
            <div>
                <p class="mb-0"><?php echo $greeting; ?>, <?php echo htmlspecialchars($admin['username']); ?> | <?php echo date('M d , Y '); ?></p>
            </div>
            <div class="login-info">
                <p class="mb-0">Last Login: <?php echo htmlspecialchars($admin['created_at']); ?></p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-0">Total Users</h6>
                        <h3 class="mb-0"><?php echo $users_count; ?></h3>
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-0">Active Users</h6>
                        <h3 class="mb-0"><?php echo $active_users; ?></h3>
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-0">Content Items</h6>
                        <h3 class="mb-0"><?php echo $content_count; ?></h3>
                        <i class="fas fa-film"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-0">Total Views</h6>
                        <h3 class="mb-0"><?php echo $views_count; ?></h3>
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-0">Paid Users</h6>
                        <h3 class="mb-0"><?php echo $paid_users; ?></h3>
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-0">Last 30 Days Revenue (INR)</h6>
                        <h3 class="mb-0"><?php echo number_format($revenue, 2); ?></h3>
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Content by Main Category</div>
                    <div class="card-body" id="contentChartContainer">
                        <canvas id="contentChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Content by Language</div>
                    <div class="card-body" id="languageChartContainer">
                        <canvas id="languageChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Users by Subscription</div>
                    <div class="card-body" id="subscriptionChartContainer">
                        <canvas id="subscriptionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Content -->
        <div class="card mb-4">
            <div class="card-header">Recent Content</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="contentTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Main Category</th>
                                <th>Language</th>
                                <th>Release Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT c.*, mc.name as main_category_name, l.name as language_name 
                                                  FROM content c 
                                                  JOIN categories cat ON c.category_id = cat.category_id 
                                                  JOIN main_categories mc ON cat.main_category_id = mc.category_id 
                                                  JOIN languages l ON c.language_id = l.language_id 
                                                  ORDER BY c.release_date DESC LIMIT 5");
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                    <td>" . htmlspecialchars($row['main_category_name']) . "</td>
                                    <td>" . htmlspecialchars($row['language_name']) . "</td>
                                    <td>" . htmlspecialchars($row['release_date']) . "</td>
                                    <td>
                                        <a href='manage_content.php?form=edit&id={$row['content_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>
                                        <form method='post' action='../api/content.php' style='display:inline;'>
                                            <input type='hidden' name='action' value='delete_content'>
                                            <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                            <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Content by Main Category Chart
        const contentCtx = document.getElementById('contentChart').getContext('2d');
        new Chart(contentCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($main_categories); ?>,
                datasets: [{
                    label: 'Content Count',
                    data: <?php echo json_encode($main_category_counts); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Content by Main Category' }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Items' } }
                }
            }
        });

        // Content by Language Chart
        const languageCtx = document.getElementById('languageChart').getContext('2d');
        new Chart(languageCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($languages); ?>,
                datasets: [{
                    label: 'Content Count',
                    data: <?php echo json_encode($language_counts); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Content by Language' }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Items' } }
                }
            }
        });

        // Users by Subscription Chart
        const subscriptionCtx = document.getElementById('subscriptionChart').getContext('2d');
        new Chart(subscriptionCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($subscription_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($subscription_data); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Users by Subscription' }
                }
            }
        });
    </script>
    <?php $conn->close(); ?>
</body>
</html>