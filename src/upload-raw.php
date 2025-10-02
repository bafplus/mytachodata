<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dddfile'])) {

    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedFile = $uploadDir . basename($_FILES['dddfile']['name']);

    if (move_uploaded_file($_FILES['dddfile']['tmp_name'], $uploadedFile)) {

        // Build parser command
        $cmd = escapeshellcmd("dddparser -card -input " . escapeshellarg($uploadedFile) . " -format");

        // Execute parser and capture JSON output
        $output = shell_exec($cmd);

        if ($output) {
            header("Content-Type: application/json");
            echo $output;
        } else {
            echo json_encode(["error" => "Parser returned no output"]);
        }

    } else {
        echo json_encode(["error" => "Failed to save uploaded file"]);
    }

} else {
    // Simple upload form
    ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="dddfile" accept=".ddd" />
        <input type="submit" value="Upload & Parse" />
    </form>
    <?php
}
