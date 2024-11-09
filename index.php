<?php
require 'aws/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Configure MinIO client
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'endpoint' => 'http://localhost:9000', // MinIO server URL
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key'    => 'minioadmin',
        'secret' => 'minioadmin',
    ],
]);

// Database connection
$link = mysqli_connect("localhost", "root", "", "miniotest");
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_name']) && isset($_FILES['file'])) {
    $docName = mysqli_real_escape_string($link, $_POST['doc_name']);
    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $filePath = $file['tmp_name'];
        $fileName = basename($file['name']);

        try {
            // Upload file to MinIO
            $result = $s3Client->putObject([
                'Bucket' => 'dae.practice',
                'Key'    => $fileName,
                'Body'   => fopen($filePath, 'r'),
                'ACL'    => 'public-read',
            ]);

            // Save document entry in the database
            $query = "INSERT INTO documents (doc_name, file_key) VALUES ('$docName', '$fileName')";
            if (mysqli_query($link, $query)) {
                // Redirect after successful upload to avoid re-upload on refresh
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error: " . mysqli_error($link);
            }
        } catch (AwsException $e) {
            echo "Error uploading file: " . $e->getMessage();
        }
    } else {
        echo "File upload error.";
    }
}

// Retrieve documents for listing
$documents = mysqli_query($link, "SELECT * FROM documents");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Upload and Listing</title>
</head>
<body>
    <h1>Upload Document</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="doc_name">Document Name:</label>
        <input type="text" id="doc_name" name="doc_name" required>
        <label for="file">Choose File:</label>
        <input type="file" id="file" name="file" required>
        <button type="submit">Upload</button>
    </form>

    <h2>Uploaded Documents</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Document Name</th>
                <th>View File</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($doc = mysqli_fetch_assoc($documents)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($doc['doc_name']); ?></td>
                    <td><a href="view_file.php?file_key=<?php echo urlencode($doc['file_key']); ?>" target="_blank">View</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
