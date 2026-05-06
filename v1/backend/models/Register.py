"""
Modelo Register - para almacenar registros de tareas
"""
from pydantic import BaseModel
from sqlalchemy import Column, Integer, String
from db import Base


class Register(Base):
    """Tabla de registros en la BD"""
    __tablename__ = 'registers'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    duracion = Column(Integer)
    descripcion = Column(String)
    estado = Column(String(32))
    notas = Column(String)
    id_empleado = Column(Integer)
    id_cliente = Column(Integer)
    fecha_creacion = Column(String)


class RegisterSchema(BaseModel):
    """Schema para validar datos de registros"""
    id: int | None = None
    duracion: int | None = None
    descripcion: str | None = None
    estado: str | None = None
    notas: str | None = None
    id_empleado: int | None = None
    id_cliente: int | None = None
    fecha_creacion: str | None = None
    
    class Config:
        from_attributes = True