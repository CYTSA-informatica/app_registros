import React, { useEffect, useMemo, useState, useCallback } from "react";
import "./components.css";
import "../pages/pages.css";
import UserService from "../services/users_service.jsx";

/* =========================
   CrearEmpleado (simple)
   ========================= */
function CrearEmpleado({ onCancel, onCreated }) {
  const [form, setForm] = useState({
    nombre: "",
    email: "",
    contra_hash: "",
    isAdmin: false,
  });
  const [msg, setMsg] = useState("");

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((p) => ({ ...p, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const token = localStorage.getItem("token");
    if (!token) return setMsg("Error: no hay token.");

    try {
      setMsg("");
      const userService = new UserService();

      // 🔧 AJUSTA AQUÍ si tu método se llama distinto:
      const created = await userService.createUser(form, token);

      onCreated?.(created);
    } catch (err) {
      console.error(err);
      setMsg("Error al crear: " + (err?.message ?? String(err)));
    }
  };

  return (
    <form className="form-container" onSubmit={handleSubmit}>
      <h3>Crear Empleado</h3>

      <div className="form-row-2">
        <label>
          Nombre:
          <input
            type="text"
            name="nombre"
            value={form.nombre}
            onChange={handleChange}
            required
            placeholder="Nombre completo"
          />
        </label>

        <label>
          Email:
          <input
            type="email"
            name="email"
            value={form.email}
            onChange={handleChange}
            required
            placeholder="Correo electrónico"
          />
        </label>
      </div>

      <label>
        Contraseña:
        <input
          type="password"
          name="contra_hash"
          value={form.contra_hash}
          onChange={handleChange}
          required
          placeholder="Contraseña"
        />
      </label>

      <div className="toggle-row">
        <span className="toggle-label">Rol administrador</span>

        <label className="toggle">
          <input
            type="checkbox"
            checked={!!form.isAdmin}
            onChange={(e) => setForm((p) => ({ ...p, isAdmin: e.target.checked }))}
          />
          <span className="toggle-track" />
        </label>

        <span className={`toggle-badge ${form.isAdmin ? "on" : "off"}`}>
          {form.isAdmin ? "Administrador" : "Usuario"}
        </span>
      </div>

      <div className="form-actions">
        <button type="submit" className="btn-primary">Aceptar</button>
        <button type="button" className="btn-ghost" onClick={onCancel}>Cancelar</button>
      </div>

      {msg && (
        <div className={msg.startsWith("Error") ? "error-message" : "success-message"}>
          {msg}
        </div>
      )}
    </form>
  );
}

export default function Empleados() {
  const [empleados, setEmpleados] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selected, setSelected] = useState(null);
  const [editForm, setEditForm] = useState(null);
  const [msg, setMsg] = useState("");
  const [filtroNombre, setFiltroNombre] = useState("");
  const [showCreate, setShowCreate] = useState(false);

  const normalizarEmpleado = useCallback((emp) => {
    if (!emp) return null;
    return {
      ...emp,
      nombre: emp.nombre ?? "",
      email: emp.email ?? "",
      isAdmin: emp.isAdmin ?? emp.is_admin ?? false,
    };
  }, []);

  const loadEmpleados = useCallback(
    async (keepSelectedId = null) => {
      setIsLoading(true);
      const token = localStorage.getItem("token");
      if (!token) {
        setMsg("Error: no hay token. Vuelve a iniciar sesión.");
        setIsLoading(false);
        return;
      }

      try {
        const userService = new UserService();
        const empsRaw = await userService.getUsers(token);
        const emps = Array.isArray(empsRaw) ? empsRaw : [];

        setEmpleados(emps);

        const idToSelect = keepSelectedId ?? (emps.length ? emps[0].id : null);
        if (idToSelect != null) {
          const found = emps.find((x) => String(x.id) === String(idToSelect)) ?? emps[0];
          setSelected(found?.id ?? null);
          setEditForm(normalizarEmpleado(found));
        } else {
          setSelected(null);
          setEditForm(null);
        }
      } catch (err) {
        console.error(err);
        setMsg("Error cargando empleados.");
      } finally {
        setIsLoading(false);
      }
    },
    [normalizarEmpleado]
  );

  useEffect(() => {
    loadEmpleados();
  }, [loadEmpleados]);

  const empleadosFiltrados = useMemo(() => {
    const q = (filtroNombre || "").toLowerCase().trim();
    if (!q) return empleados;
    return empleados.filter((emp) => (emp.nombre ?? "").toLowerCase().includes(q));
  }, [empleados, filtroNombre]);

  const handleSelect = (emp) => {
    setSelected(emp.id);
    setEditForm(normalizarEmpleado(emp));
    setMsg("");
  };

  const handleEditChange = (e) => {
    const { name, value } = e.target;
    setEditForm((prev) => (prev ? { ...prev, [name]: value } : prev));
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();
    if (!editForm) return;

    const token = localStorage.getItem("token");
    if (!token) return setMsg("Error: no hay token.");

    try {
      setMsg("");
      const userService = new UserService();
      await userService.setUser(editForm.id, editForm, token);

      setMsg("Empleado actualizado ✅");
      await loadEmpleados(editForm.id);
    } catch (err) {
      console.error(err);
      setMsg("Error al actualizar: " + (err?.message ?? String(err)));
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("¿Seguro que quieres eliminar este empleado?")) return;

    const token = localStorage.getItem("token");
    if (!token) return setMsg("Error: no hay token.");

    try {
      setMsg("");
      const userService = new UserService();
      await userService.deleteUser(id, token);

      setMsg("Empleado eliminado ✅");
      const keep = selected === id ? null : selected;
      await loadEmpleados(keep);
    } catch (err) {
      console.error(err);
      setMsg("Error al eliminar: " + (err?.message ?? String(err)));
    }
  };

  if (isLoading) {
    return (
      <div className="page-container loading-screen" role="status" aria-live="polite" aria-busy="true">
        <div className="loader" aria-hidden="true" />
        <p className="loading-text">Cargando empleados...</p>
      </div>
    );
  }

  return (
    <div className="page-container">
      <h2>Empleados</h2>

      {msg && (
        <div className={msg.startsWith("Error") ? "error-message" : "success-message"}>
          {msg}
        </div>
      )}

      {/* ✅ Layout correcto para Clientes/Empleados */}
      <div className="table-container">
        {/* IZQUIERDA: filtros + tabla */}
        <div>
          <div className="filter-bar">
            <label>
              Nombre:
              <input
                type="text"
                placeholder="Buscar empleado..."
                value={filtroNombre}
                onChange={(e) => setFiltroNombre(e.target.value)}
              />
            </label>

            <button type="button" onClick={() => setShowCreate(true)}>
              Crear empleado
            </button>
          </div>

          <div className="table-scroll">
            <table className="pretty-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Rol</th>
                </tr>
              </thead>

              <tbody>
                {empleadosFiltrados.map((emp) => (
                  <tr
                    key={emp.id}
                    className={selected === emp.id ? "selected-row" : ""}
                    onClick={() => handleSelect(emp)}
                  >
                    <td>{emp.nombre ?? ""}</td>
                    <td>{emp.email ?? ""}</td>
                    <td>{(emp.isAdmin ?? emp.is_admin) ? "Administrador" : "Usuario"}</td>
                  </tr>
                ))}

                {empleadosFiltrados.length === 0 && (
                  <tr>
                    <td colSpan={3} className="table-empty">No hay empleados con ese filtro.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* DERECHA: crear / editar */}
        <div>
          {showCreate ? (
            <CrearEmpleado
              onCancel={() => setShowCreate(false)}
              onCreated={async (createdMaybe) => {
                setShowCreate(false);
                const createdId = createdMaybe?.id ?? null;
                await loadEmpleados(createdId);
                setMsg("Empleado creado ✅");
              }}
            />
          ) : (
            <form className="form-container" onSubmit={handleEditSubmit}>
              <h3>Editar Empleado</h3>

              {editForm ? (
                <>
                  {/* ✅ Nombre mitad + Email aprovecha el resto (misma fila) */}
                  <div className="form-row-2">
                    <label>
                      Nombre:
                      <input
                        type="text"
                        name="nombre"
                        value={editForm.nombre ?? ""}
                        onChange={handleEditChange}
                        required
                        placeholder="Nombre completo"
                      />
                    </label>

                    <label>
                      Email:
                      <input
                        type="email"
                        name="email"
                        value={editForm.email ?? ""}
                        onChange={handleEditChange}
                        required
                        placeholder="Correo electrónico"
                      />
                    </label>
                  </div>

                  <div className="toggle-row">
                    <span className="toggle-label">Rol administrador</span>

                    <label className="toggle">
                      <input
                        type="checkbox"
                        checked={!!editForm.isAdmin}
                        onChange={(e) =>
                          setEditForm((prev) => ({ ...prev, isAdmin: e.target.checked }))
                        }
                      />
                      <span className="toggle-track" />
                    </label>

                    <span className={`toggle-badge ${editForm.isAdmin ? "on" : "off"}`}>
                      {editForm.isAdmin ? "Administrador" : "Usuario"}
                    </span>
                  </div>

                  <button type="submit" className="btn-primary">
                    Guardar cambios
                  </button>

                  <button
                    type="button"
                    className="delete-btn delete-btn--spaced"
                    onClick={() => handleDelete(editForm.id)}
                  >
                    Eliminar empleado
                  </button>
                </>
              ) : (
                <div className="error-message">Selecciona un empleado para editar.</div>
              )}
            </form>
          )}
        </div>
      </div>
    </div>
  );
}