<?php
// include config.php which has database login information
// config.php has variables $upload_folder and $max_file_size
include '/var/www/config.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Make sure the file was sent via POST
if(isset($_FILES['zip_file']) && $_FILES['zip_file']['error'] == 0) {
    // Make sure the file is not too big
    if($_FILES['zip_file']['size'] <= $max_file_size) {
        // Make sure the file was uploaded without error
        if($_FILES['zip_file']['error'] === UPLOAD_ERR_OK) {
            // Make sure the file is a zip file
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if($finfo->file($_FILES['zip_file']['tmp_name']) === 'application/zip') {
                // Make sure the API key is valid
                $api_key = $_POST['api_key'];
                $stmt = $pdo->prepare('SELECT * FROM api_keys WHERE api_key = :api_key');
                $stmt->execute(['api_key' => $api_key]);
                $key_row = $stmt->fetch();
                if($key_row) {
                    // Make sure the destination folder is writable
                    if(is_writable($upload_folder)) {
                        // check if there is already a file with the same name
                        $file_path = $upload_folder . $_FILES['zip_file']['name'];
                        if(!file_exists($file_path)) {
                            // move the uploaded file
                            if(move_uploaded_file($_FILES['zip_file']['tmp_name'], $file_path)) {
                                echo "File uploaded successfully!";
                            } else {
                                http_response_code(500);
                                echo "Error uploading file - check destination folder permissions.";
                            }
                        } else {
                            http_response_code(409);
                            echo "File already exists.";
                        }
                    } else {
                      http_response_code(500);
                      echo "Error uploading file - check destination folder permissions.";
                  }
              } else {
                  http_response_code(401);
                  echo "Invalid API key.";
              }
          } else {
              http_response_code(415);
              echo "Invalid file type. Only zip files are allowed.";
          }
      } else {
          http_response_code(500);
          echo "Error uploading file - please try again.";
      }
  } else {
      http_response_code(413);
      echo "File size exceeds maximum limit of 1GB.";
  }
} else {
  http_response_code(400);
  echo "No file was uploaded.";
}
