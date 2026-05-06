"""
Rutas para gestionar clientes
"""
from fastapi import APIRouter, Depends, HTTPException, status
from services import clients_service
from auth import get_current_user, require_admin
from models.Client import ClientSchema

router = APIRouter(prefix="/clients", tags=["clients"])


@router.get("", response_model=list[ClientSchema])
def get_clients():
    """Obtiene todos los clientes"""
    return clients_service.get_all_clients()


@router.get("/{client_id}", response_model=ClientSchema)
def get_client(client_id: int):
    """Obtiene un cliente por ID"""
    return clients_service.get_client_by_id(client_id)


@router.post("", response_model=ClientSchema)
def create_client(client: ClientSchema, current_user: dict = Depends(require_admin)):
    """Crea un nuevo cliente (requiere permisos de admin)"""
    return clients_service.create_client(client)


@router.put("/{client_id}", response_model=ClientSchema)
def update_client(
    client_id: int,
    client: ClientSchema,
    current_user: dict = Depends(require_admin)
):
    """Actualiza un cliente (requiere permisos de admin)"""
    return clients_service.update_client(client_id, client)


@router.delete("/{client_id}")
def delete_client(client_id: int, current_user: dict = Depends(require_admin)):
    """Elimina un cliente (requiere permisos de admin)"""
    return clients_service.delete_client(client_id)
