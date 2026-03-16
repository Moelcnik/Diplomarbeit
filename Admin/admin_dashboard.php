<?php
// Sitzung starten und Admin-Authentifizierung
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../User_Info/login.php');
    exit();
}

require_once __DIR__ . '/../Datenbank/dbconectio.php';

$user_uid = $_SESSION['user_id'];

// Überprüfe ob Benutzer Admin ist
try {
    $stmt = $conn->prepare("SELECT uid, vorname, nachname, admin FROM user WHERE uid = :uid LIMIT 1");
    $stmt->bindParam(':uid', $user_uid, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // Admin-Check: Benutzer muss Admin sein (admin = 'ja' oder uid = 3)
    if (!$user || ($user['admin'] !== 'ja' && $user['uid'] != 3)) {
        header('Location: ../Website/startseite.php');
        exit();
    }
} catch (PDOException $e) {
    die('Fehler: ' . $e->getMessage());
}

$success_message = '';
$error_message = '';

// Helper function to get primary key name
function getPrimaryKeyName($conn) {
    try {
        $stmt = $conn->query("DESCRIBE fenster_konfigurationen");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('id', $columns)) return 'id';
        if (in_array('fid', $columns)) return 'fid';
        return 'id'; // default
    } catch (Exception $e) {
        return 'id';
    }
}

$pk = getPrimaryKeyName($conn);

// Helper function to get user uid column name
function getUserUidColName($conn) {
    try {
        $stmt = $conn->query("DESCRIBE fenster_konfigurationen");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('user_uid', $columns)) return 'user_uid';
        if (in_array('uid', $columns)) return 'uid';
        return 'user_uid'; // default
    } catch (Exception $e) {
        return 'user_uid';
    }
}

$user_uid_col = getUserUidColName($conn);

// Status aktualisieren wenn gesendet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $id_to_update = intval($_POST['config_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'neu';
        $notes = $_POST['notes'] ?? '';
        
        // Überprüfe ob Spalten existieren
        $stmt_check = $conn->query("DESCRIBE fenster_konfigurationen");
        $columns = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('status', $columns)) {
            $conn->exec("ALTER TABLE fenster_konfigurationen ADD COLUMN status VARCHAR(50) DEFAULT 'neu'");
        }
        if (!in_array('notes', $columns)) {
            $conn->exec("ALTER TABLE fenster_konfigurationen ADD COLUMN notes TEXT");
        }
        
        $sql = "UPDATE fenster_konfigurationen SET status = :status, notes = :notes WHERE $pk = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id_to_update, PDO::PARAM_INT);
        $stmt->execute();
        
        $success_message = 'Status erfolgreich aktualisiert!';
    } catch (Exception $e) {
        $error_message = 'Fehler beim Aktualisieren: ' . $e->getMessage();
    }
}

// Ansicht bestimmen
$view = isset($_GET['id']) ? 'detail' : 'list';
$current_id = isset($_GET['id']) ? intval($_GET['id']) : null;

$konfigurationen = [];
$selected_config = null;

try {
    if ($view === 'list') {
        $sql = "SELECT fc.*, fc.$pk as display_id, u.email, u.vorname, u.nachname
                FROM fenster_konfigurationen fc
                LEFT JOIN user u ON fc.$user_uid_col = u.uid
                ORDER BY fc.erstelldatum DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $konfigurationen = $stmt->fetchAll();
    } else {
        $sql = "SELECT fc.*, fc.$pk as display_id, u.email, u.vorname, u.nachname, u.adresse
                FROM fenster_konfigurationen fc
                LEFT JOIN user u ON fc.$user_uid_col = u.uid
                WHERE fc.$pk = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $current_id, PDO::PARAM_INT);
        $stmt->execute();
        $selected_config = $stmt->fetch();
        
        if (!$selected_config) {
            $view = 'list';
            $error_message = 'Konfiguration nicht gefunden.';
        }
    }
} catch (PDOException $e) {
    $error_message = 'Datenbankfehler: ' . $e->getMessage();
}

// Check for unread messages
$unread_chats_count = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE sender_type = 'user' AND is_read = 0");
    $unread_chats_count = $stmt->fetchColumn();
} catch (Exception $e) {}

