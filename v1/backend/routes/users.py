"""
Rutas para gestionar usuarios
"""
from fastapi import APIRouter, Depends, HTTPException, status
from services import users_service, registers_service
from auth import get_current_user, require_admin, require_owned_resource
from models.User import UserSchema

router = APIRouter(prefix="/users", tags=["users"])


@router.get("", response_model=list[UserSchema])
def get_users():
    """Obtiene todos los usuarios"""
    return users_service.get_all_users()


@router.get("/{user_id}", response_model=UserSchema)
def get_user(user_id: int):
    """Obtiene un usuario por ID"""
    return users_service.get_user_by_id(user_id)


@router.get("/{user_id}/registers")
def get_user_registers(
    user_id: int,
    current_user: dict = Depends(get_current_user)
):
    """Obtiene los registros de un usuario (solo el propietario o admin)"""
    require_owned_resource(user_id, current_user)
    return registers_service.get_register_by_user(user_id)


@router.post("", response_model=UserSchema)
def create_user(user: UserSchema, current_user: dict = Depends(require_admin)):
    """Crea un nuevo usuario (requiere permisos de admin)"""
    result = users_service.create_user(user)
    if isinstance(result, dict) and result.get("error"):
        raise HTTPException(status_code=400, detail=result["error"])
    return result


@router.put("/{user_id}", response_model=UserSchema)
def update_user(
    user_id: int,
    user: UserSchema,
    current_user: dict = Depends(require_admin)
):
    """Actualiza un usuario (requiere permisos de admin)"""
    return users_service.update_user(user_id, user)


@router.delete("/{user_id}")
def delete_user(user_id: int, current_user: dict = Depends(require_admin)):
    """Elimina un usuario (requiere permisos de admin)"""
    return users_service.delete_user(user_id)
