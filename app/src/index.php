<?php
// app/src/index.php
require_once 'TaskService.php';

$host     = getenv('DB_HOST')     ?: 'db';
$dbname   = getenv('DB_DATABASE') ?: 'todo_db';
$username = getenv('DB_USERNAME') ?: 'todo_user';
$password = getenv('DB_PASSWORD') ?: 'todo_password';

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
$errorMessage = '';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // テーブル作成＆拡張
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        is_completed TINYINT(1) DEFAULT 0,
        due_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    try { $pdo->exec("ALTER TABLE tasks ADD COLUMN is_completed TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE tasks ADD COLUMN due_date DATE NULL"); } catch (Exception $e) {}

    $taskService = new TaskService();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 【1. 新規タスク追加】
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $title = $_POST['title'];
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            
            if ($taskService->validateTitle($title)) {
                $stmt = $pdo->prepare('INSERT INTO tasks (title, due_date) VALUES (:title, :due_date)');
                $stmt->execute(['title' => trim($title), 'due_date' => $dueDate]);
                header('Location: index.php');
                exit;
            } else {
                $errorMessage = '※タスク名は1文字以上、50文字以内で入力してください。';
            }
        }

        // 【2. 既存タスクの期限更新★追加機能】
        if (isset($_POST['action']) && $_POST['action'] === 'update_date') {
            $taskId = $_POST['task_id'];
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            $stmt = $pdo->prepare('UPDATE tasks SET due_date = :due_date WHERE id = :id');
            $stmt->execute(['due_date' => $dueDate, 'id' => $taskId]);
            header('Location: index.php');
            exit;
        }
    }

    $stmt = $pdo->query('SELECT * FROM tasks');
    $rawTasks = $stmt->fetchAll();
    $tasks = $taskService->filterAndSortTasks($rawTasks);

} catch (PDOException $e) {
    die("【接続テスト】試したホスト名: [ {$host} ] <br>エラー詳細: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>TODOアプリ（期限変更対応）</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { display: flex; gap: 8px; margin-bottom: 10px; }
        .form-group input[type="text"] { flex: 2; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .form-group input[type="date"] { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group button { padding: 10px 20px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .error-message { color: #ff4d4d; font-size: 14px; margin-bottom: 15px; }
        .task-list { list-style: none; padding: 0; }
        .task-item { padding: 12px 15px; margin-bottom: 10px; background: #fff; border-left: 6px solid #007bff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-size: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        
        .task-item.normal { border-left-color: #007bff; }
        .task-item.warning { border-left-color: #ff9800; background: #fff8e1; }
        .task-item.overdue { border-left-color: #f44336; background: #ffebee; }

        .update-form { display: flex; align-items: center; gap: 5px; }
        .update-form input[type="date"] { padding: 4px 6px; font-size: 12px; border: 1px solid #ccc; border-radius: 4px; }
        .update-form button { padding: 4px 8px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .update-form button:hover { background: #5a6268; }

        .badge { padding: 3px 8px; border-radius: 12px; color: white; font-size: 11px; margin-left: 5px; }
        .badge-overdue { background: #f44336; }
        .badge-warning { background: #ff9800; }
    </style>
</head>
<body>
<div class="container">
    <h1>📋 シンプルTODOリスト</h1>
    
    <!-- 新規追加フォーム -->
    <form action="" method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <input type="text" name="title" placeholder="新しいタスクを入力..." required autocomplete="off">
            <input type="date" name="due_date">
            <button type="submit">追加</button>
        </div>
        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
    </form>

    <!-- タスク一覧表示 -->
    <ul class="task-list">
        <?php if (empty($tasks)): ?>
            <li style="text-align: center; color: #999; padding: 20px;">タスクはまだありません。</li>
        <?php else: ?>
            <?php 
            $today = date('Y-m-d');
            $threeDaysLater = date('Y-m-d', strtotime('+3 days'));
            ?>
            <?php foreach ($tasks as $task): ?>
                <?php
                $statusClass = 'normal';
                $statusLabel = '';
                if (!empty($task['due_date'])) {
                    if ($task['due_date'] < $today) {
                        $statusClass = 'overdue';
                        $statusLabel = '<span class="badge badge-overdue">期限切れ</span>';
                    } elseif ($task['due_date'] == $today) {
                        $statusClass = 'overdue';
                        $statusLabel = '<span class="badge badge-overdue">今日まで</span>';
                    } elseif ($task['due_date'] <= $threeDaysLater) {
                        $statusClass = 'warning';
                        $statusLabel = '<span class="badge badge-warning">まもなく期限</span>';
                    }
                }
                ?>
                <li class="task-item <?= $statusClass ?>">
                    <div>
                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                        <?= $statusLabel ?>
                    </div>
                    
                    <!-- 既存タスクの期限変更用フォーム -->
                    <form action="" method="POST" class="update-form">
                        <input type="hidden" name="action" value="update_date">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        📅 <input type="date" name="due_date" value="<?= htmlspecialchars($task['due_date'] ?? '') ?>">
                        <button type="submit">保存</button>
                    </form>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
