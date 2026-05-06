"""
Modelo User - para almacenar información de usuarios
"""
from pydantic import BaseModel
from sqlalchemy import Column, Integer, String
from db import Base


class User(Base):
    """Tabla de usuarios en la BD"""
    __tablename__ = 'users'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    nombre = Column(String(255))
    email = Column(String(255), unique=True, nullable=False)
    contra_hash = Column(String(255))
    isAdmin = Column(Integer, nullable=False, default=0)


class UserSchema(BaseModel):
    """Schema para validar datos de usuarios"""
    id: int | None = None
    nombre: str | None = None
    email: str | None = None
    contra_hash: str | None = None
    isAdmin: bool | None = False
    
    class Config:
        from_attributes = True