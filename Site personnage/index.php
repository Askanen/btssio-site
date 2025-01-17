<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = 'root';
$port = 3306;
$database = 'btssio';

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

$sort = '';
$order = 'ASC';
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'prenom_asc') {
        $sort = 'prenom';
        $order = 'ASC';
    } elseif ($_GET['sort'] == 'prenom_desc') {
        $sort = 'prenom';
        $order = 'DESC';
    }
}

// Fetch distinct countries for the filter
$countries_sql = "SELECT DISTINCT pd.nom AS nom_pays FROM pays_des_personnages pd";
$countries_result = $conn->query($countries_sql);
$countries = [];
while ($country_row = $countries_result->fetch_assoc()) {
    $countries[] = $country_row['nom_pays'];
}

if (isset($_GET['country']) && $_GET['country'] != '') {
    $country_filter = $_GET['country'];
    $sql = "SELECT p.*, v.url, pd.drapeau, pd.nom AS nom_pays, pd.capitale FROM perso p 
            LEFT JOIN visuel v ON p.id_perso = v.id_visuel 
            LEFT JOIN pays_des_personnages pd ON p.id_perso = pd.id_pays 
            WHERE (p.nom LIKE '%$search%' OR p.prenom LIKE '%$search%') AND pd.nom = '" . $conn->real_escape_string($country_filter) . "' 
            ORDER BY p." . ($sort ? $sort : 'id_perso') . " " . $order;
} else {
    $sql = "SELECT p.*, v.url, pd.drapeau, pd.nom AS nom_pays, pd.capitale FROM perso p 
            LEFT JOIN visuel v ON p.id_perso = v.id_visuel 
            LEFT JOIN pays_des_personnages pd ON p.id_perso = pd.id_pays 
            WHERE p.nom LIKE '%$search%' OR p.prenom LIKE '%$search%' 
            ORDER BY p." . ($sort ? $sort : 'id_perso') . " " . $order;
}

$result = $conn->query($sql);

$succes_sql = "SELECT s.id_perso, s.resume AS succes_resume, s.description AS succes_description FROM succes s";
$succes_result = $conn->query($succes_sql);

$succes_data = [];
while ($succes_row = $succes_result->fetch_assoc()) {
    $succes_data[$succes_row['id_perso']][] = $succes_row;
}

// Fetch all character IDs and names for random selection
$ids_sql = "SELECT id_perso, nom, prenom FROM perso";
$ids_result = $conn->query($ids_sql);
$characters = [];
while ($id_row = $ids_result->fetch_assoc()) {
    $characters[] = $id_row;
}
?>

<!-- 
BDD :

host : localhost
user : root
password : root
port : 3306
database : btssio
tables :
- pays_des_personnages :
    - id_pays (autoincrement)
    - nom (VARCHAR)
    - capitale (VARCHAR)
    - drapeau (VARCHAR)
- perso
    - id_perso (autoincrement)
    - nom (VARCHAR)
    - prenom (VARCHAR)
    - date_naissance (VARCHAR)
    - date_deces (VARCHAR)
    - biographie (TEXT)
    - resume (TEXT)
- succes
    - id_succes (autoincrement)
    - id_perso (INT)
    - resume (TEXT)
    - description (TEXT)
- visuel
    - id_visuel (same as perso)
    - url (VARCHAR)

