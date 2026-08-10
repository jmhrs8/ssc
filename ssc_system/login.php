// Verificamos contraseña y cargamos los datos en la sesión
        if ($user && password_verify($password_input, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre']  = $user['nombre'];

            $nivel_limpio = strtoupper(trim($user['nivel']));
            $_SESSION['nivel'] = $nivel_limpio; // CAPTURISTA o ADMIN_GENERAL

            // --- INICIO DE LÓGICA DE PERMISOS MEJORADA ---
            $modulo = strtoupper(trim($user['modulo_acceso'] ?? ''));
            
            // ¡IMPORTANTE! Guardamos el módulo en la sesión para que index.php pueda leerlo
            $_SESSION['modulo_acceso'] = $user['modulo_acceso']; 

            // Si es ADMIN_GENERAL, tiene permiso a TODO automáticamente
            if ($nivel_limpio === 'ADMIN_GENERAL' || $modulo === 'TODOS') {
                $_SESSION['permiso_siniestros'] = 1;
                $_SESSION['permiso_radios']     = 1;
                $_SESSION['permiso_personal']   = 1;
                $_SESSION['permiso_armas']      = 1;
            } else {
                // Si es capturista, solo al módulo específico asignado
                $_SESSION['permiso_siniestros'] = ($modulo === 'SINIESTROS') ? 1 : 0;
                $_SESSION['permiso_radios']     = ($modulo === 'RADIOS') ? 1 : 0;
                $_SESSION['permiso_personal']   = ($modulo === 'PERSONAL') ? 1 : 0;
                $_SESSION['permiso_armas']      = ($modulo === 'ARMAMENTO') ? 1 : 0;
            }
            // --- FIN DE MODIFICACIÓN ---

            header("Location: index.php");
            exit;
