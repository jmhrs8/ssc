<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$host = 'localhost';
$db   = 'cafeteria_pos';
$user = 'root';
$pass = 'jmhl2474'; // Tu contraseña configurada

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión BD: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // 1. ENVIAR PEDIDO A COCINA / BARRA (INCREMENTAL)
    case 'send_to_kitchen':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['items'])) {
            echo json_encode(['success' => false, 'error' => 'Comanda vacía']);
            exit;
        }

        $table_id    = $data['table_id'] ?? null;
        $waiter_id   = $_SESSION['user']['id'] ?? 1;
        $client_name = !empty($data['client_name']) ? trim($data['client_name']) : null;
        $order_type  = $data['order_type'] ?? ($table_id ? 'mesa' : 'llevar');
        $items       = $data['items'];

        try {
            $pdo->beginTransaction();

            $order_id = null;
            if ($table_id && $order_type === 'mesa') {
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = ? AND status IN ('pendiente', 'en_preparacion', 'listo') ORDER BY id DESC LIMIT 1");
                $stmt->execute([$table_id]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $order_id = $existing['id'];
                }
            }

            if (!$order_id) {
                $stmt = $pdo->prepare("INSERT INTO orders (table_id, waiter_id, client_name, order_type, status, subtotal, iva, total) VALUES (?, ?, ?, ?, 'pendiente', 0, 0, 0)");
                $stmt->execute([$table_id, $waiter_id, $client_name, $order_type]);
                $order_id = $pdo->lastInsertId();

                if ($table_id && $order_type === 'mesa') {
                    $stmtTable = $pdo->prepare("UPDATE tables SET status = 'ocupada' WHERE id = ?");
                    $stmtTable->execute([$table_id]);
                }
            }

            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, notes, item_status) VALUES (?, ?, ?, ?, ?, 'pendiente')");

            foreach ($items as $item) {
                if (isset($item['is_saved']) && $item['is_saved']) continue;
                $price = (float)$item['price'];
                $qty   = (int)$item['quantity'];
                $notes = $item['notes'] ?? '';
                
                $stmtItem->execute([$order_id, $item['id'], $qty, $price, $notes]);
            }

            $stmtTotals = $pdo->prepare("SELECT SUM(quantity * unit_price) AS current_subtotal FROM order_items WHERE order_id = ?");
            $stmtTotals->execute([$order_id]);
            $new_subtotal = (float)$stmtTotals->fetchColumn();
            $new_iva = $new_subtotal * 0.16;
            $new_total = $new_subtotal + $new_iva;

            $stmtUpdateOrder = $pdo->prepare("UPDATE orders SET subtotal = ?, iva = ?, total = ?, status = 'en_preparacion' WHERE id = ?");
            $stmtUpdateOrder->execute([$new_subtotal, $new_iva, $new_total, $order_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Comanda enviada a cocina/barra']);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 2. OBTENER ÓRDENES PARA EL KDS
    case 'get_kds_orders':
        $stmt = $pdo->query("
            SELECT o.id AS order_id, o.table_id, t.table_number, o.client_name, o.order_type, o.status AS order_status, o.created_at,
                   u.name AS waiter_name, u.photo AS waiter_photo
            FROM orders o
            LEFT JOIN tables t ON o.table_id = t.id
            JOIN users u ON o.waiter_id = u.id
            WHERE o.status IN ('pendiente', 'en_preparacion', 'listo')
            ORDER BY o.created_at ASC
        ");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$ord) {
            $stmtItems = $pdo->prepare("
                SELECT oi.id AS item_id, p.name AS product_name, oi.quantity, oi.notes, oi.item_status
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmtItems->execute([$ord['order_id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }

        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    // 3. CAMBIAR ESTADO DE UN ÍTEM ESPECÍFICO (COCINA / BARRA INDEPENDIENTE)
    case 'update_item_status':
        $data = json_decode(file_get_contents('php://input'), true);
        $item_id = $data['item_id'] ?? null;
        $status  = $data['status'] ?? 'listo';

        if (!$item_id) {
            echo json_encode(['success' => false, 'error' => 'ID de ítem no proporcionado']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE order_items SET item_status = ? WHERE id = ?");
        $stmt->execute([$status, $item_id]);

        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        break;

    // 4. OBTENER DETALLE DE MESA
    case 'get_table_bill':
        $table_id = $_GET['table_id'] ?? null;
        if (!$table_id) {
            echo json_encode(['success' => false, 'error' => 'ID de mesa requerido']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT o.*, t.table_number, u.name AS waiter_name
            FROM orders o
            JOIN tables t ON o.table_id = t.id
            JOIN users u ON o.waiter_id = u.id
            WHERE o.table_id = ? AND o.status IN ('pendiente', 'en_preparacion', 'listo')
            ORDER BY o.id DESC LIMIT 1
        ");
        $stmt->execute([$table_id]);
        $order = $stmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'No hay cuenta activa']);
            exit;
        }

        $stmtItems = $pdo->prepare("
            SELECT oi.*, p.name AS product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmtItems->execute([$order['id']]);
        $order['items'] = $stmtItems->fetchAll();

        echo json_encode(['success' => true, 'order' => $order]);
        break;

    // 5. PROCESAR COBRO
    case 'process_payment':
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? null;

        if (!$order_id) {
            echo json_encode(['success' => false, 'error' => 'Orden inválida']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT table_id, total, client_name FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            $stmtCobro = $pdo->prepare("UPDATE orders SET status = 'cobrado' WHERE id = ?");
            $stmtCobro->execute([$order_id]);

            if (!empty($order['table_id'])) {
                $stmtTable = $pdo->prepare("UPDATE tables SET status = 'libre' WHERE id = ?");
                $stmtTable->execute([$order['table_id']]);
            }

            if (!empty($order['client_name'])) {
                $stmtClient = $pdo->prepare("
                    INSERT INTO clients (name, visits, total_spent) VALUES (?, 1, ?)
                    ON DUPLICATE KEY UPDATE visits = visits + 1, total_spent = total_spent + VALUES(total_spent)
                ");
                $stmtClient->execute([$order['client_name'], $order['total']]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Cobro exitoso']);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 6. CREAR MESA
    case 'add_table':
        $data = json_decode(file_get_contents('php://input'), true);
        $table_number = trim($data['table_number'] ?? '');
        if (empty($table_number)) {
            echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO tables (table_number, zone, capacity, status) VALUES (?, 'Salón Principal', 4, 'libre')");
        $stmt->execute([$table_number]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    // 7. GUARDAR / SUBIR MESERO (PNG/JPG FÍSICO)
    case 'save_waiter':
        $id       = $_POST['id'] ?? null;
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $photo_url = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80';

        if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo_file']['tmp_name'];
            $fileName = md5(time() . $_FILES['photo_file']['name']) . '.' . pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
            if(move_uploaded_file($fileTmpPath, './uploads/' . $fileName)) {
                $photo_url = 'uploads/' . $fileName;
            }
        }

        if (empty($name) || empty($username)) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos']);
            exit;
        }

        if ($id) {
            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, photo = ? WHERE id = ?");
                $stmt->execute([$name, $username, $photo_url, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
                $stmt->execute([$name, $username, $id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, role, username, password, photo) VALUES (?, 'waiter', ?, '1234', ?)");
            $stmt->execute([$name, $username, $photo_url]);
        }
        echo json_encode(['success' => true]);
        break;

    // 8. GUARDAR / SUBIR PRODUCTO (PNG/JPG FÍSICO)
    case 'save_product':
        $id       = $_POST['id'] ?? null;
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $price    = (float)($_POST['price'] ?? 0);
        $img_url  = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=300&q=80';

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image_file']['tmp_name'];
            $fileName = md5(time() . $_FILES['image_file']['name']) . '.' . pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            if(move_uploaded_file($fileTmpPath, './uploads/' . $fileName)) {
                $img_url = 'uploads/' . $fileName;
            }
        }

        if (empty($name) || $price <= 0) {
            echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            exit;
        }

        if ($id) {
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $category, $price, $img_url, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ? WHERE id = ?");
                $stmt->execute([$name, $category, $price, $id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (category, name, price, apply_iva, image) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([$category, $name, $price, $img_url]);
        }
        echo json_encode(['success' => true]);
        break;

    // 9. OBTENER DATOS ADMINISTRATIVOS
    case 'get_admin_data':
        $waiters = $pdo->query("SELECT id, name, username, photo FROM users WHERE role != 'admin'")->fetchAll();
        $products = $pdo->query("SELECT id, category, name, price, image FROM products")->fetchAll();
        echo json_encode(['success' => true, 'waiters' => $waiters, 'products' => $products]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