-->

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BTS SIO - Personnages historiques</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="./boostrap/flag-icons/css/flag-icon.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="icon" type="image/x-icon" href="julienblanc.ico">
    </head>
    <!-- MODALS -->
    <?php
    $result->data_seek(0); // Reset result pointer
    while ($row = $result->fetch_assoc()) {
        $modalId = strtolower($row['nom']);
        $homeTabPaneId = $modalId . '-home-tab-pane';
        $profileTabPaneId = $modalId . '-profile-tab-pane';
        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">';
        echo '<div class="modal-dialog modal-dialog-centered">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h1 class="modal-title fs-5" id="exampleModalLabel">' . $row['prenom'] . ' ' . $row['nom'] . ' <span class="fi fi-' . strtolower($row['drapeau']) . ' fis"></span></h1>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<ul class="nav nav-tabs" id="' . $row['nom'] . '" role="tablist">';
        echo '<li class="nav-item" role="presentation">';
        echo '<button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#' . $homeTabPaneId . '" type="button" role="tab" aria-controls="' . $homeTabPaneId . '" aria-selected="true">Biographie</button>';
        echo '</li>';
        echo '<li class="nav-item" role="presentation">';
        echo '<button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#' . $profileTabPaneId . '" type="button" role="tab" aria-controls="' . $profileTabPaneId . '" aria-selected="false">Contributions</button>';
        echo '</li>';
        echo '</ul>';
        echo '<div class="tab-content" id="myTabContent">';
        echo '<div class="tab-pane fade show active" id="' . $homeTabPaneId . '" role="tabpanel" aria-labelledby="home-tab" tabindex="0">';

        echo '<p><span class="fw-bold">Pays</span>: ' . $row['nom_pays'] . ' <span class="fi fi-' . strtolower($row['drapeau']) . ' fis"></span> (' . $row['capitale'] . ')</p>';
        echo '<p><span class="fw-bold">Date de naissance</span>: ' . $row['date_naissance'] . '</p>';
        echo '<p><span class="fw-bold">Date de décès</span>: ' . $row['date_deces'] . '</p>';
        echo '<hr>';
        echo '<p>' . $row['biographie'] . '</p>';
        echo '</div>';
        echo '<div class="tab-pane fade" id="' . $profileTabPaneId . '" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">';
        echo '<table class="table">';
        echo '<thead><tr><th>Résumé</th><th>Description</th></tr></thead>';
        echo '<tbody>';
        if (isset($succes_data[$row['id_perso']])) {
            foreach ($succes_data[$row['id_perso']] as $succes) {
                echo '<tr>';
                echo '<td class="fw-bold">' . $succes['succes_resume'] . '</td>';
                echo '<td>' . $succes['succes_description'] . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        echo '<div class="collapse" id="collapseExample">';
        echo '<div class="card card-body">Some placeholder content for the collapse component. This panel is hidden by default but revealed when the user activates the relevant trigger.</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    ?>
    <body>
        <header>
            <nav class="navbar navbar-expand-lg bg-secondary-subtle">
            <div class="container-fluid">
                <a class="navbar-brand fs-1 text-secondary fw-bold" href=".">BTS SIO</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <form class="d-flex mx-auto" role="search" method="GET" action="">
                    <input class="form-control me-2" style="width: 230px;" type="search" name="search" placeholder="Rechercher un personnage" aria-label="Search" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-success" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
                </form>
                <a class="navbar-brand ml-100" href="#">
                    <img src="/img/stcharlesstecroix.png" alt="Stcharles Stecroix" width="240" height="100">
                </a>
            </div>
            </nav>
        </header>
        <div id="progress-bar-container" class="container mt-2" style="display: none;">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-4">
            <form method="GET" action="" class="row g-3" id="filter-form">
                <div class="col-md-4">
                    <label for="country" class="form-label"><i class="fa-solid fa-earth-europe"></i> Filtrer par pays</label>
                    <select id="country" name="country" class="form-select">
                        <option value="">Tous les pays</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country); ?>" <?php echo (isset($_GET['country']) && $_GET['country'] == $country) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($country); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="sort" class="form-label"><i class="fa-solid fa-arrow-up-z-a"></i> Trier par</label>
                    <select id="sort" name="sort" class="form-select">
                        <option value="">Par défaut</option>
                        <option value="prenom_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'prenom_asc') ? 'selected' : ''; ?>>Prénom (A-Z)</option>
                        <option value="prenom_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'prenom_desc') ? 'selected' : ''; ?>>Prénom (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrer</button>
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="button" class="btn btn-secondary" onclick="randomCharacter()"><i class="fa-solid fa-dice-five"></i> Personnage aléatoire</button>
                </div>
            </form>
        </div>
        <?php if ($result->num_rows == 0): ?>
            <div class="alert alert-warning text-center m-3" role="alert">
            Aucun personnage trouvé pour votre recherche "<?php echo htmlspecialchars($search); ?>"
            </div>
        <?php else: ?>
        <div class="container mt-4">
            <div class="row">
                <?php
                $i = 0;
                $result->data_seek(0); // Reset result pointer
                while ($row = $result->fetch_assoc()) {
                    if ($i > 0 && $i % 3 == 0) {
                        echo '</div><div class="row">';
                    }
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="card shadow-lg">';
                    echo '<img src="' . $row['url'] . '" class="card-img-top" data-bs-toggle="modal" data-bs-target="#' . strtolower($row['nom']) . '" width="400rem" height="400rem" alt="' . $row['nom'] . '">';
                    echo '<div class="card-body">';
                    echo '<ul class="nav nav-tabs">';
                    echo '<h5 class="card-title">' . $row['prenom'] . ' ' . $row['nom'] . ' <span class="fi fi-' . strtolower($row['drapeau']) . ' fis"></span></h5>';
                    $resume = strlen($row['resume']) > 150 ? substr($row['resume'], 0, 150) . '...' : $row['resume'];
                    echo '<p class="card-text">' . $resume . '</p>';
                    echo '<button class="mx-auto btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#' . strtolower($row['nom']) . '"><i class="fa-solid fa-book-bookmark"></i> En apprendre plus sur ' . $row['nom'] . '</button>';
                    echo '</div></div></div>';
                    $i++;
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </body>
</html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" integrity="sha512-7Pi/otdlbbCR+LnW+F7PwFcSDJOuUJB3OxtEHbg4vSMvzvJjde4Po1v4BR9Gdc9aXNUNFVUY+SK51wWT8WF0Gg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    document.querySelector('form[role="search"]').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressBar = progressBarContainer.querySelector('.progress-bar');
        progressBar.style.width = '0%';
        progressBarContainer.style.display = 'block';

        let width = 0;
        const interval = setInterval(() => {
            if (width >= 100) {
                clearInterval(interval);
                form.submit();
            } else {
                width += 15;
                progressBar.style.width = width + '%';
            }
        }, 150);
    });

    document.getElementById('filter-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressBar = progressBarContainer.querySelector('.progress-bar');
        progressBar.style.width = '0%';
        progressBarContainer.style.display = 'block';

        let width = 0;
        const interval = setInterval(() => {
            if (width >= 100) {
                clearInterval(interval);
                form.submit();
            } else {
                width += 15;
                progressBar.style.width = width + '%';
            }
        }, 150);
    });

    function randomCharacter() {
        const characters = <?php echo json_encode($characters); ?>;
        const randomCharacter = characters[Math.floor(Math.random() * characters.length)];
        const searchQuery = randomCharacter.nom;

        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressBar = progressBarContainer.querySelector('.progress-bar');
        progressBar.style.width = '0%';
        progressBarContainer.style.display = 'block';

        let width = 0;
        const interval = setInterval(() => {
            if (width >= 100) {
                clearInterval(interval);
                window.location.href = '?search=' + encodeURIComponent(searchQuery);
            } else {
                width += 15;
                progressBar.style.width = width + '%';
            }
        }, 150);
    }
</script>
<?php
$conn->close();
?>