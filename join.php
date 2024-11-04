<?php
session_start();

// Check if the user is logged in, if not redirect to the login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Initialize variables for tables
$left_join_data = [];
$right_join_data = [];
$union_data = [];

// Fetch data for Left Join
try {
    $left_join_sql = "SELECT users.username, toys.name AS toy_name, toys.description 
                      FROM users 
                      LEFT JOIN toy_ratings ON users.id = toy_ratings.user_id 
                      LEFT JOIN toys ON toy_ratings.toy_id = toys.id";
    $left_join_stmt = $pdo->prepare($left_join_sql);
    $left_join_stmt->execute();
    $left_join_data = $left_join_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching left join data: " . $e->getMessage();
}

// Fetch data for Right Join
try {
    $right_join_sql = "SELECT toys.id AS toy_id, toys.name AS toy_name, AVG(toy_ratings.rating) AS toy_rate 
                       FROM toys 
                       RIGHT JOIN toy_ratings ON toys.id = toy_ratings.toy_id 
                       GROUP BY toys.id";
    $right_join_stmt = $pdo->prepare($right_join_sql);
    $right_join_stmt->execute();
    $right_join_data = $right_join_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching right join data: " . $e->getMessage();
}

// Fetch data for Union Join
try {
    $union_sql = "SELECT toys.name AS toy_name, toys.description, toy_ratings.user_id, toy_ratings.rating 
                  FROM toys 
                  JOIN toy_ratings ON toys.id = toy_ratings.toy_id
                  UNION
                  SELECT toys.name AS toy_name, toys.description, NULL AS user_id, NULL AS rating 
                  FROM toys 
                  WHERE toys.id NOT IN (SELECT toy_id FROM toy_ratings)";
    $union_stmt = $pdo->prepare($union_sql);
    $union_stmt->execute();
    $union_data = $union_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching union data: " . $e->getMessage();
}

// Close connection
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join Operations</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: black;
            color: yellow;
            padding: 20px;
        }
        .navbar {
            margin-bottom: 20px;
            background-color: #333; /* Darker navbar background */
        }
        .navbar-brand,
        .nav-link {
            color: yellow !important; /* Navbar text color */
        }
        .btn {
            margin: 10px;
            background-color: yellow; /* Button background color */
            color: black; /* Button text color */
        }
        .btn:hover {
            background-color: #ffcc00; /* Lighter yellow on hover */
        }
        .data-table {
            margin-top: 20px;
            border: 1px solid yellow; /* Border color */
            padding: 15px; /* Padding for table */
            background-color: rgba(255, 255, 255, 0); /* Fully transparent */
        }
        th, td {
            color: yellow; /* Font color for table */
        }
    </style>
    <script>
        function showTable(tableId) {
            document.querySelectorAll('.data-table').forEach(table => {
                table.style.display = 'none';
            });
            document.getElementById(tableId).style.display = 'block';
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="#">Toy Rating</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2>Join Operations</h2>
        <button class="btn" onclick="showTable('leftJoinTable')">Left Join</button>
        <button class="btn" onclick="showTable('rightJoinTable')">Right Join</button>
        <button class="btn" onclick="showTable('unionTable')">Union Join</button>

        <!-- Left Join Table -->
        <div id="leftJoinTable" class="data-table" style="display: none;">
            <h3>Left Join Result</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Toy Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($left_join_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['toy_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Join Table -->
        <div id="rightJoinTable" class="data-table" style="display: none;">
            <h3>Right Join Result</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Toy ID</th>
                        <th>Toy Name</th>
                        <th>Toy Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($right_join_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['toy_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['toy_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['toy_rate']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Union Table -->
        <div id="unionTable" class="data-table" style="display: none;">
            <h3>Union Result</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Toy Name</th>
                        <th>Description</th>
                        <th>User ID</th>
                        <th>Toy Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($union_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['toy_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_id'] !== null ? $row['user_id'] : 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['rating'] !== null ? $row['rating'] : 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
