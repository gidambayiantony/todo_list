<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$todos = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ?");
$todos->execute([$user_id]);
$todos = $todos->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        $todo = htmlspecialchars($_POST['todo']);
        $priority = htmlspecialchars($_POST['priority']);
        $due_date = htmlspecialchars($_POST['due_date']);
        $description = htmlspecialchars($_POST['description']);
        $category = htmlspecialchars($_POST['category']);
        $subtasks = htmlspecialchars($_POST['subtasks']);

        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, priority, due_date, completed, description, category, subtasks) VALUES (?, ?, ?, ?, 0, ?, ?, ?)");
        $stmt->execute([$user_id, $todo, $priority, $due_date, $description, $category, $subtasks]);
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    } elseif (isset($_POST['complete'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE tasks SET completed = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    } elseif (isset($_POST['clear_completed'])) {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE completed = 1 AND user_id = ?");
        $stmt->execute([$user_id]);
    } elseif (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $new_todo = htmlspecialchars($_POST['new_todo']);
        $priority = htmlspecialchars($_POST['priority']);
        $due_date = htmlspecialchars($_POST['due_date']);
        $description = htmlspecialchars($_POST['description']);
        $category = htmlspecialchars($_POST['category']);
        $subtasks = htmlspecialchars($_POST['subtasks']);

        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, priority = ?, due_date = ?, description = ?, category = ?, subtasks = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_todo, $priority, $due_date, $description, $category, $subtasks, $id, $user_id]);
    }
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';
$filter_priority = $_GET['filter_priority'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';

$filtered_todos = array_filter($todos, function($todo) use ($search, $filter_priority, $filter_category) {
    return stripos($todo['title'], $search) !== false && 
           ($filter_priority ? $todo['priority'] == $filter_priority : true) &&
           ($filter_category ? $todo['category'] == $filter_category : true);
});

if ($sort == 'due_date_asc') {
    usort($filtered_todos, function($a, $b) {
        return strtotime($a['due_date']) <=> strtotime($b['due_date']);
    });
} elseif ($sort == 'due_date_desc') {
    usort($filtered_todos, function($a, $b) {
        return strtotime($b['due_date']) <=> strtotime($a['due_date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Todo List</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <button class="btn btn-dark toggle-mode mb-3">Toggle Dark Mode</button>
        <h1 class="text-center mb-4">Todo List</h1>

        <form method="POST" action="logout.php" class="text-center mb-4">
            <input type="submit" value="Logout" class="btn btn-primary">
        </form>

        <!-- Search Form -->
        <form method="GET" action="" class="form-inline mb-4">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search tasks" value="<?php echo htmlspecialchars($search); ?>">
            <input type="submit" value="Search" class="btn btn-secondary">
        </form>

        <!-- Sort Form -->
        <form method="GET" action="" class="form-inline mb-4">
            <label for="sort" class="mr-2">Sort by:</label>
            <select name="sort" id="sort" class="form-control mr-2">
                <option value="due_date_asc" <?php echo $sort == 'due_date_asc' ? 'selected' : ''; ?>>Due Date (Ascending)</option>
                <option value="due_date_desc" <?php echo $sort == 'due_date_desc' ? 'selected' : ''; ?>>Due Date (Descending)</option>
            </select>
            <input type="submit" value="Sort" class="btn btn-secondary">
        </form>

        <!-- Filter Form -->
        <form method="GET" action="" class="form-inline mb-4">
            <label for="filter_priority" class="mr-2">Filter by Priority:</label>
            <select name="filter_priority" id="filter_priority" class="form-control mr-2">
                <option value="">All</option>
                <option value="low" <?php echo $filter_priority == 'low' ? 'selected' : ''; ?>>Low</option>
                <option value="medium" <?php echo $filter_priority == 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="high" <?php echo $filter_priority == 'high' ? 'selected' : ''; ?>>High</option>
            </select>
            <label for="filter_category" class="mr-2">Filter by Category:</label>
            <select name="filter_category" id="filter_category" class="form-control mr-2">
                <option value="">All</option>
                <option value="work" <?php echo $filter_category == 'work' ? 'selected' : ''; ?>>Work</option>
                <option value="personal" <?php echo $filter_category == 'personal' ? 'selected' : ''; ?>>Personal</option>
            </select>
            <input type="submit" value="Filter" class="btn btn-secondary">
        </form>

        <!-- Add Todo Button -->
        <button class="btn btn-primary mb-4" data-toggle="modal" data-target="#addModal"><i class="fas fa-plus"></i> Add Todo</button>

        <!-- Add Todo Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addModalLabel">Add New Todo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="text" name="todo" class="form-control mb-2" placeholder="Todo" required>
                            <input type="text" name="priority" class="form-control mb-2" placeholder="Priority (low, medium, high)" required>
                            <input type="date" name="due_date" class="form-control mb-2" required>
                            <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
                            <input type="text" name="category" class="form-control mb-2" placeholder="Category (work, personal)" required>
                            <textarea name="subtasks" class="form-control mb-2" placeholder="Subtasks (comma-separated)"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="add" class="btn btn-primary">Add Todo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Todo List -->
        <div class="list-group" id="todo-list">
            <?php foreach ($filtered_todos as $todo): ?>
                <div class="list-group-item" data-id="<?php echo $todo['id']; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($todo['title']); ?></h5>
                            <p class="mb-1">
                                <strong>Priority:</strong> <?php echo htmlspecialchars($todo['priority']); ?><br>
                                <strong>Due Date:</strong> <?php echo htmlspecialchars($todo['due_date']); ?><br>
                                <strong>Description:</strong> <?php echo htmlspecialchars($todo['description']); ?><br>
                                <strong>Category:</strong> <?php echo htmlspecialchars($todo['category']); ?><br>
                                <strong>Subtasks:</strong> <?php echo htmlspecialchars($todo['subtasks']); ?>
                            </p>
                        </div>
                        <div>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                                <button type="submit" name="complete" class="btn btn-success"><i class="fas fa-check"></i></button>
                            </form>
                            <button class="btn btn-info" data-toggle="modal" data-target="#editModal-<?php echo $todo['id']; ?>"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Todo Modal -->
                <div class="modal fade" id="editModal-<?php echo $todo['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel-<?php echo $todo['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel-<?php echo $todo['id']; ?>">Edit Todo</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?php echo $todo['id']; ?>">
                                    <input type="text" name="new_todo" class="form-control mb-2" value="<?php echo htmlspecialchars($todo['title']); ?>" required>
                                    <input type="text" name="priority" class="form-control mb-2" value="<?php echo htmlspecialchars($todo['priority']); ?>" required>
                                    <input type="date" name="due_date" class="form-control mb-2" value="<?php echo htmlspecialchars($todo['due_date']); ?>" required>
                                    <textarea name="description" class="form-control mb-2"><?php echo htmlspecialchars($todo['description']); ?></textarea>
                                    <input type="text" name="category" class="form-control mb-2" value="<?php echo htmlspecialchars($todo['category']); ?>" required>
                                    <textarea name="subtasks" class="form-control mb-2"><?php echo htmlspecialchars($todo['subtasks']); ?></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" action="" class="mt-4">
            <input type="submit" name="clear_completed" value="Clear Completed Tasks" class="btn btn-danger">
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui/1.12.1/jquery-ui.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