// Determine view
if (isset($_GET['view']) && $_GET['view'] === 'chat') {
    $view = 'chat';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Fenster-Konfigurator</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --secondary-color: #3f37c9;
            --bg-color: #f8f9fc;
            --card-bg: #ffffff;
            --text-main: #2b2d42;
            --text-muted: #8d99ae;
            --status-neu: #ff9f1c;
            --status-bearbeitet: #4cc9f0;
            --status-abgeschlossen: #2ec4b6;
            --status-abgelehnt: #e71d36;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Sidebar Style */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            width: 260px;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar h4 {
            font-weight: 700;
            margin-bottom: 2rem;
            padding-left: 1rem;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }

        .sidebar h4 i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.8rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .nav-link i {
            width: 25px;
            margin-right: 10px;
            font-size: 1.1em;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2.5rem;
            transition: all 0.3s;
        }

        .header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-weight: 800;
            font-size: 1.75rem;
            color: var(--text-main);
            margin: 0;
        }

        /* Card System */
        .custom-card {
            background: var(--card-bg);
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Table Style */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f1f3f9;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 1.2rem 1rem;
        }

        .table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #f1f3f9;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.02);
            cursor: pointer;
        }

        /* Status Badges */
        .badge-custom {
            padding: 0.5rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .status-neu { background-color: rgba(255, 159, 28, 0.15); color: #ff9f1c; }
        .status-bearbeitet { background-color: rgba(76, 201, 240, 0.15); color: #4cc9f0; }
        .status-abgeschlossen { background-color: rgba(46, 196, 182, 0.15); color: #2ec4b6; }
        .status-abgelehnt { background-color: rgba(231, 29, 54, 0.15); color: #e71d36; }

        /* Detail View Styles */
        .detail-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .back-btn {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.2s;
            text-decoration: none !important;
        }

        .back-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-box {
            background: #f8faff;
            padding: 1.2rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .info-box label {
            display: block;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .info-box span {
            font-weight: 600;
            color: var(--text-main);
            font-size: 1rem;
        }

        .status-update-form {
            background: #ffffff;
            border: 2px solid #f1f3f9;
            border-radius: 15px;
            padding: 1.5rem;
        }

        .btn-update {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-update:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .skizze-img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        @media (max-width: 992px) {
            .sidebar { width: 80px; padding: 2rem 0.5rem; }
            .sidebar h4 span, .nav-link span { display: none; }
            .sidebar h4 { padding-left: 0; justify-content: center; }
            .nav-link { justify-content: center; }
            .nav-link i { margin-right: 0; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 576px) {
            .main-content { padding: 1.5rem; }
            .page-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4><i class="fas fa-tools"></i> <span>Admin Panel</span></h4>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo ($view === 'list' || $view === 'detail') ? 'active' : ''; ?>" href="admin_dashboard.php">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>
        <a class="nav-link <?php echo $view === 'chat' ? 'active' : ''; ?>" href="admin_dashboard.php?view=chat">
            <i class="fas fa-comments"></i> <span>Chat</span>
            <?php if ($unread_chats_count > 0): ?>
                <span class="badge badge-warning ml-2"><?php echo $unread_chats_count; ?></span>
            <?php endif; ?>
        </a>
        <hr style="border-color: rgba(255,255,255,0.1); width: 100%;">
        <a class="nav-link" href="../Website/startseite.php">
            <i class="fas fa-home"></i> <span>Startseite</span>
        </a>
        <a class="nav-link" href="../User_Info/logout.php">
            <i class="fas fa-sign-out-alt"></i> <span>Abmelden</span>
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <div class="header-area">
        <div>
            <h1 class="page-title">
                <?php echo $view === 'list' ? 'Alle Bestellungen' : 'Bestellung #' . $selected_config['display_id']; ?>
            </h1>
            <p class="text-muted"><?php echo date('l, d. F Y'); ?></p>
        </div>
        <?php if ($view === 'detail'): ?>
            <a href="admin_dashboard.php" class="back-btn" title="Zurück zur Liste">
                <i class="fas fa-arrow-left"></i>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px;">
            <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px;">
            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($view === 'list'): ?>
        <!-- Table View -->
        <div class="custom-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Datum</th>
                            <th>Kunde</th>
                            <th>Fenstertyp</th>
                            <th>Maße</th>
                            <th>Anzahl</th>
                            <th>Status</th>
                            <th class="text-right">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($konfigurationen)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <img src="https://img.icons8.com/illustrations/external-tulpahn-outline-color-tulpahn/100/null/external-order-ecommerce-ui-tulpahn-outline-color-tulpahn.png" alt="No orders" style="opacity: 0.5;"><br>
                                    <p class="mt-3 text-muted">Keine Bestellungen gefunden.</p>
                                </td>
                            </tr>
                    <?php elseif ($view === 'chat'): ?>
        <!-- Admin Chat View -->
        <div class="row" style="height: calc(100vh - 200px);">
            <div class="col-md-4">
                <div class="custom-card h-100" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <div class="p-3 bg-light border-bottom">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-users mr-2"></i> Aktive Chats</h6>
                    </div>
                    <div id="admin-user-list" class="flex-grow-1 overflow-auto">
                        <!-- Users will be loaded here via JS -->
                        <div class="text-center p-5 text-muted">Lade Benutzer...</div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="custom-card h-100" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold" id="chat-with-title">Chat auswählen</h6>
                        <span id="chat-status" class="badge badge-success d-none">Verbunden</span>
                    </div>
                    <div id="admin-chat-messages" class="flex-grow-1 p-4 overflow-auto bg-white" style="display: flex; flex-direction: column; gap: 15px;">
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                            <i class="fas fa-comments fa-4x mb-3" style="opacity: 0.2;"></i>
                            <p>Wählen Sie einen Kunden aus der Liste, um das Gespräch zu beginnen.</p>
                        </div>
                    </div>
                    <div class="p-3 bg-light border-top" id="admin-chat-input-area" style="display: none;">
                        <div class="input-group">
                            <input type="text" id="admin-chat-input" class="form-control" style="border-radius: 20px 0 0 20px; border-right: none;" placeholder="Nachricht an Kunden schreiben...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" id="admin-chat-send" style="border-radius: 0 20px 20px 0; padding: 0 25px;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .chat-user-item {
                padding: 15px;
                border-bottom: 1px solid #f1f3f9;
                cursor: pointer;
                transition: background 0.2s;
            }
            .chat-user-item:hover { background: #f8f9fc; }
            .chat-user-item.active { background: #eef2ff; border-left: 4px solid var(--primary-color); }
            
            .msg { max-width: 70%; padding: 10px 15px; border-radius: 15px; font-size: 0.95rem; line-height: 1.4; }
            .msg-from-admin { align-self: flex-end; background: var(--primary-color); color: white; border-bottom-right-radius: 2px; }
            .msg-from-user { align-self: flex-start; background: #f1f3f9; color: var(--text-main); border-bottom-left-radius: 2px; }
            .unread-dot { width: 10px; height: 10px; background: #ff9f1c; border-radius: 50%; display: inline-block; margin-left: 5px; }
        </style>

        <script>
            let currentChatUserId = null;
            
            function loadUserList() {
                fetch('../Website/chat_handler.php?action=list_users')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('admin-user-list');
                    if (!data.users || data.users.length === 0) {
                        container.innerHTML = '<div class="p-4 text-center text-muted">Keine Chats vorhanden.</div>';
                        return;
                    }
                    container.innerHTML = '';
                    data.users.forEach(u => {
                        const div = document.createElement('div');
                        div.className = `chat-user-item ${currentChatUserId == u.uid ? 'active' : ''}`;
                        div.onclick = () => selectUser(u.uid, `${u.vorname} ${u.nachname}`);
                        div.innerHTML = `
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="font-weight-bold">${u.vorname} ${u.nachname}</div>
                                    <small class="text-muted">${u.email}</small>
                                </div>
                                ${u.unread_count > 0 ? `<span class="badge badge-warning">${u.unread_count}</span>` : ''}
                            </div>
                        `;
                        container.appendChild(div);
                    });
                });
            }

            function selectUser(uid, name) {
                currentChatUserId = uid;
                document.getElementById('chat-with-title').innerText = `Chat mit ${name}`;
                document.getElementById('admin-chat-input-area').style.display = 'block';
                document.getElementById('chat-status').classList.remove('d-none');
                
                // Highlight active user in list
                document.querySelectorAll('.chat-user-item').forEach(el => el.classList.remove('active'));
                loadMessages();
                loadUserList(); // Update unread counts
            }

            function loadMessages() {
                if (!currentChatUserId) return;
                fetch(`../Website/chat_handler.php?action=fetch&target_user_id=${currentChatUserId}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('admin-chat-messages');
                    container.innerHTML = '';
                    data.messages.forEach(m => {
                        const div = document.createElement('div');
                        div.className = `msg ${m.sender_type === 'admin' ? 'msg-from-admin' : 'msg-from-user'}`;
                        div.innerText = m.message;
                        container.appendChild(div);
                    });
                    container.scrollTop = container.scrollHeight;
                });
            }

            function sendAdminMessage() {
                const input = document.getElementById('admin-chat-input');
                const msg = input.value.trim();
                if (!msg || !currentChatUserId) return;

                const fd = new FormData();
                fd.append('action', 'send');
                fd.append('message', msg);
                fd.append('target_user_id', currentChatUserId);

                fetch('../Website/chat_handler.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        loadMessages();
                    }
                });
            }

            document.getElementById('admin-chat-send').addEventListener('click', sendAdminMessage);
            document.getElementById('admin-chat-input').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendAdminMessage();
            });

            // Initial load and periodic updates
            loadUserList();
            setInterval(() => {
                loadUserList();
                if (currentChatUserId) loadMessages();
            }, 5000);
        </script>

    <?php else: ?>
                            <?php foreach ($konfigurationen as $config): ?>
                                <tr onclick="window.location.href='?id=<?php echo $config['display_id']; ?>'">
                                    <td><strong>#<?php echo $config['display_id']; ?></strong></td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?php echo date('d.m.Y', strtotime($config['erstelldatum'])); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($config['erstelldatum'])); ?></small>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars(($config['vorname'] ?? '') . ' ' . ($config['nachname'] ?? 'Gast')); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($config['email'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $types = ['rechteck' => 'Rechteckig', 'rund' => 'Rund', 'sonder' => 'Sonderform'];
                                            echo $types[$config['type']] ?? ucfirst($config['type']); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($config['bemessung']); ?></td>
                                    <td><span class="badge badge-light"><?php echo intval($config['anzahl']); ?>x</span></td>
                                    <td>
                                        <span class="badge-custom status-<?php echo strtolower($config['status'] ?? 'neu'); ?>">
                                            <?php echo htmlspecialchars($config['status'] ?? 'Neu'); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="?id=<?php echo $config['display_id']; ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                                            <i class="fas fa-eye mr-1"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Detail View -->
        <div class="row">
            <div class="col-lg-8">
                <div class="custom-card">
                    <h5 class="mb-4 font-weight-bold">Konfigurations-Details</h5>
                    <div class="info-grid">
                        <div class="info-box">
                            <label>Fenstertyp</label>
                            <span><?php 
                                $types = ['rechteck' => 'Rechteckig', 'rund' => 'Rund', 'sonder' => 'Sonderform'];
                                echo $types[$selected_config['type']] ?? ucfirst($selected_config['type']); 
                            ?></span>
                        </div>
                        <div class="info-box">
                            <label>Material</label>
                            <span><?php echo htmlspecialchars($selected_config['material'] ?? 'Nicht angegeben'); ?></span>
                        </div>
                        <div class="info-box">
                            <label>Profil / Stärke</label>
                            <span><?php echo htmlspecialchars($selected_config['staerke']); ?> mm</span>
                        </div>
                        <div class="info-box">
                            <label>Verglasung</label>
                            <span><?php echo htmlspecialchars($selected_config['verglasung']); ?></span>
                        </div>
                        <div class="info-box">
                            <label>Maße (B x H)</label>
                            <span><?php echo htmlspecialchars($selected_config['bemessung']); ?></span>
                        </div>
                        <div class="info-box">
                            <label>Lichtstufe</label>
                            <span><?php echo htmlspecialchars($selected_config['lichtstufe']); ?></span>
                        </div>
                        <div class="info-box">
                            <label>Anzahl</label>
                            <span><?php echo intval($selected_config['anzahl']); ?> Stück</span>
                        </div>
                        <div class="info-box">
                            <label>Bestelldatum</label>
                            <span><?php echo date('d.m.Y H:i', strtotime($selected_config['erstelldatum'])); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($selected_config['sonderwuensche'])): ?>
                        <div class="mt-4 p-3 bg-light rounded shadow-sm" style="border-right: 4px solid #ddd;">
                            <label class="font-weight-bold text-muted small text-uppercase">Sonderwünsche:</label>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($selected_config['sonderwuensche'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($selected_config['skizze'])): ?>
                        <div class="mt-4">
                            <label class="font-weight-bold text-muted small text-uppercase mb-2">Hochgeladene Skizze / Bild:</label><br>
                            <?php 
                                $skizze_path = $selected_config['skizze'];
                                // Check if it's a relative path and adjust if needed
                                if (!preg_match('~^(?:f|ht)tps?://~i', $skizze_path) && !file_exists($skizze_path)) {
                                    $skizze_path = '../' . ltrim($skizze_path, './');
                                }
                            ?>
                            <a href="<?php echo htmlspecialchars($skizze_path); ?>" target="_blank">
                                <img src="<?php echo htmlspecialchars($skizze_path); ?>" class="skizze-img" alt="Skizze">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Customer Info -->
                <div class="custom-card mb-4" style="border-top: 4px solid var(--primary-light);">
                    <h5 class="mb-4 font-weight-bold">Kundeninformationen</h5>
                    <div class="d-flex align-items-center mb-4">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: 700;">
                            <?php echo strtoupper(substr($selected_config['vorname'] ?? 'G', 0, 1) . substr($selected_config['nachname'] ?? 'A', 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-weight-bold"><?php echo htmlspecialchars(($selected_config['vorname'] ?? '') . ' ' . ($selected_config['nachname'] ?? 'Gast')); ?></div>
                            <small class="text-muted">UID: <?php echo $selected_config['uid'] ?? 'N/A'; ?></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small text-uppercase d-block mb-1">E-Mail</label>
                        <a href="mailto:<?php echo htmlspecialchars($selected_config['email']); ?>" class="text-dark font-weight-500">
                            <i class="fas fa-envelope mr-1 text-primary"></i> <?php echo htmlspecialchars($selected_config['email'] ?? 'Nicht angegeben'); ?>
                        </a>
                    </div>
                    <div>
                        <label class="text-muted small text-uppercase d-block mb-1">Adresse</label>
                        <div class="font-weight-500">
                            <i class="fas fa-map-marker-alt mr-1 text-primary"></i> <?php echo nl2br(htmlspecialchars($selected_config['adresse'] ?? 'Keine Adresse hinterlegt')); ?>
                        </div>
                    </div>
                </div>

                <!-- Status Update -->
                <div class="status-update-form">
                    <h5 class="mb-4 font-weight-bold">Status Verwalten</h5>
                    <form action="admin_dashboard.php?id=<?php echo $selected_config['display_id']; ?>" method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="config_id" value="<?php echo $selected_config['display_id']; ?>">
                        
                        <div class="form-group">
                            <label class="small text-uppercase font-weight-bold text-muted">Aktueller Status</label>
                            <select name="status" class="form-control custom-select" style="height: 50px; border-radius: 10px;">
                                <option value="Neu" <?php echo ($selected_config['status'] ?? 'Neu') === 'Neu' ? 'selected' : ''; ?>>Neu</option>
                                <option value="In Bearbeitung" <?php echo ($selected_config['status'] ?? '') === 'In Bearbeitung' ? 'selected' : ''; ?>>In Bearbeitung</option>
                                <option value="Abgeschlossen" <?php echo ($selected_config['status'] ?? '') === 'Abgeschlossen' ? 'selected' : ''; ?>>Abgeschlossen</option>
                                <option value="Abgelehnt" <?php echo ($selected_config['status'] ?? '') === 'Abgelehnt' ? 'selected' : ''; ?>>Abgelehnt</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="small text-uppercase font-weight-bold text-muted">Interne Notizen</label>
                            <textarea name="notes" class="form-control" rows="4" style="border-radius: 10px;" placeholder="Notizen zum Auftrag..."><?php echo htmlspecialchars($selected_config['notes'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-update btn-block">
                            <i class="fas fa-save mr-2"></i> Speichern
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        // Subtle row fade in animation
        $('tbody tr').each(function(i) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(10px)',
                'transition': 'all 0.3s ease ' + (i * 0.05) + 's'
            });
            
            setTimeout(() => {
                $(this).css({
                    'opacity': '1',
                    'transform': 'translateY(0)'
                });
            }, 100);
        });
    });
</script>

</body>
</html>
