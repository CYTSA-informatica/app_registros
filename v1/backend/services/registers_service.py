"""
Servicio para gestionar registros
"""
from models.Register import Register, RegisterSchema
from db import SessionLocal


def _convert_to_schema(register):
    """Convierte un objeto Register a RegisterSchema"""
    data = register.__dict__.copy()
    if 'fecha_creacion' in data and data['fecha_creacion'] is not None:
        data['fecha_creacion'] = str(data['fecha_creacion'])
    data.pop('_sa_instance_state', None)
    return RegisterSchema(**data)


def get_all_registers():
    """Obtiene todos los registros"""
    session = SessionLocal()
    try:
        registers = session.query(Register).all()
        return [_convert_to_schema(r) for r in registers]
    except Exception as e:
        print(f"Error retrieving all registers: {e}")
        return []
    finally:
        session.close()


def get_register_by_id(register_id):
    """Obtiene un registro por ID"""
    session = SessionLocal()
    try:
        return session.query(Register).filter_by(id=register_id).first()
    except Exception as e:
        print(f"Error retrieving register by id: {e}")
        return None
    finally:
        session.close()


def get_register_by_user(user_id):
    """Obtiene todos los registros de un usuario"""
    session = SessionLocal()
    try:
        return session.query(Register).filter_by(id_empleado=user_id).all()
    except Exception as e:
        print(f"Error retrieving register by user id: {e}")
        return []
    finally:
        session.close()


def create_register(register_schema):
    """Crea un nuevo registro"""
    session = SessionLocal()
    try:
        reg_data = register_schema.model_dump(exclude={'fecha_creacion'})
        register = Register(**reg_data)
        session.add(register)
        session.commit()
        session.refresh(register)
        return _convert_to_schema(register)
    except Exception as e:
        session.rollback()
        print(f"Error creating register: {e}")
        return None
    finally:
        session.close()


def update_register(register_id, register_schema):
    """Actualiza un registro existente"""
    session = SessionLocal()
    try:
        existing = session.query(Register).filter_by(id=register_id).first()
        if not existing:
            return None
        
        update_data = register_schema.model_dump(exclude_unset=True, exclude={'fecha_creacion'})
        for key, value in update_data.items():
            setattr(existing, key, value)
        
        session.commit()
        session.refresh(existing)
        return _convert_to_schema(existing)
    except Exception as e:
        session.rollback()
        print(f"Error updating register: {e}")
        return None
    finally:
        session.close()


def delete_register(register_id):
    """Elimina un registro"""
    session = SessionLocal()
    try:
        existing = session.query(Register).filter_by(id=register_id).first()
        if existing:
            session.delete(existing)
            session.commit()
            return {"success": True}
        return {"success": False, "error": "Register not found"}
    except Exception as e:
        session.rollback()
        print(f"Error deleting register: {e}")
        return {"success": False, "error": str(e)}
    finally:
        session.close()