<?php
// Initialize an empty array to store todos
$todos = file('tasks.txt', FILE_IGNORE_NEW_LINES);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        $todo = htmlspecialchars($_POST['todo']);
        $priority = htmlspecialchars($_POST['priority']);
        $due_date = htmlspecialchars($_POST['due_date']);
        array_push($todos, "$todo|$priority|$due_date|0");
        file_put_contents('tasks.txt', implode(PHP_EOL, $todos));
    } elseif (isset($_POST['delete'])) {
        $index = $_POST['index'];
        unset($todos[$index]);
        $todos = array_values($todos); // Re-index the array after deletion
        file_put_contents('tasks.txt', implode(PHP_EOL, $todos));
    } elseif (isset($_POST['complete'])) {
        $index = $_POST['index'];
        $todo_data = explode('|', $todos[$index]);
        $todo_data[3] = "1"; // Mark as completed
        $todos[$index] = implode('|', $todo_data);
        file_put_contents('tasks.txt', implode(PHP_EOL, $todos));
    } elseif (isset($_POST['clear_completed'])) {
        $todos = array_filter($todos, function($todo) {
            $todo_data = explode('|', $todo);
            return $todo_data[3] == "0"; // Keep only uncompleted tasks
        });
        file_put_contents('tasks.txt', implode(PHP_EOL, $todos));
    } elseif (isset($_POST['edit'])) {
        $index = $_POST['index'];
        $new_todo = htmlspecialchars($_POST['new_todo']);
        $priority = htmlspecialchars($_POST['priority']);
        $due_date = htmlspecialchars($_POST['due_date']);
        $todo_data = explode('|', $todos[$index]);
        $todo_data[0] = $new_todo;
        $todo_data[1] = $priority;
        $todo_data[2] = $due_date;
        $todos[$index] = implode('|', $todo_data);
        file_put_contents('tasks.txt', implode(PHP_EOL, $todos));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Todo List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <button class="toggle-mode">Toggle Dark Mode</button>
        <h1>Todo List</h1>
        <form method="POST" action="">
            <input type="text" name="todo" placeholder="New task" required>
            <label for="priority">Priority:</label>
            <select name="priority" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
            <label for="due_date">Due Date:</label>
            <input type="datetime-local" name="due_date" required>
            <input type="submit" name="add" value="Add Task" class="add-btn">
        </form>
        <ul>
            <?php foreach ($todos as $index => $todo): ?>
                <?php 
                    $todo_data = explode('|', $todo); 
                    $class = $todo_data[1] . ($todo_data[3] == "1" ? " completed" : "");
                ?>
                <li class="<?php echo $class; ?>">
                    <span><?php echo $todo_data[0]; ?> (due: <?php echo $todo_data[2]; ?>)</span>
                    <div class="buttons">
                        <?php if ($todo_data[3] == "0"): ?>
                            <form method="POST" action="" class="edit-form">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <input type="text" name="new_todo" value="<?php echo $todo_data[0]; ?>" class="edit-input">
                                <select name="priority" class="edit-priority">
                                    <option value="low" <?php echo $todo_data[1] == "low" ? "selected" : ""; ?>>Low</option>
                                    <option value="medium" <?php echo $todo_data[1] == "medium" ? "selected" : ""; ?>>Medium</option>
                                    <option value="high" <?php echo $todo_data[1] == "high" ? "selected" : ""; ?>>High</option>
                                </select>
                                <input type="datetime-local" name="due_date" value="<?php echo $todo_data[2]; ?>" class="edit-date">
                                <input type="submit" name="edit" value="Edit" class="edit-btn">
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <input type="submit" name="complete" value="Complete" class="complete-btn">
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <input type="submit" name="delete" value="Delete" class="delete-btn">
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="POST" action="">
            <input type="submit" name="clear_completed" value="Clear Completed Tasks" class="clear-btn">
        </form>
    </div>

    <script>
        document.querySelector('.toggle-mode').addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
        });
    </script>
</body>
</html>
