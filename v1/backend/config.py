"""
Configuración centralizada de la aplicación
"""
import os
from dotenv import load_dotenv

load_dotenv()

# Secret Keys
SECRET_KEY = os.getenv("SECRET_KEY")
ALGORITHM = os.getenv("ALGORITHM")

# Database Configuration
MYSQL_USER = os.getenv("MYSQL_USER")
MYSQL_PASSWORD = os.getenv("MYSQL_PASSWORD")
MYSQL_HOST = os.getenv("MYSQL_HOST")
MYSQL_PORT = os.getenv("MYSQL_PORT")
MYSQL_DATABASE = os.getenv("MYSQL_DATABASE")

DATABASE_URL = f'mysql+pymysql://{MYSQL_USER}:{MYSQL_PASSWORD}@{MYSQL_HOST}:{MYSQL_PORT}/{MYSQL_DATABASE}'

# API Authentication
API_USERNAME = os.getenv("API_USERNAME")
API_PASSWORD = os.getenv("API_PASSWORD")

# Error Messages
ERROR_MESSAGES = {
    "invalid_credentials": "Invalid credentials",
    "invalid_token": "Could not validate credentials",
    "unauthorized": "Usuario no autenticado",
    "forbidden": "No tienes permisos para realizar esta acción",
    "admin_required": "Se requieren permisos de administrador",
    "email_exists": "El email ya existe en la base de datos",
    "not_found": "Recurso no encontrado",
}
