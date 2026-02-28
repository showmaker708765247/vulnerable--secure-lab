<?php 
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

if (isset($_POST['submit'])) { // Check press or not Post Comment Button
    $name = mysqli_real_escape_string($conn, $_POST['name']); // Basic sanitization
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    $sql = "INSERT INTO comments (name, email, comment)
            VALUES ('$name', '$email', '$comment')";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo "<script>alert('Comment added successfully.')</script>";
    } else {
        echo "<script>alert('Comment did not add.')</script>";
    }
}

// Handle search if needed
$search_result = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = "SELECT * FROM comments WHERE name LIKE '%$search%' OR comment LIKE '%$search%'";
    $search_result = mysqli_query($conn, $search_sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header/Navigation */
        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logout-btn {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
        }

        /* Form Styling */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .form-title {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
            border-left: 5px solid #667eea;
            padding-left: 15px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .textarea textarea {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        /* Quick Search Section (formerly XSS Demo) */
        .quick-search-section {
            background: linear-gradient(135deg, #e6f0ff 0%, #d4e4ff 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid #4299e1;
        }

        .quick-search-title {
            color: #2b6cb0;
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-search-title::before {
            content: "🔍";
            font-size: 24px;
        }

        .quick-search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .quick-search-box input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #90cdf4;
            border-radius: 10px;
            font-size: 14px;
        }

        .quick-search-box button {
            padding: 12px 30px;
            background: #4299e1;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .quick-search-box button:hover {
            background: #3182ce;
        }

        .quick-search-output {
            background: white;
            padding: 15px;
            border-radius: 10px;
            min-height: 60px;
            border: 2px dashed #90cdf4;
        }

        .quick-search-hint {
            background: #ebf8ff;
            color: #2c5282;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            border-left: 4px solid #4299e1;
        }

        .quick-search-hint code {
            background: #bee3f8;
            padding: 2px 5px;
            border-radius: 4px;
        }

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .search-title {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }

        .search-box button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
        }

        /* Comments Section */
        .comments-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .comments-title {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
            border-left: 5px solid #764ba2;
            padding-left: 15px;
        }

        .comments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .comment-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .comment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .comment-name {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .comment-email {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 10px;
            padding: 5px 10px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 5px;
        }

        .comment-email:hover {
            background: rgba(102, 126, 234, 0.2);
        }

        .comment-text {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 16px;
        }

        .search-highlight {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #856404;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .clear-search {
            color: #856404;
            text-decoration: underline;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <h1>💬 Interactive Comment System <?php echo $_GET['test'] ?? ''; ?></h1>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>

        <!-- Comment Form -->
        <div class="form-container">
            <h2 class="form-title">📝 Leave a Comment</h2>
            <form action="" method="POST">
                <div class="row">
                    <div class="input-group">
                        <label for="name">👤 Full Name</label>
                        <input type="text" name="name" id="name" placeholder="John Doe" required>
                    </div>
                    <div class="input-group">
                        <label for="email">📧 Email Address</label>
                        <input type="email" name="email" id="email" placeholder="john@example.com" required>
                    </div>
                </div>
                <div class="input-group textarea">
                    <label for="comment">💭 Your Comment</label>
                    <textarea id="comment" name="comment" placeholder="Share your thoughts..." required></textarea>
                </div>
                <div class="input-group">
                    <button type="submit" name="submit" class="btn">📬 Post Comment</button>
                </div>
            </form>
        </div>

        <!-- Quick Search (formerly XSS Demo - now disguised as a search feature) -->
        <div class="quick-search-section">
            <h2 class="quick-search-title">Quick Search</h2>
            <div class="quick-search-box">
                <input type="text" id="quickSearch" placeholder="Search for users or keywords...">
                <button onclick="quickSearch()">Search</button>
            </div>
            <div class="quick-search-output" id="quickSearchOutput">
                Search results will appear here
            </div>
            
        </div>

        <!-- Advanced Search Section -->
        <div class="search-section">
            <h2 class="search-title">🔍 Advanced Search</h2>
            <form action="" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search by name or comment..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">Search Database</button>
            </form>
            
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <div class="search-highlight">
                    <span>📊 Found results for: "<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                    <a href="?" class="clear-search">Clear search</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Comments Display -->
        <div class="comments-section">
            <h2 class="comments-title">💬 Recent Comments</h2>
            <div class="comments-grid">
                <?php 
                // Determine which comments to show
                if (isset($search_result) && $search_result && mysqli_num_rows($search_result) > 0) {
                    $comments = $search_result;
                } else {
                    $sql = "SELECT * FROM comments ORDER BY id DESC";
                    $comments = mysqli_query($conn, $sql);
                }

                if ($comments && mysqli_num_rows($comments) > 0) {
                    while ($row = mysqli_fetch_assoc($comments)) {
                ?>
                <div class="comment-card">
                    <h3 class="comment-name"><?php echo $row['name']; ?></h3>
                    <a href="mailto:<?php echo $row['email']; ?>" class="comment-email">
                        📧 <?php echo $row['email']; ?>
                    </a>
                    <p class="comment-text"><?php echo $row['comment']; ?></p>
                </div>
                <?php 
                    }
                } else {
                    echo '<div class="empty-state">📭 No comments yet. Be the first to comment!</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
    function quickSearch() {
        var searchTerm = document.getElementById('quickSearch').value;
        document.getElementById('quickSearchOutput').innerHTML = searchTerm;
        
       
        if (searchTerm.includes('<') || searchTerm.includes('>')) {
            console.log('🔍 Search feature processing input');
        }
    }

    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            document.getElementById('quickSearch').value = '<img src=x onerror=alert("XSS in Search!")>';
            quickSearch();
        }
    });
    </script>
</body>
</html>