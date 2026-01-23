# Click Gastos

Sistema web para la gestión de gastos, ingresos y transferencias.

Proyecto desarrollado en PHP con estructura modular, pensado para uso local y despliegue en producción.

---

## 📦 Instalación

1. Clonar el repositorio:

```bash
git clone https://github.com/TU_USUARIO/click-gastos.git
cd click-gastos
```

2. Crear el archivo de configuración de base de datos:

```bash
cp config/database.example.php config/database.php
```

3. Editar `config/database.php` y completar los datos de conexión:

```php
<?php

return [
    'host'     => 'localhost',
    'database' => 'click_gastos',
    'user'     => 'USUARIO_DB',
    'password' => 'PASSWORD_DB',
];
```

4. Configurar el servidor web apuntando al directorio del proyecto.

---

## ⚙️ Requisitos

* PHP 7.0 o superior
* MySQL / MariaDB
* Servidor web (Apache / Nginx)

---

## 🔐 Seguridad

El archivo `config/database.php` **no se versiona** y está excluido en `.gitignore`.

Para nuevos entornos (local / staging / producción) se debe crear manualmente este archivo a partir de `database.example.php`.

---

## 🧱 Estructura del proyecto

* `accounts/` Gestión de cuentas
* `categories/` Categorías de gastos / ingresos
* `expenses/` Gastos
* `income/` Ingresos
* `products/` Productos
* `transfers/` Transferencias
* `config/` Configuración (no sensible versionada)
* `includes/` Helpers y archivos comunes
* `mobile/` Vistas o lógica mobile

---

## 🚀 Despliegue

* Crear `config/database.php` en el servidor
* Ajustar permisos si corresponde
* No es necesario modificar el repositorio para cambiar credenciales

---

## 📄 Licencia

Uso privado / interno.
