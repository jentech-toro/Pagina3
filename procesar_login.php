<?php
require 'init.php';
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (!validate_csrf_token($csrf)) {
        header('Location: index.php?error=csrf');
        exit;
    }

    if ($email === '' || $password === '') {
        header('Location: index.php?error=1');
        exit;
    }

    // Limitador de intentos persistido en la base de datos (fallback a sesión si la tabla no existe)
    $maxAttempts = 5;
    $decay = 900; // 15 minutos
    $now = time();
    $useDb = true;
    try {
        // Asegurar que la tabla exista
        $conn->exec(
            "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                attempts INT NOT NULL DEFAULT 0,
                first_attempt DATETIME NOT NULL,
                last_attempt DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

        $qa = $conn->prepare('SELECT attempts, first_attempt FROM login_attempts WHERE email = :email');
        $qa->bindParam(':email', $email);
        $qa->execute();
        $row = $qa->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $first_ts = strtotime($row['first_attempt']);
            if ($row['attempts'] >= $maxAttempts && ($now - $first_ts) < $decay) {
                header('Location: index.php?error=locked');
                exit;
            }
            if (($now - $first_ts) >= $decay) {
                // resetear contador
                $upd = $conn->prepare('UPDATE login_attempts SET attempts = 0, first_attempt = :first, last_attempt = :last WHERE email = :email');
                $first = date('Y-m-d H:i:s', $now);
                $last = $first;
                $upd->bindParam(':first', $first);
                $upd->bindParam(':last', $last);
                $upd->bindParam(':email', $email);
                $upd->execute();
            }
        }
    } catch (Exception $e) {
        // Si falla la BD, usamos método en sesión
        $useDb = false;
        if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
        $attempts = &$_SESSION['login_attempts'];
        if (isset($attempts[$email])) {
            if ($attempts[$email]['count'] >= $maxAttempts && ($now - $attempts[$email]['first']) < $decay) {
                header('Location: index.php?error=locked');
                exit;
            }
            if (($now - $attempts[$email]['first']) >= $decay) {
                unset($attempts[$email]);
            }
        }
    }

    $stmt = $conn->prepare('SELECT id, nombre, email, password, rol FROM usuarios WHERE email = :email');
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $returnTo = isset($_POST['return_to']) ? basename($_POST['return_to']) : '';
    if ($returnTo === 'productos.php' || $returnTo === 'index.php') {
        $safeReturn = $returnTo;
    } else {
        $safeReturn = 'panel.php';
    }

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['rol'];

        // Si había un carrito en sesión antes de loguear, migrarlo a la BD
        try {
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $userId = intval($_SESSION['user_id']);
            if (!empty($_SESSION['cart'])) {
                $ins = $conn->prepare("INSERT INTO carts (user_id, item_id, name, price, image, quantity) VALUES (:user, :item, :name, :price, :image, :qty)
                    ON DUPLICATE KEY UPDATE quantity = quantity + :addQty, name = :name2, price = :price2, image = :image2");
                foreach ($_SESSION['cart'] as $localId => $it) {
                    $ins->bindParam(':user', $userId);
                    $ins->bindParam(':item', $localId);
                    $ins->bindParam(':name', $it['name']);
                    $ins->bindParam(':price', $it['price']);
                    $ins->bindParam(':image', $it['image']);
                    $ins->bindParam(':qty', $it['quantity']);
                    $ins->bindParam(':addQty', $it['quantity']);
                    $ins->bindParam(':name2', $it['name']);
                    $ins->bindParam(':price2', $it['price']);
                    $ins->bindParam(':image2', $it['image']);
                    $ins->execute();
                }
            }

            // Cargar carrito persistido desde BD y reemplazar la sesión con contenido actualizado
            $qa = $conn->prepare('SELECT item_id, name, price, image, quantity FROM carts WHERE user_id = :user');
            $qa->bindParam(':user', $userId);
            $qa->execute();
            $dbCart = [];
            while ($row = $qa->fetch(PDO::FETCH_ASSOC)) {
                $dbCart[$row['item_id']] = [
                    'id' => $row['item_id'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'image' => $row['image'],
                    'quantity' => intval($row['quantity'])
                ];
            }
            $_SESSION['cart'] = $dbCart;
        } catch (Exception $e) {
            // Si algo falla, dejamos el carrito de sesión tal como está
        }

        // Limpiar contador de intentos al hacer login correcto
        try {
            if ($useDb) {
                $del = $conn->prepare('DELETE FROM login_attempts WHERE email = :email');
                $del->bindParam(':email', $email);
                $del->execute();
            } else {
                if (isset($attempts[$email])) unset($attempts[$email]);
            }
        } catch (Exception $e) {
            // ignorar errores de limpieza
        }

        header('Location: ' . $safeReturn);
        exit;
    }
    // Registrar intento fallido (persistir en BD si posible)
    try {
        if ($useDb) {
            $qa = $conn->prepare('SELECT attempts, first_attempt FROM login_attempts WHERE email = :email');
            $qa->bindParam(':email', $email);
            $qa->execute();
            $row = $qa->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $attemptsCount = $row['attempts'] + 1;
                $upd = $conn->prepare('UPDATE login_attempts SET attempts = :attempts, last_attempt = :last WHERE email = :email');
                $last = date('Y-m-d H:i:s', $now);
                $upd->bindParam(':attempts', $attemptsCount);
                $upd->bindParam(':last', $last);
                $upd->bindParam(':email', $email);
                $upd->execute();
            } else {
                $ins = $conn->prepare('INSERT INTO login_attempts (email, attempts, first_attempt, last_attempt) VALUES (:email, 1, :first, :last)');
                $first = date('Y-m-d H:i:s', $now);
                $last = $first;
                $ins->bindParam(':email', $email);
                $ins->bindParam(':first', $first);
                $ins->bindParam(':last', $last);
                $ins->execute();
            }
        } else {
            if (!isset($attempts[$email])) {
                $attempts[$email] = ['count' => 1, 'first' => $now];
            } else {
                $attempts[$email]['count']++;
            }
        }
    } catch (Exception $e) {
        // Fallback: session
        if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
        $attempts = &$_SESSION['login_attempts'];
        if (!isset($attempts[$email])) {
            $attempts[$email] = ['count' => 1, 'first' => $now];
        } else {
            $attempts[$email]['count']++;
        }
    }

    header('Location: index.php?error=1');
    exit;
}
?>