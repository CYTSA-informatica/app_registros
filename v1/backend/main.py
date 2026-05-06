# uvicorn main:app --host 0.0.0.0 --port 8000 --reload

"""
API Principal - Registros de Tareas
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routes import registers, clients, users, logins

app = FastAPI(
    title="Registros de Tareas API",
    description="API para gestionar registros de tareas, clientes y usuarios",
    version="1.0.0"
)

# CORS Middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health Check
@app.get("/")
def read_root():
    """Endpoint raíz"""
    return {"message": "API running"}


@app.get("/health")
def health():
    """Verifica el estado de la API"""
    return {"status": "ok"}


# Incluir routers
app.include_router(registers.router)
app.include_router(clients.router)
app.include_router(users.router)
app.include_router(logins.router)

# Compatibilidad retroactiva: mantiene soporte para clientes que aun usan /api
app.include_router(registers.router, prefix="/api")
app.include_router(clients.router, prefix="/api")
app.include_router(users.router, prefix="/api")
app.include_router(logins.router, prefix="/api")