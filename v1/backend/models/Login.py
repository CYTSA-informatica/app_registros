"""
Modelos para autenticación
"""
from pydantic import BaseModel


class LoginRequest(BaseModel):
    """Schema para solicitud de login"""
    email: str
    password: str


class LoginResponse(BaseModel):
    """Schema para respuesta de login"""
    access_token: str
    token_type: str
    isAdmin: bool
    user_id: int
    name: str
    email: str
