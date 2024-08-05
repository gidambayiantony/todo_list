document.addEventListener("DOMContentLoaded", function() {
    const toggleModeBtn = document.querySelector('.toggle-mode');
    toggleModeBtn.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
    });

    const editButtons = document.querySelectorAll('.edit-todo');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const priority = this.dataset.priority;
            const due_date = this.dataset.due_date;
            const description = this.dataset.description;
            const category = this.dataset.category;
            const subtasks = this.dataset.subtasks;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-title').value = title;
            document.getElementById('edit-priority').value = priority;
            document.getElementById('edit-due_date').value = due_date;
            document.getElementById('edit-description').value = description;
            document.getElementById('edit-category').value = category;
            document.getElementById('edit-subtasks').value = subtasks;

            $('#editModal').modal('show');
        });
    });

    // Drag and Drop for reordering
    $('#todo-list').sortable({
        update: function(event, ui) {
            let order = $(this).sortable('toArray', { attribute: 'data-id' });
            console.log(order); // Send this array to the server to save the new order
        }
    });
});
