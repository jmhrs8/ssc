#!/bin/bash

#################################################################################
# Script de Respaldo Automatizado y Continuidad de Negocio                      #
# Autor: Ing. Juan Manuel Hernandez Lugo                                        #
#                                                                               #
# Version 1.5 - Corrección de sintaxis Bash, exportación activa de BD MySQL,   #
#               empaquetado directo con TAR, ajuste de flags de correo y        #
#               transferencia SCP optimizada.                                   #
#################################################################################

#####################   Parametros Iniciales   #################################
DATE=$(date +%Y-%m-%d_%H-%M-%S)
HOSTNAME=$(hostname)

# 1. Directorios y archivos a respaldar
LOCAL_DIR[1]="/var/www/html/control_gestion/"
LOCAL_DIR[2]="/var/www/html/ssc_system/"

# 2. Comando MySQL activo para Respaldo de Base de Datos
COMM[1]="mysqldump -u root -pjmhl2474 --single-transaction --quick ssc_control_gestion"

# 3. Datos del Servidor Remoto de Respaldos
DIR_TEMP="/tmp/bkp_$DATE"
REMOTE_SERVER="localhost"              # Cambiar por la IP real en producción
BACKUPDIR="/home/aseguramiento/backup/$HOSTNAME/"
PTO=22
USER="root"

# Flag de Errores para el Correo
FLG_MAIL=0
LOG_FILE="/var/log/bkupserver.log"

################################################################################

echo ""
echo "<<      Inicio del Proceso de Respaldo y Sincronización      >>"
echo ""

# Crear directorio temporal exclusivo
mkdir -p "$DIR_TEMP"

## 1. Ejecución de Comandos Previos (Dump MySQL)
echo "     <<   Ejecutando volcado de Base de Datos (MySQL)...   >>"
for c in "${!COMM[@]}"; do
    # Ejecuta el dump guardando la BD directamente en la carpeta temporal
    ${COMM[$c]} > "$DIR_TEMP/bd_dump_$c.sql" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo -e "$(date +'%b %d %H:%M:%S') $USER BkupServers: Comando MySQL $c OK" >> "$LOG_FILE"
        echo "     << BD Dump $c completado con éxito >>"
    else
        FLG_MAIL=1
        echo -e "$(date +'%b %d %H:%M:%S') $USER BkupServers: ERROR en Comando MySQL $c" >> "$LOG_FILE"
        echo "     << ERROR al exportar la Base de Datos $c >>"
    fi
done

## 2. Copia de Archivos y Directorios
echo ""
echo "     <<   Empaquetando Archivos y Códigos del Sistema...   >>"
for i in "${!LOCAL_DIR[@]}"; do
    ORIGEN="${LOCAL_DIR[$i]}"
    if [ -d "$ORIGEN" ] || [ -f "$ORIGEN" ]; then
        # Extraer el nombre de la carpeta/archivo para la subcarpeta de destino
        NOMBRE_SUBDIR=$(basename "$ORIGEN")
        mkdir -p "$DIR_TEMP/$NOMBRE_SUBDIR"
        
        echo "     << Copiando contenido desde: $ORIGEN >>"
        cp -r "$ORIGEN"* "$DIR_TEMP/$NOMBRE_SUBDIR/" 2>/dev/null
    else
        echo "     << ADVERTENCIA: La ruta $ORIGEN no existe >>"
    fi
done

## 3. Empaquetado y Compresión TAR.GZ
echo ""
echo "     <<   Comprimiendo Paquete Final...   >>"
TAR_FILE="/tmp/bk-$HOSTNAME-$DATE.tar.gz"
tar -czf "$TAR_FILE" -C "$DIR_TEMP" . 2>/dev/null

if [ $? -eq 0 ]; then
    echo "     << Archivo comprimido creado: $TAR_FILE >>"
else
    FLG_MAIL=1
    echo "     << ERROR al crear el archivo comprimido >>"
fi

## 4. Transferencia Servidor Remoto (SCP)
echo ""
echo "     <<   Iniciando Transferencia Remota...   >>"

# Creación de directorio remoto previa transmisión (si no existe)
ssh -P $PTO $USER@$REMOTE_SERVER "mkdir -p $BACKUPDIR" 2>/dev/null

# Transferencia mediante SCP (Opción -P mayúscula para el puerto)
scp -P $PTO "$TAR_FILE" "$USER@$REMOTE_SERVER:$BACKUPDIR"
ERROR_SCP=$?

if [ $ERROR_SCP -eq 0 ]; then
    echo -e "$(date +'%b %d %H:%M:%S') $USER BkupServers: Transferencia SCP Completa con Exito " >> "$LOG_FILE"
    echo "     << Transferencia Completa >>"
else
    FLG_MAIL=1
    echo -e "$(date +'%b %d %H:%M:%S') $USER BkupServers: ERROR en Sincronización SCP Code: $ERROR_SCP " >> "$LOG_FILE"
    echo "     << ERROR en la Transferencia >>"
fi

## 5. Limpieza de Archivos Temporales
echo ""
echo "     <<   Limpiando archivos temporales...   >>"
rm -rf "$DIR_TEMP"
rm -f "$TAR_FILE"

## 6. Envío de Notificaciones por Correo Electrónico
if [ $FLG_MAIL -eq 0 ]; then
    # Notificación de ÉXITO
    (
        echo "From: respaldos@aseguramiento.ssc.cdmx.gob.mx"
        echo "To: jmhrs8@gmail.com"
        echo "Subject: [EXITO] Respaldo de Servidor $HOSTNAME"
        echo ""
        echo "El proceso de respaldo del servidor $HOSTNAME finalizó correctamente."
        echo "Fecha y Hora: $(date +'%Y-%m-%d %H:%M:%S')"
        echo "Archivo enviado a $REMOTE_SERVER:$BACKUPDIR"
    ) | sendmail -t
else
    # Notificación de ERROR
    (
        echo "From: respaldos@aseguramiento.ssc.cdmx.gob.mx"
        echo "To: jmhrs8@gmail.com"
        echo "Subject: [[ALERT]] ERROR en Respaldo de Servidor $HOSTNAME"
        echo ""
        echo "Se han detectado fallos durante el proceso de respaldo en $HOSTNAME."
        echo "Por favor revise el archivo de log en /var/log/bkupserver.log"
    ) | sendmail -t
fi

echo ""
echo "<<  Proceso Finalizado Exitosamente  >>"
echo ""
