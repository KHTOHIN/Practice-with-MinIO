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

// Get file_key from query parameter
$fileKey = isset($_GET['file_key']) ? $_GET['file_key'] : '';

if ($fileKey) {
    try {
        // Retrieve file from MinIO
        $result = $s3Client->getObject([
            'Bucket' => 'dae.practice', // Replace with your actual bucket name
            'Key'    => $fileKey,
        ]);

        // Set appropriate headers and output the file content
        header("Content-Type: " . $result['ContentType']);
        header("Content-Disposition: inline; filename=\"" . basename($fileKey) . "\"");
        echo $result['Body'];
    } catch (AwsException $e) {
        // If an error occurs, display it
        echo "Error retrieving file: " . $e->getMessage();
    }
} else {
    echo "No file specified.";
}
?>
