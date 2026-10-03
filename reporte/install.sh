#!/ invasion/bash
# Actualizar sistema e instalar dependencias
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-gd php-curl php-zip php-xml

# Crear Base de Datos
sudo mysql -e "CREATE DATABASE IF NOT EXISTS reporte_fotografico;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'admin_user'@'localhost' IDENTIFIED BY 'Password123!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON reporte_fotografico.* TO 'admin_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Crear tabla de inventario y usuarios
sudo mysql reporte_fotografico <<EOF
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    rol ENUM('admin', 'operador') DEFAULT 'operador'
);
CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    n_inventario VARCHAR(50),
    n_placa VARCHAR(50),
    n_tipo VARCHAR(50),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fotos TEXT
);
INSERT INTO usuarios (username, password, rol) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
EOF

# Configurar permisos de carpeta
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
mkdir -p /var/www/html/uploads
sudo chown www-data:www-data /var/www/html/uploads

echo "Instalación completada. Accede a http://localhost"
