"""
Gestión de conexión a la base de datos
"""
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, declarative_base
from config import DATABASE_URL

engine = create_engine(DATABASE_URL)
SessionLocal = sessionmaker(bind=engine)
Base = declarative_base()


def get_session():
    """
    Crea una nueva sesión de base de datos
    """
    session = SessionLocal()
    try:
        yield session
    finally:
        session.close()


def get_db():
    """
    Alias para obtener una sesión de BD (alternativa)
    """
    return SessionLocal()
