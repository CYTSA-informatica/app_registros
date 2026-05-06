"""
Funciones de autenticación y autorización centralizadas
"""
from fastapi import Depends, HTTPException, status
from jose import jwt, JWTError
from config import SECRET_KEY, ALGORITHM, ERROR_MESSAGES
from fastapi.security import OAuth2PasswordBearer

oauth2_scheme = OAuth2PasswordBearer(tokenUrl="token")


def get_current_user(token: str = Depends(oauth2_scheme)) -> dict:
    """
    Valida el token JWT y retorna el payload del usuario
    """
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail=ERROR_MESSAGES["invalid_token"],
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        if not payload.get("email"):
            raise credentials_exception
        return payload
    except JWTError:
        raise credentials_exception


def require_admin(current_user: dict = Depends(get_current_user)) -> dict:
    """
    Valida que el usuario actual sea administrador
    """
    if not current_user.get("isAdmin"):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=ERROR_MESSAGES["admin_required"],
        )
    return current_user


def require_owned_resource(user_id: int, current_user: dict = Depends(get_current_user)) -> dict:
    """
    Valida que el usuario actual sea propietario del recurso o sea admin
    """
    if current_user.get("user_id") != user_id and not current_user.get("isAdmin"):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=ERROR_MESSAGES["forbidden"],
        )
    return current_user
