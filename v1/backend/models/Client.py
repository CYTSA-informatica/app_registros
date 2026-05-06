"""
Modelo Client - para almacenar información de clientes
"""
from pydantic import BaseModel
from sqlalchemy import Column, Integer, String
from db import Base


class Client(Base):
    """Tabla de clientes en la BD"""
    __tablename__ = 'clients'
    
    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(100), nullable=False)
    email = Column(String(100), nullable=False, unique=True)
    phone = Column(String(20))
    address = Column(String(200))
    dni = Column(String(20))
    pais = Column(String(50))
    postal = Column(String(10))
    poblacion = Column(String(100))
    provincia = Column(String(100))


class ClientSchema(BaseModel):
    """Schema para validar datos de clientes"""
    id: int | None = None
    nombre: str | None = None
    email: str | None = None
    phone: str | None = None
    address: str | None = None
    dni: str | None = None
    pais: str | None = None
    postal: str | None = None
    poblacion: str | None = None
    provincia: str | None = None

    class Config:
        from_attributes = True