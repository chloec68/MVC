public function addMovie(): void
    {
        $session = new Session();

        if ($session->isAdmin()) {

            $pdo = Connect::toLogIn();

            $requestDirectors = $pdo->query("
                SELECT director.idDirector, person.firstname, person.surname
                FROM director
                INNER JOIN person ON director.idPerson = person.idPerson
                ORDER BY surname
        ");

            $requestThemes = $pdo->query("
                SELECT theme.idTheme, theme.typeName
                FROM theme
                ORDER BY typeName
        ");

            if (isset($_POST['submit'])) { // Vérifie si le formulaire a été soumis

                $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $releaseYear = filter_input(INPUT_POST, "releaseYear", FILTER_VALIDATE_INT);
                $duration = filter_input(INPUT_POST, "duration", FILTER_VALIDATE_INT);
                $note = filter_input(INPUT_POST, "note", FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $synopsis = filter_input(INPUT_POST, "synopsis", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $director = filter_input(INPUT_POST, "idDirector", FILTER_SANITIZE_NUMBER_INT);

                if (isset($_FILES['file'])) {
                    $tmpName = $_FILES['file']['tmp_name'];
                    $name = $_FILES['file']['name'];
                    $size = $_FILES['file']['size'];
                    $error = $_FILES['file']['error'];
                    $type = $_FILES['file']['type'];

                    $tabExtension = explode('.', $name);
                    $extension = strtolower(end($tabExtension));

                    // Tableau des extensions qu'on autorise
                    $allowedExtensions = ['jpg', 'png', 'jpeg', 'webp'];
                    $maxSize = 100000000;

                    if (in_array($extension, $allowedExtensions) && $size <= $maxSize && $error == 0) {

                        $uniqueName = uniqid('', true);
                        $file = $uniqueName . '.' . $extension;

                        move_uploaded_file($tmpName, "public/img/movies/" . $file);

                        // Conversion en webp
                        // Création de mon image en doublon en chaine de caractères
                        $posterSource = imagecreatefromstring(file_get_contents("public/img/movies/" . $file));
                        // Récupération du chemin de l'image
                        $webpPath = "public/img/movies/" . $uniqueName . ".webp";
                        // Conversion en format webp
                        imagewebp($posterSource, $webpPath);
                        // Suppression de l'ancienne image
                        unlink("public/img/movies/" . $file);
                    } else {
                        echo "Wrong extension or file size too large or error !";
                    }
                }

                $poster = isset($webpPath) ? $webpPath : "public/img/movies/default.webp";

                $requestAddMovie = $pdo->prepare("
            INSERT INTO movie (title, releaseYear, duration, note, synopsis, poster, idDirector)
            VALUES (:title, :releaseYear, :duration, :note, :synopsis, :poster, :idDirector)
            ");

                $requestAddMovie->execute([
                    "title" => $title,
                    "releaseYear" => $releaseYear,
                    "duration" => $duration,
                    "note" => $note,
                    "synopsis" => $synopsis,
                    "poster" => $poster,
                    "idDirector" => $director
                ]);

                $movieId = $pdo->lastInsertId();

                foreach ($_POST['theme'] as $theme) {

                    $theme = filter_var($theme, FILTER_VALIDATE_INT);

                    $requestAddMovieTheme = $pdo->prepare("
                INSERT INTO movie_theme (idMovie, idTheme)
                VALUES(:idMovie, :idTheme)
                ");

                    $requestAddMovieTheme->execute([
                        "idMovie" => $movieId,
                        "idTheme" => $theme
                    ]);
                }

                // Redirection vers la page 'index.php?action=listMovies' après le traitement du formulaire
                header("Location:index.php?action=listMovies");
                $_SESSION['message'] = "<div class='alert'>Movie added successfully !</div>";
                exit;
            }

            require "view/movies/addMovie.php";
        } else {
            header("Location:index.php");
            exit;
        }
    }









    
    public function editMovie($id)
    {
        if (!Service::exists("movie", $id)) {
            header("Location:index.php");
            exit;
        } else {
            $session = new Session();
            if ($session->isAdmin()) {
                $pdo = Connect::toLogIn();

                $requestMovie = $pdo->prepare("
        SELECT movie.idMovie, movie.title, movie.releaseYear, movie.duration, movie.note, movie.synopsis, movie.poster, movie.idDirector
        FROM movie
        WHERE movie.idMovie = :id
        ");
                $requestMovie->execute(["id" => $id]);

                $requestDirectors = $pdo->query("
        SELECT director.idDirector, person.firstname, person.surname
        FROM director
        INNER JOIN person ON director.idPerson = person.idPerson
        ORDER BY surname
        ");

                $requestThemes = $pdo->query("
        SELECT theme.idTheme, theme.typeName
        FROM theme
        ORDER BY typeName
        ");

                $requestMovieThemes = $pdo->prepare("
        SELECT theme.idTheme
        FROM movie_theme
        INNER JOIN theme ON movie_theme.idTheme = theme.idTheme
        INNER JOIN movie ON movie_theme.idMovie = movie.idMovie
        WHERE movie.idMovie = :id
        ");
                $requestMovieThemes->execute(["id" => $id]);

                $themes = $requestMovieThemes->fetchAll();
                $themesMovie = [];
                foreach ($themes as $t) {
                    $themesMovie[] = $t["idTheme"];
                }

                if (isset($_POST['submit'])) {

                    $newTitle = filter_input(INPUT_POST, "title", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $newReleaseYear = filter_input(INPUT_POST, "releaseYear", FILTER_VALIDATE_INT);
                    $newDuration = filter_input(INPUT_POST, "duration", FILTER_VALIDATE_INT);
                    $newNote = filter_input(INPUT_POST, "note", FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $newSynopsis = filter_input(INPUT_POST, "synopsis", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $newDirector = filter_input(INPUT_POST, "idDirector", FILTER_SANITIZE_NUMBER_INT);

                    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                        $tmpName = $_FILES['file']['tmp_name'];
                        $name = $_FILES['file']['name'];
                        $size = $_FILES['file']['size'];
                        $error = $_FILES['file']['error'];
                        $type = $_FILES['file']['type'];

                        $tabExtension = explode('.', $name);
                        $extension = strtolower(end($tabExtension));

                        // Tableau des extensions qu'on autorise
                        $allowedExtensions = ['jpg', 'png', 'jpeg', 'webp'];
                        $maxSize = 100000000;

                        if (in_array($extension, $allowedExtensions) && $size <= $maxSize && $error == 0) {

                            $uniqueName = uniqid('', true);
                            $file = $uniqueName . '.' . $extension;

                            $requestPoster = $pdo->prepare("
                        SELECT movie.poster
                        FROM movie
                        WHERE movie.idMovie = :id
                        ");
                            $requestPoster->execute(["id" => $id]);

                            // Permet de récupérer l'image du poster du film et de la supprimer en passant par la variable et le tableau "poster", autrement on pourrait faire une variable pour récupérer directement le tableau
                            $linkPoster = $requestPoster->fetch();

                            if (!$linkPoster !== "./public/img/movies/default.webp") {
                                unlink($linkPoster['poster']);
                            }

                            // On récupère l'image de notre forumulaire via la superglobale file, on prend le chemin et on crée l'image
                            $posterSource = imagecreatefromstring(file_get_contents($tmpName));
                            // Récupération du chemin cible de l'image
                            $webpPath = "public/img/movies/" . $uniqueName . ".webp";
                            // Conversion en format webp (on prend l'image et on la colle dans le dossier de destination)
                            imagewebp($posterSource, $webpPath);

                            $requestNewPoster = $pdo->prepare("
                        UPDATE movie
                        SET poster = :poster
                        WHERE idMovie = :id
                        ");

                            $requestNewPoster->execute([
                                "poster" => $webpPath,
                                "id" => $id
                            ]);
                        } else {
                            echo "Wrong extension or file size too large or error !";
                            exit;
                        }
                    }

                    $requestEditMovie = $pdo->prepare("
                UPDATE movie
                SET title = :title, releaseYear = :releaseYear, duration = :duration, note = :note, synopsis = :synopsis, idDirector = :idDirector
                WHERE idMovie = :id
                ");
                    $requestEditMovie->execute([
                        "title" => $newTitle,
                        "releaseYear" => $newReleaseYear,
                        "duration" => $newDuration,
                        "note" => $newNote,
                        "synopsis" => $newSynopsis,
                        "idDirector" => $newDirector,
                        "id" => $id
                    ]);

                    $theme = filter_input(INPUT_POST, "idTheme", FILTER_VALIDATE_INT);

                    $requestPurgeMovieTheme = $pdo->prepare("
                DELETE FROM movie_theme
                WHERE idMovie = :idMovie
                ");

                    $requestPurgeMovieTheme->execute([
                        "idMovie" => $id
                    ]);

                    foreach ($_POST['theme'] as $theme) {

                        $requestEditMovieTheme = $pdo->prepare("
                INSERT INTO movie_theme (idMovie, idTheme)
                VALUES(:idMovie, :idTheme)
                ");

                        $requestEditMovieTheme->execute([
                            "idMovie" => $id,
                            "idTheme" => $theme
                        ]);
                    }

                    header("Location:index.php?action=editMovie&id=$id");
                    $_SESSION['message'] = "<div class='alert'>This movie has been edited successfully !</div>";
                    exit;
                }

                require "view/movies/editMovie.php";
            } else {
                header("Location:index.php");
                exit;
            }
        }
    }