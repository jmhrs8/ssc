<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Validar Sesión
if (!isset($_SESSION['usuario']) && !isset($_SESSION['id_usuario'])) {
    if (isset($_REQUEST['accion'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida'], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

$id_usuario_actual = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 1;

$host = '127.0.0.1';
$db   = 'ssc_control_gestion';
$user = 'root';
$pass = 'jmhl2474';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    if (isset($_REQUEST['accion'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'mensaje' => 'Error de conexión: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        die('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}

// ==========================================
// 1. MANEJO DE PETICIONES AJAX / BACKEND
// ==========================================
if (isset($_REQUEST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_REQUEST['accion'];

    switch ($accion) {
        case 'listar':
            try {
                $sql = "SELECT a.id_cita, a.titulo, a.id_usuario_asignado,
                               a.fecha_cita, a.hora_inicio, a.hora_fin, a.motivo, a.lugar, a.estatus,
                               a.creado_por, a.creado_en,
                               CONCAT(a.fecha_cita, 'T', a.hora_inicio) AS start,
                               CONCAT(a.fecha_cita, 'T', a.hora_fin) AS end
                        FROM ssc_agenda_gerencial a
                        ORDER BY a.fecha_cita DESC, a.hora_inicio ASC";

                $stmt = $pdo->query($sql);
                $eventos = $stmt->fetchAll();

                // Catálogo de usuarios para mapear nombres en los eventos
                $stmtUsers = $pdo->query("SELECT id_usuario, nombre_completo FROM usuarios");
                $mapUsuarios = [];
                while ($u = $stmtUsers->fetch()) {
                    $mapUsuarios[$u['id_usuario']] = $u['nombre_completo'];
                }

                // Concatenar los nombres de los asignados
                foreach ($eventos as &$evt) {
                    $ids = array_filter(explode(',', $evt['id_usuario_asignado']));
                    $nombres = [];
                    foreach ($ids as $id) {
                        if (isset($mapUsuarios[$id])) {
                            $nombres[] = $mapUsuarios[$id];
                        }
                    }
                    $evt['asignado_a'] = !empty($nombres) ? implode(', ', $nombres) : 'Sin asignar';
                }

                echo json_encode(['status' => 'success', 'data' => $eventos], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Error al consultar la agenda: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'listar_usuarios':
            try {
                $stmt = $pdo->query("SELECT id_usuario, nombre_completo, username, correo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo ASC");
                $usuarios = $stmt->fetchAll();
                echo json_encode(['status' => 'success', 'data' => $usuarios], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Error al obtener usuarios: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'guardar':
            $id_cita              = $_POST['id_cita'] ?? 0;
            $titulo               = $_POST['titulo'] ?? '';
            $usuarios_seleccionados = $_POST['id_usuario_asignado'] ?? []; // Array desde los checkboxes
            $fecha_cita           = $_POST['fecha_cita'] ?? '';
            $hora_inicio          = $_POST['hora_inicio'] ?? '';
            $hora_fin             = $_POST['hora_fin'] ?? '';
            $motivo               = $_POST['motivo'] ?? '';
            $lugar                = $_POST['lugar'] ?? 'Oficina de Dirección';
            
            $estatus_raw = trim($_POST['estatus'] ?? 'PENDIENTE');
            $estatus     = substr($estatus_raw, 0, 10);

            if (empty($titulo) || empty($usuarios_seleccionados) || empty($fecha_cita) || empty($hora_inicio) || empty($hora_fin) || empty($motivo)) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Debe ingresar el título, seleccionar al menos a un participante y llenar la información obligatoria.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Convertir array de IDs a cadena separada por comas (ej. "6,15,18")
            $id_usuario_asignado_str = is_array($usuarios_seleccionados) ? implode(',', $usuarios_seleccionados) : $usuarios_seleccionados;

            try {
                $pdo->exec("SET SESSION sql_mode=''");
                $es_edicion = false;

                if ($id_cita > 0) {
                    $es_edicion = true;
                    $sql = "UPDATE ssc_agenda_gerencial 
                            SET titulo = ?, id_usuario_asignado = ?, fecha_cita = ?, hora_inicio = ?, hora_fin = ?, motivo = ?, lugar = ?, estatus = ?
                            WHERE id_cita = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$titulo, $id_usuario_asignado_str, $fecha_cita, $hora_inicio, $hora_fin, $motivo, $lugar, $estatus, $id_cita]);
                    $id_cita_final = $id_cita;
                } else {
                    $sql = "INSERT INTO ssc_agenda_gerencial
                            (titulo, id_usuario_asignado, fecha_cita, hora_inicio, hora_fin, motivo, lugar, estatus, creado_por)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$titulo, $id_usuario_asignado_str, $fecha_cita, $hora_inicio, $hora_fin, $motivo, $lugar, $estatus, $id_usuario_actual]);
                    $id_cita_final = $pdo->lastInsertId();
                }

                // =======================================================
                // ENVÍO DE CORREO A PARTICIPANTES MARCADOS
                // =======================================================
                $idsArray = is_array($usuarios_seleccionados) ? $usuarios_seleccionados : explode(',', $usuarios_seleccionados);
                $inQuery  = implode(',', array_fill(0, count($idsArray), '?'));

                $stmtUsers = $pdo->prepare("SELECT nombre_completo, correo FROM usuarios WHERE id_usuario IN ($inQuery) AND correo IS NOT NULL AND correo != ''");
                $stmtUsers->execute($idsArray);
                $destinatarios = $stmtUsers->fetchAll();

                $correos_enviados = 0;
                $lista_notificados = [];

                if (!empty($destinatarios)) {
                    $cita_formateada = str_pad($id_cita_final, 5, "0", STR_PAD_LEFT);
                    $accion_texto    = $es_edicion ? "Actualización de Cita" : "Nueva Cita Agendada";
                    $asunto_texto    = "=[SSC GESTIÓN]= " . $accion_texto . " - Cita #" . $cita_formateada;
                    $asunto_mail     = "=?UTF-8?B?" . base64_encode($asunto_texto) . "?=";

                    $headers  = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                    $headers .= "From: Sistema Control de Gestión <sraaseguramiento@gmail.com>\r\n";

                    foreach ($destinatarios as $userDest) {
                        $to = trim($userDest['correo']);
                        
                        $mensaje = "
                        <html>
                        <head><title>Agenda Gerencial</title></head>
                        <body style='font-family: sans-serif; color: #333;'>
                            <h2 style='color: #861532;'>SSC - Control de Gestión</h2>
                            <p>Estimado(a) <strong>" . htmlspecialchars($userDest['nombre_completo']) . "</strong>,</p>
                            <p>Se notifica que se le ha asignado la siguiente cita de trabajo:</p>
                            <ul>
                                <li><strong>ID Cita:</strong> #{$cita_formateada}</li>
                                <li><strong>Asunto / Título:</strong> " . htmlspecialchars($titulo) . "</li>
                                <li><strong>Fecha:</strong> " . htmlspecialchars($fecha_cita) . "</li>
                                <li><strong>Horario:</strong> " . htmlspecialchars($hora_inicio) . " a " . htmlspecialchars($hora_fin) . " hrs</li>
                                <li><strong>Lugar:</strong> " . htmlspecialchars($lugar) . "</li>
                                <li><strong>Estatus:</strong> " . htmlspecialchars($estatus_raw) . "</li>
                                <li><strong>Motivo:</strong> " . nl2br(htmlspecialchars($motivo)) . "</li>
                            </ul>
                            <hr>
                            <small>Aviso automático enviado desde el Sistema de Control de Gestión.</small>
                        </body>
                        </html>";

                        if (mail($to, $asunto_mail, $mensaje, $headers)) {
                            $correos_enviados++;
                            $lista_notificados[] = $to;
                        }
                    }

                    $detalle_correo = "y notificaciones enviadas a ($correos_enviados): " . implode(', ', $lista_notificados);
                } else {
                    $detalle_correo = "(Los usuarios seleccionados no tienen correo registrado)";
                }

                $msg = "Cita guardada correctamente " . $detalle_correo;
                echo json_encode(['status' => 'success', 'mensaje' => $msg], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Error al guardar la cita: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'eliminar':
            $id_cita = $_POST['id_cita'] ?? 0;
            try {
                $stmt = $pdo->prepare("DELETE FROM ssc_agenda_gerencial WHERE id_cita = ?");
                $stmt->execute([$id_cita]);

                echo json_encode(['status' => 'success', 'mensaje' => 'Cita eliminada correctamente'], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Error al eliminar la cita: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'mensaje' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
            break;
    }
    exit;
}

// ==========================================
// 2. VISTA HTML (Interfaz de Usuario)
// ==========================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Gerencial - SSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <style>
        .fc-header-toolbar { font-size: 0.9rem; }
        .fc-button-primary { background-color: #861532 !important; border-color: #861532 !important; }
        .fc-button-primary:hover { background-color: #631024 !important; border-color: #631024 !important; }
        .bg-ssc { background-color: #861532; color: #fff; }
        .usuario-item:hover { background-color: #f1f3f5; border-radius: 4px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa-solid fa-calendar-days text-danger me-2"></i>Agenda Gerencial</h2>
        <button class="btn btn-danger btn-sm" style="background-color: #861532;" data-bs-toggle="modal" data-bs-target="#modalEvento" onclick="limpiarFormulario()">
            <i class="fa-solid fa-plus me-1"></i>Nueva Cita
        </button>
    </div>

    <div class="card shadow-sm p-3 bg-white rounded border-0">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal Registrar / Editar Cita -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-ssc">
                <h5 class="modal-title" id="modalTitulo">Registrar Nueva Cita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEvento">
                <input type="hidden" name="id_cita" id="id_cita" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Título / Asunto de la Cita</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Ej. Reunión de seguimiento de folios" required>
                    </div>

                    <!-- SECCIÓN DE SELECCIÓN CON CHECKBOXES CORREGIDA -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Participantes Asignados</label>
                            <div>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" onclick="marcarTodos(true)">Marcar todos</button>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" onclick="marcarTodos(false)">Desmarcar</button>
                            </div>
                        </div>

                        <input type="text" id="buscarUsuario" class="form-control form-control-sm mb-2" placeholder="🔍 Buscar participante por nombre...">

                        <div class="border rounded p-2 bg-white" id="contenedorUsuarios" style="max-height: 220px; overflow-y: auto; position: relative;">
                            <div class="text-muted small">Cargando lista de usuarios...</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Fecha</label>
                            <input type="date" name="fecha_cita" id="fecha_cita" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Inicio</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fin</label>
                            <input type="time" name="hora_fin" id="hora_fin" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Estatus</label>
                        <select name="estatus" id="estatus" class="form-select">
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="PROCESO">EN PROCESO</option>
                            <option value="CONCLUIDO">CONCLUIDO</option>
                            <option value="CANCELADO">CANCELADO</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo</label>
                        <textarea name="motivo" id="motivo" class="form-control" rows="3" placeholder="Detalles u objetivo de la cita..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Lugar</label>
                        <input type="text" name="lugar" id="lugar" class="form-control" value="Oficina de Dirección">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" style="background-color: #861532;">Guardar y Notificar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let calendar;

function limpiarFormulario() {
    document.getElementById('id_cita').value = '0';
    document.getElementById('formEvento').reset();
    document.getElementById('lugar').value = 'Oficina de Dirección';
    document.getElementById('modalTitulo').textContent = 'Registrar Nueva Cita';
    marcarTodos(false);
}

function marcarTodos(estado) {
    const checkboxes = document.querySelectorAll('.chk-usuario');
    checkboxes.forEach(chk => chk.checked = estado);
}

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const contenedorUsuarios = document.getElementById('contenedorUsuarios');
    const buscarUsuarioInput = document.getElementById('buscarUsuario');

    // Cargar catálogo de usuarios como Checkboxes con formato corregido
    function cargarUsuariosCheckboxes() {
        fetch('gestion_agenda.php?accion=listar_usuarios')
            .then(res => res.json())
            .then(data => {
                contenedorUsuarios.innerHTML = '';
                if (data.status === 'success') {
                    data.data.forEach(user => {
                        const div = document.createElement('div');
                        // ps-4 da el margen necesario a la izquierda para que no se recorte el checkbox
                        div.className = 'form-check usuario-item ps-4 pe-2 py-1 mb-1';
                        
                        const correoTxt = user.correo ? `<span class="text-muted font-monospace small">(${user.correo})</span>` : '<span class="text-danger small">(Sin Correo)</span>';
                        
                        div.innerHTML = `
                            <input class="form-check-input chk-usuario" type="checkbox" name="id_usuario_asignado[]" value="${user.id_usuario}" id="usr_chk_${user.id_usuario}" style="cursor: pointer;">
                            <label class="form-check-label w-100" style="cursor: pointer; word-break: break-word;" for="usr_chk_${user.id_usuario}">
                                <strong>${user.nombre_completo}</strong> ${correoTxt}
                            </label>
                        `;
                        contenedorUsuarios.appendChild(div);
                    });
                } else {
                    contenedorUsuarios.innerHTML = '<div class="text-danger small">Error al cargar la lista de usuarios.</div>';
                }
            })
            .catch(() => {
                contenedorUsuarios.innerHTML = '<div class="text-danger small">Error de conexión al obtener usuarios.</div>';
            });
    }

    cargarUsuariosCheckboxes();

    // Filtro de búsqueda en tiempo real
    buscarUsuarioInput.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const items = contenedorUsuarios.querySelectorAll('.usuario-item');
        
        items.forEach(item => {
            const texto = item.textContent.toLowerCase();
            item.style.display = texto.includes(filtro) ? 'block' : 'none';
        });
    });

    calendar = new FullCalendar.Calendar(calendarEl, {
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
                                id_usuario_asignado: item.id_usuario_asignado,
                                asignado_a: item.asignado_a,
                                fecha_cita: item.fecha_cita,
                                hora_inicio: item.hora_inicio,
                                hora_fin: item.hora_fin,
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
                html: `<div class="text-start">
                        <p><b>Participantes:</b> ${props.asignado_a || 'N/A'}</p>
                        <p><b>Fecha:</b> ${props.fecha_cita}</p>
                        <p><b>Horario:</b> ${props.hora_inicio} a ${props.hora_fin} hrs</p>
                        <p><b>Lugar:</b> ${props.lugar}</p>
                        <p><b>Estatus:</b> <span class="badge bg-secondary">${props.estatus}</span></p>
                        <p><b>Motivo:</b> ${props.motivo}</p>
                       </div>`,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Editar Cita',
                denyButtonText: 'Eliminar Cita',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#0d6efd',
                denyButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('id_cita').value = info.event.id;
                    document.getElementById('titulo').value = info.event.title;
                    document.getElementById('fecha_cita').value = props.fecha_cita;
                    document.getElementById('hora_inicio').value = props.hora_inicio;
                    document.getElementById('hora_fin').value = props.hora_fin;
                    document.getElementById('estatus').value = props.estatus;
                    document.getElementById('motivo').value = props.motivo;
                    document.getElementById('lugar').value = props.lugar;
                    
                    // Marcar en la lista los checkboxes correspondientes
                    marcarTodos(false);
                    const selectedIds = props.id_usuario_asignado.split(',');
                    selectedIds.forEach(id => {
                        const chk = document.getElementById(`usr_chk_${id.trim()}`);
                        if (chk) chk.checked = true;
                    });
                    
                    document.getElementById('modalTitulo').textContent = 'Editar Cita';
                    new bootstrap.Modal(document.getElementById('modalEvento')).show();
                } else if (result.isDenied) {
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
                Swal.fire('Resultado', data.mensaje, 'info');
                bootstrap.Modal.getInstance(document.getElementById('modalEvento')).hide();
                limpiarFormulario();
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
