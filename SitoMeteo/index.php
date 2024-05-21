<?php
// Connessione al database
$servername = "PC0128";
$username = "ServerMeteoDBPascal";
$password = "MeteoPascalReading";
$dbname = "meteodb";

// Connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica della connessione
if ($conn->connect_error) {
    die ("Connessione fallita: " . $conn->connect_error);
}

// Query per ottenere tutte le unità di misura disponibili
$sql_unita_misura = "SELECT DISTINCT GrandezzaFisica FROM grandezzafisica";
$result_unita_misura = $conn->query($sql_unita_misura);
$unita_misura_options = "";
if ($result_unita_misura->num_rows > 0) {
    while ($row_unita_misura = $result_unita_misura->fetch_assoc()) {
        $unita_misura_options .= "<option value='" . $row_unita_misura['GrandezzaFisica'] . "'>" . $row_unita_misura['GrandezzaFisica'] . "</option>";
    }
}

// Query per ottenere lo storico delle rilevazioni meteo filtrato per unità di misura se è stata selezionata
$sql = "SELECT st.idNomeStazione, r.Dato, g.SimboloUnitaDiMisuraAdottato, r.DataOra
        FROM rilevamenti r
        JOIN sensoriinstallati si ON r.idSensoriInstallati = si.idSensoriInstallati
        JOIN sensori s ON si.idCodiceSensore = s.idCodiceSensore
        JOIN grandezzafisica g ON s.idGrandezzaFisica = g.idGrandezzaFisica
        JOIN stazioni st ON si.idNomeStazione = st.idNomeStazione";
if (isset ($_GET['unita_misura']) && !empty ($_GET['unita_misura'])) {
    $unita_misura_selezionata = $_GET['unita_misura'];
    $sql .= " WHERE g.GrandezzaFisica = '$unita_misura_selezionata'";
}
$sql .= " ORDER BY r.DataOra DESC";

$result = $conn->query($sql);

// Chiudi la connessione al database
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <title>Storico Rilevazioni Meteo</title>
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
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
    <form method="GET" action="">
    <div class="container-sm">
        <label for="unita_misura">Seleziona unità di misura:</label>
        <select name="unita_misura" id="unita_misura" class="form-select" aria-label="Default select example">
            <option value="">Tutte</option>
            <?= $unita_misura_options ?>
        </select>
        <input type="submit" value="Filtra" class="btn btn-primary">
    </div>
    </form>
    <div class="container-sm">
        <div class="row justify-content-center">
            <table border="1" class="table table-primary table-striped table-responsive">
                <tr>
                    <th>Nome stazione</th>
                    <th>Dato</th>
                    <th>Data e Ora</th>
                </tr>
                <?php
                // Verifica se ci sono risultati
                if ($result->num_rows > 0) {
                    // Output dei dati
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr class='bg-primary'>
                <td>" . $row["idNomeStazione"] . "</td>
                <td>" . $row["Dato"] . $row["SimboloUnitaDiMisuraAdottato"] . "</td>
                <td>" . $row["DataOra"] . "</td>
                    </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Nessun risultato trovato</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>