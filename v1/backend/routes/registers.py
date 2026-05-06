"""
Rutas para gestionar registros
"""
from fastapi import APIRouter, Depends, HTTPException, status
from services import registers_service
from auth import get_current_user, require_admin
from models.Register import RegisterSchema

router = APIRouter(prefix="/registers", tags=["registers"])


@router.get("", response_model=list[RegisterSchema])
def get_registers(current_user: dict = Depends(get_current_user)):
    """Obtiene todos los registros (requiere autenticación)"""
    if not current_user:
        raise HTTPException(status_code=401, detail="Usuario no autenticado.")
    return registers_service.get_all_registers()


@router.get("/{register_id}", response_model=RegisterSchema)
def get_register(register_id: int, current_user: dict = Depends(get_current_user)):
    """Obtiene un registro por ID"""
    if not current_user:
        raise HTTPException(status_code=401, detail="Usuario no autenticado.")
    return registers_service.get_register_by_id(register_id)


@router.post("", response_model=RegisterSchema)
def create_register(register: RegisterSchema, current_user: dict = Depends(require_admin)):
    """Crea un nuevo registro (requiere permisos de admin)"""
    return registers_service.create_register(register)


@router.put("/{register_id}", response_model=RegisterSchema)
def update_register(
    register_id: int,
    register: RegisterSchema,
    current_user: dict = Depends(require_admin)
):
    """Actualiza un registro (requiere permisos de admin)"""
    return registers_service.update_register(register_id, register)


@router.delete("/{register_id}")
def delete_register(register_id: int, current_user: dict = Depends(require_admin)):
    """Elimina un registro (requiere permisos de admin)"""
    return registers_service.delete_register(register_id)