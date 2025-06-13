<?php
// ---- Database Connection ----
$db_url = getenv('DATABASE_URL');
if (!$db_url) {
    die("Database connection URL not found. Please set the DATABASE_URL environment variable.");
}
$db_parts = parse_url($db_url);
$host = $db_parts['host'];
$port = $db_parts['port'];
$dbname = ltrim($db_parts['path'], '/');
$user = $db_parts['user'];
$password = $db_parts['pass'];
$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password";
$db_conn = pg_connect($conn_str);

if (!$db_conn) {
    die("Error in connection: " . pg_last_error());
}

// ---- Table Creation (Sirf ek baar chalega) ----
$create_table_query = "
CREATE TABLE IF NOT EXISTS entries (
    id SERIAL PRIMARY KEY,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
pg_query($db_conn, $create_table_query);

// ---- Handle Form Submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['key_data'])) {
    $data_to_save = $_POST['key_data'];
    $escaped_data = pg_escape_string($db_conn, $data_to_save);
    $insert_query = "INSERT INTO entries (content) VALUES ('$escaped_data')";
    $result = pg_query($db_conn, $insert_query);
    if (!$result) {
        echo "Error saving data: " . pg_last_error($db_conn);
    } else {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Data Store</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        input[type="text"] { width: calc(100% - 80px); padding: 10px; } button { padding: 10px 15px; }
        ul { list-style-type: none; padding: 0; } li { background-color: #f4f4f4; border: 1px solid #ddd; margin-bottom: 5px; padding: 10px; }
    </style>
</head>
<body>
    <h1>Save Your Key/Data</h1>
    <form action="index.php" method="POST">
        <input type="text" name="key_data" placeholder="Enter anything here..." required>
        <button type="submit">Save</button>
    </form>
    <hr>
    <h2>Saved Entries</h2>
    <ul>
        <?php
        $fetch_query = "SELECT content, created_at FROM entries ORDER BY id DESC";
        $result = pg_query($db_conn, $fetch_query);
        if (pg_num_rows($result) > 0) {
            while ($row = pg_fetch_assoc($result)) {
                echo "<li>" . htmlspecialchars($row['content']) . "</li>";
            }
        } else {
            echo "<li>No entries yet.</li>";
        }
        pg_close($db_conn);
        ?>
    </ul>
</body>
</html>
