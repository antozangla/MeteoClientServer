<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Ultime Rilevazioni Meteo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">

    <a class="navbar-brand" href="#">
    <i class="bi bi-cloud-sun"></i>
    </a>
        <a class="nav-item nav-link active text-white" href="home.php" padding-left="20px" margin-right="20px">Home</a>
        <a class="nav-item nav-link text-white" href="last_week.php">Ultima settimana</a>
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
        <h1 class="mb-4">Meteo Realtime</h1>
        
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
            die("Connessione fallita: " . $conn->connect_error);
        }

        // Query per ottenere le ultime rilevazioni
        $sql = "SELECT r.idRilevamenti, s.Nome AS NomeSensore, g.GrandezzaFisica, r.DataOra, r.Dato, g.SimboloUnitaDiMisuraAdottato
                FROM rilevamenti r
                JOIN sensoriinstallati si ON r.idSensoriInstallati = si.idSensoriInstallati
                JOIN sensori s ON si.idCodiceSensore = s.idCodiceSensore
                JOIN grandezzafisica g ON s.idGrandezzaFisica = g.idGrandezzaFisica
                WHERE s.nome <> 'DHT22' AND g.GrandezzaFisica <> 'Immagine'
                ORDER BY r.DataOra DESC LIMIT 5"; // Limita a 5 rilevazioni più recenti

        $result = $conn->query($sql);

        // Percorso della cartella delle immagini
        $imageFolder = "\\\\PC0128\MeteoCAM\Pascal01\\";

        // Ottieni l'elenco dei file nella cartella delle immagini
        $imageFiles = scandir("\\\\PC0128\MeteoCAM\Pascal01", SCANDIR_SORT_DESCENDING);
        //echo $imageFiles[1];
        // Trova il primo file immagine nella cartella
        /*$imageFileName = '';
        foreach ($imageFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'jpg' || pathinfo($file, PATHINFO_EXTENSION) === 'jpeg' || pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                $imageFileName = $file;
                break;
            }
        }*/
        $imageFileName = $imageFiles[1];
        $image = fopen("\\\\PC0128\MeteoCAM\Pascal01\\" . $imageFileName, 'rb');
        $bytes= fread($image, 100000000);

        // Percorso completo dell'immagine
        //$imagePath = 'data:image/jpeg;base64, ' . base64_encode($bytes) .'';
        ?>

        <?php if (!empty($imageFileName)): ?>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Immagine Meteo</h5>
                            <img <?php echo "src='data:image/jpeg;base64,". base64_encode($bytes)."'"?> class="card-img-top" alt=<?php echo $imageFileName ?> />
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row["GrandezzaFisica"]; ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo $row["NomeSensore"]; ?></h6>
                                <p class="card-text"><?php echo $row["Dato"] . $row["SimboloUnitaDiMisuraAdottato"]; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nessuna rilevazione trovata.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-sun" viewBox="0 0 16 16">
    <path d="M7 8a3.5 3.5 0 0 1 3.5 3.555.5.5 0 0 0 .624.492A1.503 1.503 0 0 1 13 13.5a1.5 1.5 0 0 1-1.5 1.5H3a2 2 0 1 1 .1-3.998.5.5 0 0 0 .51-.375A3.5 3.5 0 0 1 7 8m4.473 3a4.5 4.5 0 0 0-8.72-.99A3 3 0 0 0 3 16h8.5a2.5 2.5 0 0 0 0-5z"/>
    <path d="M10.5 1.5a.5.5 0 0 0-1 0v1a.5.5 0 0 0 1 0zm3.743 1.964a.5.5 0 1 0-.707-.707l-.708.707a.5.5 0 0 0 .708.708zm-7.779-.707a.5.5 0 0 0-.707.707l.707.708a.5.5 0 1 0 .708-.708zm1.734 3.374a2 2 0 1 1 3.296 2.198q.3.423.516.898a3 3 0 1 0-4.84-3.225q.529.017 1.028.129m4.484 4.074c.6.215 1.125.59 1.522 1.072a.5.5 0 0 0 .039-.742l-.707-.707a.5.5 0 0 0-.854.377M14.5 6.5a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
</svg>
</body>
</html>
