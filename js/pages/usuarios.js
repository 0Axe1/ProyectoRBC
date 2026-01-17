document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('user-modal');
    if (!modal) return;

    const addUserBtn = document.getElementById('add-user-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const editBtns = document.querySelectorAll('.edit-btn');
    const form = document.getElementById('user-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const submitBtn = document.getElementById('submit-btn');
    const passwordHelp = document.getElementById('password-help');
   
    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');

    if(addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            form.reset();
            modalTitle.textContent = 'Agregar Nuevo Usuario';
            formAction.value = 'create';
            submitBtn.textContent = 'Crear Usuario';
            passwordHelp.classList.add('hidden');
            document.getElementById('contrasena').required = true;
            openModal();
        });
    }

    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            form.reset();
            modalTitle.textContent = 'Editar Usuario';
            formAction.value = 'update';
            submitBtn.textContent = 'Actualizar Usuario';
           
            document.getElementById('id_usuario').value = btn.dataset.id;
            document.getElementById('nombre_usuario').value = btn.dataset.nombre;
            document.getElementById('id_rol').value = btn.dataset.idRol;
           
            passwordHelp.classList.remove('hidden');
            document.getElementById('contrasena').required = false;
            openModal();
        });
    });

    if(cancelBtn) cancelBtn.addEventListener('click', closeModal);
});
