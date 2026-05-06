"""
Servicio para gestionar clientes
"""
from models.Client import Client, ClientSchema
from db import SessionLocal


def get_all_clients():
    """Obtiene todos los clientes"""
    session = SessionLocal()
    try:
        clients = session.query(Client).all()
        return [ClientSchema.model_validate(c) for c in clients]
    except Exception as e:
        print(f"Error retrieving all clients: {e}")
        return []
    finally:
        session.close()


def get_client_by_id(client_id):
    """Obtiene un cliente por ID"""
    session = SessionLocal()
    try:
        return session.query(Client).filter_by(id=client_id).first()
    except Exception as e:
        print(f"Error retrieving client by id: {e}")
        return None
    finally:
        session.close()


def create_client(client_schema):
    """Crea un nuevo cliente"""
    session = SessionLocal()
    try:
        client = Client(**client_schema.model_dump())
        session.add(client)
        session.commit()
        session.refresh(client)
        return ClientSchema.model_validate(client)
    except Exception as e:
        session.rollback()
        print(f"Error creating client: {e}")
        return None
    finally:
        session.close()


def update_client(client_id, client_schema):
    """Actualiza un cliente existente"""
    session = SessionLocal()
    try:
        existing = session.query(Client).filter_by(id=client_id).first()
        if not existing:
            return None
        
        update_data = client_schema.model_dump(exclude_unset=True)
        for key, value in update_data.items():
            setattr(existing, key, value)
        
        session.commit()
        session.refresh(existing)
        return ClientSchema.model_validate(existing)
    except Exception as e:
        session.rollback()
        print(f"Error updating client: {e}")
        return None
    finally:
        session.close()


def delete_client(client_id):
    """Elimina un cliente"""
    session = SessionLocal()
    try:
        existing = session.query(Client).filter_by(id=client_id).first()
        if existing:
            session.delete(existing)
            session.commit()
            return {"success": True}
        return {"success": False, "error": "Client not found"}
    except Exception as e:
        session.rollback()
        print(f"Error deleting client: {e}")
        return {"success": False, "error": str(e)}
    finally:
        session.close()