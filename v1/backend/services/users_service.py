"""
Servicio para gestionar usuarios
"""
from models.User import User, UserSchema
from db import SessionLocal
import bcrypt
import pymysql


def get_all_users():
    """Obtiene todos los usuarios"""
    session = SessionLocal()
    try:
        users = session.query(User).all()
        return [UserSchema.model_validate(u) for u in users]
    except Exception as e:
        print(f"Error retrieving all users: {e}")
        return []
    finally:
        session.close()


def get_user_by_id(user_id):
    """Obtiene un usuario por ID"""
    session = SessionLocal()
    try:
        return session.query(User).filter_by(id=user_id).first()
    except Exception as e:
        print(f"Error retrieving user by id: {e}")
        return None
    finally:
        session.close()


def get_user_by_email(email):
    """Obtiene un usuario por email"""
    session = SessionLocal()
    try:
        return session.query(User).filter_by(email=email).first()
    except Exception as e:
        print(f"Error retrieving user by email: {e}")
        return None
    finally:
        session.close()


def create_user(user_schema):
    """Crea un nuevo usuario"""
    session = SessionLocal()
    try:
        user_data = user_schema.model_dump()
        
        # Hash password
        password_field = user_data.get('contra_hash') or user_data.get('password')
        if password_field:
            user_data['contra_hash'] = bcrypt.hashpw(
                password_field.encode('utf-8'),
                bcrypt.gensalt()
            ).decode('utf-8')
        
        user_data.pop('password', None)
        user = User(**user_data)
        session.add(user)
        session.commit()
        session.refresh(user)
        return UserSchema.model_validate(user)
    except Exception as e:
        session.rollback()
        if hasattr(e, 'orig') and isinstance(e.orig, pymysql.err.IntegrityError):
            if 'Duplicate entry' in str(e.orig):
                return {"error": "El email ya existe en la base de datos."}
        print(f"Error creating user: {e}")
        return {"error": str(e)}
    finally:
        session.close()


def update_user(user_id, user_schema):
    """Actualiza un usuario existente"""
    session = SessionLocal()
    try:
        existing = session.query(User).filter_by(id=user_id).first()
        if not existing:
            return None
        
        update_data = user_schema.model_dump(exclude_unset=True)
        
        # Handle password hashing
        if 'contra_hash' in update_data and update_data['contra_hash']:
            update_data['contra_hash'] = bcrypt.hashpw(
                update_data['contra_hash'].encode('utf-8'),
                bcrypt.gensalt()
            ).decode('utf-8')
        
        update_data.pop('password', None)
        
        for key, value in update_data.items():
            setattr(existing, key, value)
        
        session.commit()
        session.refresh(existing)
        return UserSchema.model_validate(existing)
    except Exception as e:
        session.rollback()
        print(f"Error updating user: {e}")
        return None
    finally:
        session.close()


def delete_user(user_id):
    """Elimina un usuario"""
    session = SessionLocal()
    try:
        existing = session.query(User).filter_by(id=user_id).first()
        if existing:
            session.delete(existing)
            session.commit()
            return {"success": True}
        return {"success": False, "error": "User not found"}
    except Exception as e:
        session.rollback()
        print(f"Error deleting user: {e}")
        return {"success": False, "error": str(e)}
    finally:
        session.close()