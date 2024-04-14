<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["website"])) {
        // Code zum Hinzufügen einer Website
        $new_website = $_POST["website"];
        $config_file = 'config.json';
        $config_content = file_get_contents($config_file);
        if ($config_content === false) {
            echo "<div class='alert alert-danger' role='alert'>Error reading configuration file.</div>";
        } else {
            $config = json_decode($config_content, true);
            if ($config && isset($config['websites'])) {
                $config['websites'][] = $new_website;
                $json_content = json_encode($config, JSON_PRETTY_PRINT);
                if ($json_content !== false) {
                    $result = file_put_contents($config_file, $json_content);
                    if ($result !== false) {
                        echo "<div class='alert alert-success' role='alert'>Website added successfully.</div>";
                    } else {
                        echo "<div class='alert alert-danger' role='alert'>Error writing to configuration file.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger' role='alert'>Error encoding JSON data.</div>";
                }
            } else {
                echo "<div class='alert alert-danger' role='alert'>Invalid configuration file or no 'websites' key found.</div>";
            }
        }
    } elseif (isset($_POST["delete_index"])) {
        // Code zum Löschen einer Website
        $delete_index = $_POST["delete_index"];
        $config_file = 'config.json';
        $config_content = file_get_contents($config_file);
        if ($config_content === false) {
            echo "<div class='alert alert-danger' role='alert'>Error reading configuration file.</div>";
        } else {
            $config = json_decode($config_content, true);
            if ($config && isset($config['websites'][$delete_index])) {
                unset($config['websites'][$delete_index]);
                $json_content = json_encode($config, JSON_PRETTY_PRINT);
                if ($json_content !== false) {
                    $result = file_put_contents($config_file, $json_content);
                    if ($result !== false) {
                        echo "<div class='alert alert-success' role='alert'>Website deleted successfully.</div>";
                    } else {
                        echo "<div class='alert alert-danger' role='alert'>Error writing to configuration file.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger' role='alert'>Error encoding JSON data.</div>";
                }
            } else {
                echo "<div class='alert alert-danger' role='alert'>Invalid index or no such website found.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SSL Certificate Checker</title>
    <!-- Bootstrap CSS einbinden -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">SSL Certificate Checker</h1>

        <!-- Formular zur Hinzufügung von Websites -->
        <form method="post" class="mt-4">
            <div class="form-group">
                <label for="websiteInput">Website URL:</label>
                <input type="text" class="form-control" id="websiteInput" name="website" placeholder="https://example.com">
            </div>
            <button type="submit" class="btn btn-primary">Add Website</button>
        </form>

        <!-- Tabellarische Anzeige der Zertifikate -->
        <h2 class="mt-4">SSL Certificate Status</h2>
        <table class="table table-bordered mt-4">
            <thead class="thead-dark">
                <tr>
                    <th>Website</th>
                    <th>Expiry Date</th>
                    <th>Days Until Expiry</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Konfigurationsdatei laden
                $config_file = 'config.json';
                $config_content = file_get_contents($config_file);

                // Überprüfen, ob die Konfigurationsdatei erfolgreich geladen wurde
                if ($config_content === false) {
                    echo "<tr><td colspan='4'>Error reading configuration file.</td></tr>";
                } else {
                    // JSON-Daten in ein Array konvertieren
                    $config = json_decode($config_content, true);

                    // Überprüfen, ob das Array und das Schlüsselwort 'websites' vorhanden sind
                    if ($config && isset($config['websites'])) {
                        foreach ($config['websites'] as $index => $url) {
                            // Wenn die URL mit 'https://' beginnt, entferne dieses Präfix
                            $url = str_replace('https://', '', $url);

                            // Variablen für Zertifikatsprüfung initialisieren
                            $error_message = '';
                            $expiry_date = '-';
                            $days_until_expiry = '-';

                            // Verbindung zum Host aufbauen und Zertifikat überprüfen
                            $context = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
                            $stream = stream_socket_client("ssl://$url:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
                            if (!$stream) {
                                $error_message = "Error connecting to the website.";
                            } else {
                                $params = stream_context_get_params($stream);
                                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

                                $expiry_date = date('Y-m-d', $cert['validTo_time_t']);
                                $days_until_expiry = date_diff(date_create(), date_create_from_format('U', $cert['validTo_time_t']))->format('%a');

                                fclose($stream);
                            }

                            // Generate table row
                            echo "<tr>";
                            echo "<td>$url</td>";
                            echo "<td>$expiry_date</td>";
                            echo "<td>$days_until_expiry days</td>";
                            echo "<td>";
                            echo "<form method='post'>";
                            echo "<input type='hidden' name='delete_index' value='$index'>";
                            echo "<button type='submit' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this website?\")'>Delete</button>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Invalid configuration file or no websites found.</td></tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
