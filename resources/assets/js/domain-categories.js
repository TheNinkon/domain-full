'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('categoryForm');
  const methodInput = document.getElementById('categoryFormMethod');
  const nameInput = document.getElementById('categoryName');
  const colorInput = document.getElementById('categoryColor');
  const modalLabel = document.getElementById('categoryModalLabel');
  const createUrl = form.dataset.createUrl;

  document.getElementById('newCategoryBtn').addEventListener('click', function () {
    form.action = createUrl;
    methodInput.value = 'POST';
    nameInput.value = '';
    colorInput.value = '#696cff';
    modalLabel.textContent = 'Nueva categoría';
  });

  document.querySelectorAll('.category-edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      form.action = btn.dataset.action;
      methodInput.value = 'PUT';
      nameInput.value = btn.dataset.name;
      colorInput.value = btn.dataset.color || '#696cff';
      modalLabel.textContent = 'Editar categoría';
    });
  });

  document.querySelectorAll('.category-delete-form').forEach(function (deleteForm) {
    deleteForm.addEventListener('submit', function (e) {
      e.preventDefault();

      Swal.fire({
        title: '¿Eliminar esta categoría?',
        text: 'Solo se puede si no tiene dominios asociados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-danger me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.isConfirmed) {
          deleteForm.submit();
        }
      });
    });
  });
});
