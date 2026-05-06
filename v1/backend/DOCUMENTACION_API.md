# Documentación Técnica - API de Registros de Tareas

## Descripción General
API REST para gestión de usuarios, clientes y registros de tareas, con autenticación JWT, roles (admin/user), integración con MariaDB y protección de endpoints. Backend en FastAPI y Docker.

---

## Flujos Principales

### 1. Autenticación y Autorización
- **Login:**
  - Endpoint: `POST /token`
  - Recibe usuario y contraseña.
  - Verifica el hash bcrypt contra la base de datos.
  - Devuelve un JWT válido con los campos:
    - `sub`: nombre de usuario
    - `isAdmin`: booleano (true/false)
- **Protección de Endpoints:**
  - Todos los endpoints requieren un token JWT válido en el header `Authorization: Bearer <token>`.
  - El backend valida la firma y extrae los datos del usuario y su rol desde el token.

### 2. Usuarios
- **Obtener usuarios:**
  - `GET /users` (requiere JWT)
- **Crear usuario:**
  - `POST /users` (requiere JWT)
  - Si el email ya existe, devuelve error 400.
- **Actualizar usuario:**
  - `PUT /users/{id}` (requiere JWT)
- **Eliminar usuario:**
- **Obtener clientes:**
  - `GET /clients` (requiere JWT)
- **Crear cliente:**
  - `POST /clients` (requiere JWT)
  - Campos: `nombre`, `email`
- **Actualizar cliente:**
  - `PUT /clients/{id}` (requiere JWT)
- **Eliminar cliente:**
  - `DELETE /clients/{id}` (requiere JWT)

### 4. Registros de Tareas
- **Obtener registros:**
  - `GET /registers` (requiere JWT)
- **Crear registro:**
  - `POST /registers` (requiere JWT)
  - Campos: `duracion`, `descripcion`, `estado`, `notas`, `id_empleado`, `id_cliente`, `fecha_creacion`
- **Actualizar registro:**
  - `PUT /registers/{id}` (requiere JWT)
- **Eliminar registro:**
  - `DELETE /registers/{id}` (requiere JWT)

---

## Detalles Técnicos

### Estructura de la Base de Datos (MariaDB)
- **users:**
  - `id` (PK, autoincrement)
  - `nombre` (varchar)
  - `email` (varchar, único)
  - `contra_hash` (varchar, bcrypt)
  - `isAdmin` (boolean, indica si el usuario es admin)
  - `email` (varchar, único)
  - `id` (PK, autoincrement)
  - `duracion` (int)
  - `descripcion` (text)
  - `estado` (enum)
  - `notas` (text)
  - `id_empleado` (int)
  - `id_cliente` (int)
  - `fecha_creacion` (timestamp)

### Modelos y Esquemas
- Modelos SQLAlchemy y esquemas Pydantic alineados 1:1 con la base de datos.
- Conversión de tipos (por ejemplo, `fecha_creacion` a string) para compatibilidad con Pydantic.
- El campo `isAdmin` está presente en el modelo y el schema de usuario.

### Seguridad y Roles
- Contraseñas almacenadas con bcrypt.
- JWT firmado con clave secreta y algoritmo HS256.
- El token JWT incluye el campo `isAdmin` para distinguir entre usuarios admin y normales.
- Todos los endpoints protegidos excepto `/token`.
- Puedes usar el campo `isAdmin` del token en los endpoints para controlar permisos.

### Manejo de Errores
- Errores de integridad (por ejemplo, email duplicado) devuelven HTTP 400 con mensaje claro.
- Errores de validación de datos gestionados y reportados.

### Docker
- Backend y base de datos corren en contenedores separados.
- Variables sensibles gestionadas por `.env`.

### Pruebas
- Archivo `api_test.http` con ejemplos de todos los endpoints y payloads correctos.

---

## Ejemplo de Uso

1. **Login y obtención de token:**
   - Realiza un POST a `/token` con usuario y contraseña.
   - El token JWT recibido contendrá el campo `isAdmin`.
   - Usa el token en el header `Authorization` para todas las peticiones siguientes.
2. **CRUD de usuarios, clientes y registros:**
   - Usa los endpoints correspondientes con el token JWT.
   - Payloads deben usar los nombres de campos exactos según la base de datos.
   - Puedes comprobar el rol del usuario leyendo el campo `isAdmin` del token.

---

## Notas
- Si cambias la estructura de la base de datos, actualiza los modelos y esquemas para mantener la compatibilidad.
- Si cambias el algoritmo de hash o el método de autenticación, revisa el flujo de login y registro.
- Si quieres proteger endpoints solo para admin, revisa el campo `isAdmin` del token en el backend.

---

> Última actualización: 24/02/2026
