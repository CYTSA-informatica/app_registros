"""
Rutas para autenticación
"""
from fastapi import APIRouter, Depends
from fastapi.security import OAuth2PasswordRequestForm
from models.Login import LoginRequest, LoginResponse
from services.logins_service import authenticate_login, authenticate_login_form, logout_service

router = APIRouter(prefix="/auth", tags=["auth"])


@router.post('/login', response_model=LoginResponse)
def login(request: LoginRequest):
    """Inicia sesión con email y contraseña"""
    return authenticate_login(request)


@router.post('/token', response_model=LoginResponse)
def login_token(form_data: OAuth2PasswordRequestForm = Depends()):
    """Endpoint de token alternativo"""
    return authenticate_login_form(form_data)


@router.post('/logout')
def logout():
    """Cierra la sesión del usuario"""
    return logout_service()
