document.addEventListener('DOMContentLoaded', () => {
    
    // Delegación general de clics para edición in-line y cancelación
    document.addEventListener('click', (event) => {
        // Habilitar edición
        const btnEdit = event.target.closest('[data-action="edit"]');
        if (btnEdit) {
            const id = btnEdit.dataset.id;
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.classList.add('is-editing');
            }
        }

        // Cancelar edición
        const btnCancel = event.target.closest('[data-action="cancel-edit"]');
        if (btnCancel) {
            const id = btnCancel.dataset.id;
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.classList.remove('is-editing');
            }
        }
    });

    // Delegación de submit para confirmar eliminación
    document.addEventListener('submit', (event) => {
        if (event.target.matches('.form-delete')) {
            const confirmed = confirm('¿Está seguro de eliminar este usuario?');
            if (!confirmed) {
                event.preventDefault(); // Detener el envío del formulario si se cancela
            }
        }
    });

});
