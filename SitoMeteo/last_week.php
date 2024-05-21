<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rilevamenti Meteo</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <a class="navbar-brand" href="home.php">Home</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="index.php" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Storico
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                        <a class="dropdown-item" href="index.php">-</a>
                        <a class="dropdown-item" href="index.php?unita_misura=Temperatura">Temperatura</a>
                        <a class="dropdown-item" href="index.php?unita_misura=Pressione">Pressione</a>
                        <a class="dropdown-item" href="index.php?unita_misura=Umidità relativa">Umidità</a>
                        <a class="dropdown-item" href="index.php?unita_misura=Direzione del vento">Direzione del vento</a>
                        <a class="dropdown-item" href="index.php?unita_misura=Velocità del vento">Velocità del vento</a>
                        <a class="dropdown-item" href="index.php?unita_misura=Pioggia">Pioggia</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Grandezza Fisica</th>
                                    <th>Data e Ora</th>
                                    <th>Dato</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                <?php
                                // Connessione al database
                                $conn = new mysqli('PC0128', 'ServerMeteoDBPascal', 'MeteoPascalReading', 'meteodb');
                                // Verifica della connessione
                                if ($conn->connect_error) {
                                    die("Connessione fallita: " . $conn->connect_error);
                                }

                               
                                // Costruzione della data e ora di inizio e fine nel formato italiano
                                $start_datetime = date('Y-m-d H:i:s', strtotime('-1 week'));
                                $end_datetime = date('Y-m-d H:i:s');

                                // Query per ottenere i rilevamenti nell'intervallo di tempo specificato
                                if ($start_datetime && $end_datetime) {
                                    $sql_readings = "SELECT grandezzafisica.GrandezzaFisica, rilevamenti.DataOra, rilevamenti.Dato
                                        FROM rilevamenti
                                        JOIN sensoriinstallati ON rilevamenti.idSensoriInstallati = sensoriinstallati.idSensoriInstallati
                                        JOIN sensori ON sensoriinstallati.idCodiceSensore = sensori.idCodiceSensore
                                        JOIN grandezzafisica ON sensori.idGrandezzaFisica = grandezzafisica.idGrandezzaFisica
                                        WHERE rilevamenti.DataOra BETWEEN '$start_datetime' AND '$end_datetime'
                                        ORDER BY rilevamenti.DataOra DESC";

                                    $result_readings = $conn->query($sql_readings);

                                    // Stampare i rilevamenti nella tabella
                                    if ($result_readings->num_rows > 0) {
                                        while ($row = $result_readings->fetch_assoc()) {
                                            echo "<tr>
                                                <td>" . $row["GrandezzaFisica"] . "</td>
                                                <td>" . date('d/m/Y H:i', strtotime($row["DataOra"])) . "</td>
                                                <td>" . $row["Dato"] . "</td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3'>Nessun dato trovato per l'intervallo di tempo selezionato</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>Si prega di fornire sia la data che l'ora di inizio e fine.</td></tr>";
                                }

                                // Chiudere la connessione al database
                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>