'use strict';

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.domain-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      Swal.fire({
        title: '¿Eliminar este dominio?',
        text: 'Se moverá a la papelera, podrás recuperarlo después.',
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
          form.submit();
        }
      });
    });
  });
});
