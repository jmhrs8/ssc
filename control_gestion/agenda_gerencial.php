<?php
session_start();
if (!isset($_SESSION['usuario']) && !isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Gerencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa-solid fa-calendar-days text-primary me-2"></i>Agenda Gerencial</h2>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEvento">
            <i class="fa-solid fa-plus me-1"></i>Nueva Cita
        </button>
    </div>

    <div class="card shadow-sm p-3 bg-white rounded">
        <div id="calendar"></div>
    </div>
</div>

<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEvento">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Título de la Cita</label>
                        <input type="text" name="titulo" class="form-control" placeholder="Ej. Reunión Operativa" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Asignar Cita a Usuario</label>
                        <select name="id_usuario_asignado" id="selectUsuarios" class="form-select" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_cita" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hora Inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hora Fin</label>
                            <input type="time" name="hora_fin" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <textarea name="motivo" class="form-control" rows="2" placeholder="Detalles de la reunión..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lugar</label>
                        <input type="text" name="lugar" class="form-control" value="Oficina de Dirección">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const selectUsuarios = document.getElementById('selectUsuarios');

    function cargarUsuarios() {
        fetch('gestion_agenda.php?accion=listar_usuarios')
            .then(res => res.json())
            .then(data => {
                selectUsuarios.innerHTML = '<option value="">-- Selecciona un usuario --</option>';
                if (data.status === 'success') {
                    data.data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id_usuario;
                        option.textContent = user.nombre || `Usuario #${user.id_usuario}`;
                        selectUsuarios.appendChild(option);
                    });
                } else {
                    selectUsuarios.innerHTML = '<option value="">Error al cargar usuarios</option>';
                }
            })
            .catch(() => {
                selectUsuarios.innerHTML = '<option value="">Error de conexión</option>';
            });
    }

    cargarUsuarios();

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('gestion_agenda.php?accion=listar')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const eventos = data.data.map(item => ({
                            id: item.id_cita,
                            title: item.titulo,
                            start: item.start,
                            end: item.end,
                            extendedProps: {
                                motivo: item.motivo,
                                lugar: item.lugar,
                                estatus: item.estatus
                            }
                        }));
                        successCallback(eventos);
                    } else {
                        Swal.fire('Error', data.mensaje, 'error');
                    }
                })
                .catch(err => failureCallback(err));
        },
        eventClick: function(info) {
            const props = info.event.extendedProps;
            Swal.fire({
                title: info.event.title,
                html: `<p><b>Motivo:</b> ${props.motivo}</p>
                       <p><b>Lugar:</b> ${props.lugar}</p>
                       <p><b>Estatus:</b> <span class="badge bg-info">${props.estatus}</span></p>`,
                showCancelButton: true,
                confirmButtonText: 'Eliminar Cita',
                confirmButtonColor: '#d33',
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id_cita', info.event.id);

                    fetch('gestion_agenda.php?accion=eliminar', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Eliminado', data.mensaje, 'success');
                            calendar.refetchEvents();
                        } else {
                            Swal.fire('Error', data.mensaje, 'error');
                        }
                    });
                }
            });
        }
    });

    calendar.render();

    document.getElementById('formEvento').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('gestion_agenda.php?accion=guardar', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Guardado', data.mensaje, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalEvento')).hide();
                this.reset();
                calendar.refetchEvents();
            } else {
                Swal.fire('Error', data.mensaje, 'error');
            }
        });
    });
});
</script>
</body>
</html>
