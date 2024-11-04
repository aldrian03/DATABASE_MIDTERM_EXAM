<?php
session_start();

// Check if the user is logged in, if not redirect to the login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Initialize variables
$toys = [];
$rating_msg = "";

// Fetch up to 10 toys from the database
try {
    $sql = "SELECT * FROM toys LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $toys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching toys: " . $e->getMessage();
}

// Handle ratings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rate'])) {
    $toy_id = $_POST['toy_id'];
    $rating = $_POST['rating'];

    // Validate rating
    if ($rating < 1 || $rating > 10) {
        $rating_msg = "Rating must be between 1 and 10.";
    } else {
        // Check if user has already rated this toy
        $check_sql = "SELECT * FROM toy_ratings WHERE user_id = :user_id AND toy_id = :toy_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':user_id', $_SESSION['id']);
        $check_stmt->bindParam(':toy_id', $toy_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() == 0) {
            // Insert new rating
            $insert_sql = "INSERT INTO toy_ratings (user_id, toy_id, rating) VALUES (:user_id, :toy_id, :rating)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->bindParam(':user_id', $_SESSION['id']);
            $insert_stmt->bindParam(':toy_id', $toy_id);
            $insert_stmt->bindParam(':rating', $rating);
            $insert_stmt->execute();

            $rating_msg = "Thank you for rating the toy!";
        } else {
            $rating_msg = "You have already rated this toy.";
        }
    }
}

// Fetch user's ratings for displaying existing ratings
$user_ratings = [];
$rating_query = "SELECT toy_id, rating FROM toy_ratings WHERE user_id = :user_id";
$rating_stmt = $pdo->prepare($rating_query);
$rating_stmt->bindParam(':user_id', $_SESSION['id']);
$rating_stmt->execute();
$user_ratings = $rating_stmt->fetchAll(PDO::FETCH_ASSOC);

// Close connection
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif; /* Artistic font */
            background-color: black; /* Main background color */
            color: #FFD700; /* Gold color for text */
            padding: 20px;
        }
        .navbar {
            margin-bottom: 20px;
            background-color: black; /* Navbar background */
        }
        .navbar-brand,
        .nav-link {
            color: #FFD700 !important; /* Navbar text color */
        }
        .container {
            margin-top: 20px;
            background-color: black; /* Container background color */
            padding: 30px; /* Padding inside container */
            border-radius: 20px; /* Rounded corners */
            border: 2px solid #FFD700; /* Yellow border */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5); /* Shadow for depth */
        }
        .toy-card {
            margin-bottom: 20px;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.1); /* Transparent background */
            backdrop-filter: blur(5px); /* Blur effect */
            color: #FFD700; /* Yellow font color */
            border-radius: 15px; /* Rounded corners */
            border: 2px solid #FFD700; /* Yellow border */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Card shadow */
            transition: transform 0.2s; /* Smooth hover effect */
        }
        .card:hover {
            transform: translateY(-5px); /* Lift effect on hover */
        }
        .card-img-top {
            width: 100%; /* Make images responsive */
            height: 200px; /* Set a fixed height */
            object-fit: cover; /* Crop the image to fit */
            border-top-left-radius: 15px; /* Rounded corners for image */
            border-top-right-radius: 15px; /* Rounded corners for image */
        }
        .card-title {
            text-align: center; /* Center the toy name */
            border: 2px solid #FFD700; /* Yellow border around the toy name */
            border-radius: 5px; /* Slightly rounded corners for the border */
            padding: 5px; /* Padding for the text */
            margin-bottom: 15px; /* Margin below the title */
            font-weight: bold; /* Make the font bold */
        }
        .alert-info {
            background-color: rgba(255, 215, 0, 0.9); /* Transparent yellow for alerts */
            color: black; /* Text color for alerts */
        }
        .alert-warning {
            background-color: rgba(255, 165, 0, 0.9); /* Different shade for warnings */
            color: black; /* Text color for alerts */
        }
        .btn-primary {
            background-color: #FFD700; /* Button background color */
            color: black; /* Button text color */
            border: none; /* No border */
            transition: background-color 0.3s; /* Smooth hover effect */
        }
        .btn-primary:hover {
            background-color: #ffcc00; /* Lighter yellow on hover */
        }
    </style>
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
                    <a class="nav-link" href="join.php">Join</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>

        <?php if (!empty($rating_msg)): ?>
            <div class="alert alert-info"><?php echo $rating_msg; ?></div>
        <?php endif; ?>

        <div class="row">
            <?php if (count($toys) > 0): ?>
                <?php foreach ($toys as $toy): ?>
                    <div class="col-md-4 toy-card">
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($toy['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($toy['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($toy['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($toy['description']); ?></p>

                                <?php
                                $user_rating = null;
                                foreach ($user_ratings as $user_rating_row) {
                                    if ($user_rating_row['toy_id'] == $toy['id']) {
                                        $user_rating = $user_rating_row['rating'];
                                        break;
                                    }
                                }
                                ?>

                                <?php if ($user_rating !== null): ?>
                                    <p>Your rating: <?php echo htmlspecialchars($user_rating); ?> (You can't change this rating)</p>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="toy_id" value="<?php echo $toy['id']; ?>">
                                        <div class="form-group">
                                            <label for="rating">Rate this toy (1-10):</label>
                                            <select name="rating" class="form-control" required>
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="rate" class="btn btn-primary">Submit Rating</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning">No toys available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
