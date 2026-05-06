"""
Servicio para autenticación
"""
import bcrypt
from fastapi import HTTPException
from services.users_service import get_user_by_email
from jose import jwt
from config import SECRET_KEY, ALGORITHM, ERROR_MESSAGES
from models.Login import LoginRequest, LoginResponse


def create_access_token(data: dict) -> str:
    """Crea un token JWT"""
    encoded_jwt = jwt.encode(data, SECRET_KEY, algorithm=ALGORITHM)
    return encoded_jwt


def _verify_credentials(user, password: str):
    """Verifica credenciales del usuario"""
    if not user or not user.contra_hash:
        raise HTTPException(status_code=401, detail=ERROR_MESSAGES["invalid_credentials"])
    
    if not bcrypt.checkpw(password.encode('utf-8'), user.contra_hash.encode('utf-8')):
        raise HTTPException(status_code=401, detail=ERROR_MESSAGES["invalid_credentials"])


def authenticate_login(request: LoginRequest) -> LoginResponse:
    """Autentica un usuario con email y contraseña"""
    user = get_user_by_email(request.email)
    _verify_credentials(user, request.password)
    
    token = create_access_token({
        "user_id": user.id,
        "email": user.email,
        "isAdmin": user.isAdmin
    })
    
    return LoginResponse(
        access_token=token,
        token_type="bearer",
        isAdmin=bool(user.isAdmin),
        user_id=user.id,
        name=user.nombre,
        email=user.email
    )


def authenticate_login_form(form_data) -> LoginResponse:
    """Autentica un usuario usando formulario OAuth2"""
    user = get_user_by_email(form_data.username)
    _verify_credentials(user, form_data.password)
    
    token = create_access_token({
        "user_id": user.id,
        "email": user.email,
        "isAdmin": user.isAdmin
    })
    
    return LoginResponse(
        access_token=token,
        token_type="bearer",
        isAdmin=bool(user.isAdmin),
        user_id=user.id,
        name=user.nombre,
        email=user.email
    )


def logout_service():
    """Servicio de logout"""
    return {"message": "Logout successful. Please delete your token on the client."}
