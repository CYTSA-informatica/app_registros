import React, { useEffect, useMemo, useRef, useState, useCallback } from "react";
import "./components.css";
import "../pages/pages.css";

import ClientService from "../services/clients_service.jsx";
import UserService from "../services/users_service.jsx";
import RegisterService from "../services/registers_service.jsx";

/* =========================
   CLIENTE COMBO (arreglado)
   ========================= */
function ClienteCombo({ clientes = [], value, onChange }) {
  const [input, setInput] = useState("");
  const [showDropdown, setShowDropdown] = useState(false);
  const wrapperRef = useRef(null);

  useEffect(() => {
    if (value === null || value === undefined || value === "") {
      setInput("");
      return;
    }
    const cli = clientes.find((c) => String(c.id) === String(value));
    setInput(cli ? cli.nombre : "");
  }, [value, clientes]);

  useEffect(() => {
    const onDocMouseDown = (e) => {
      if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
        setShowDropdown(false);
      }
    };
    document.addEventListener("mousedown", onDocMouseDown);
    return () => document.removeEventListener("mousedown", onDocMouseDown);
  }, []);

  const clientesFiltrados = useMemo(() => {
    const q = (input || "").toLowerCase();
    return clientes.filter((c) => (c.nombre || "").toLowerCase().includes(q));
  }, [clientes, input]);

  return (
    <div ref={wrapperRef} className="cliente-combo">
      <div className="cliente-combo-row">
        <input
          className="cliente-combo-input"
          type="text"
          placeholder="Buscar cliente..."
          value={input}
          onFocus={() => setShowDropdown(true)}
          onChange={(e) => {
            setInput(e.target.value);
            setShowDropdown(true);
            onChange(null); // limpiar selección al teclear
          }}
        />
        <button
          className="cliente-combo-btn"
          type="button"
          onClick={() => setShowDropdown((v) => !v)}
          tabIndex={-1}
          aria-label="Abrir lista de clientes"
        >
          ▼
        </button>
      </div>

      {showDropdown && (
        <div className="cliente-dropdown">
          {clientesFiltrados.length === 0 ? (
            <div className="cliente-dropdown-empty">Sin resultados</div>
          ) : (
            clientesFiltrados.map((c) => (
              <button
                key={c.id}
                type="button"
                className="cliente-dropdown-item"
                onClick={() => {
                  onChange(c.id);
                  setInput(c.nombre);
                  setShowDropdown(false);
                }}
              >
                {c.nombre}
              </button>
            ))
          )}
        </div>
      )}
    </div>
  );
}

export default function MisRegistros() {
  const [clientes, setClientes] = useState([]);
  const [empleados, setEmpleados] = useState([]);
  const [registros, setRegistros] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  const [selected, setSelected] = useState(null);
  const [editForm, setEditForm] = useState(null);

  const [msg, setMsg] = useState("");

  // filtros
  const [filtroTexto, setFiltroTexto] = useState("");
  const [filtroEstado, setFiltroEstado] = useState("");
  const [filtroCliente, setFiltroCliente] = useState(null);

  const [user, setUser] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem("user") || "null");
    } catch {
      return null;
    }
  });

  const isAdmin = useMemo(() => !!(user?.isAdmin ?? user?.is_admin ?? false), [user]);

  const getToken = () => localStorage.getItem("token");

  const hydrateEditForm = useCallback((r) => {
    if (!r) return null;
    return {
      ...r,
      duracion: r.duracion ?? "",
      descripcion: r.descripcion ?? "",
      estado: r.estado ?? "pendiente",
      notas: r.notas ?? "",
      id_cliente: r.id_cliente ?? null,
    };
  }, []);

  const loadAll = useCallback(async () => {
    setIsLoading(true);
    const token = getToken();
    if (!token) {
      setMsg("Error: no hay token. Vuelve a iniciar sesión.");
      setIsLoading(false);
      return;
    }

    const clientService = new ClientService();
    const userService = new UserService();
    const registerService = new RegisterService();

    try {
      setMsg("");

      // 1) Obtener "me" (si existe método)
      let me = user;
      if (!me && typeof userService.getMe === "function") {
        me = await userService.getMe(token);
        setUser(me);
        localStorage.setItem("user", JSON.stringify(me));
      }

      const admin = !!(me?.isAdmin ?? me?.is_admin ?? false);

      // 2) Cargar clientes y registros en paralelo
      const [cls, regs] = await Promise.all([
        clientService.getClients(token),
        registerService.getRegisters(token), // ajusta si tu método se llama distinto
      ]);

      const clsArr = Array.isArray(cls) ? cls : [];
      const regsArr = Array.isArray(regs) ? regs : [];

      setClientes(clsArr);
      setRegistros(regsArr);

      // selección inicial
      if (regsArr.length > 0) {
        const r0 = regsArr[0];
        setSelected((prev) => (prev == null ? r0.id : prev));
        setEditForm((prev) => (prev == null ? hydrateEditForm(r0) : prev));
      } else {
        setSelected(null);
        setEditForm(null);
      }

      // 3) Si admin, cargar empleados
      if (admin && typeof userService.getUsers === "function") {
        const emps = await userService.getUsers(token);
        setEmpleados(Array.isArray(emps) ? emps : []);
      } else {
        setEmpleados([]);
      }
    } catch (e) {
      console.error(e);
      setMsg("Error cargando datos.");
    } finally {
      setIsLoading(false);
    }
  }, [user, hydrateEditForm]);

  useEffect(() => {
    loadAll();
  }, [loadAll]);

  const registrosFiltrados = useMemo(() => {
    const q = (filtroTexto || "").toLowerCase().trim();

    return registros.filter((r) => {
      const okTexto =
        !q ||
        (r.descripcion || "").toLowerCase().includes(q) ||
        (r.notas || "").toLowerCase().includes(q);

      const okEstado = !filtroEstado || r.estado === filtroEstado;

      const okCliente = !filtroCliente || String(r.id_cliente) === String(filtroCliente);

      return okTexto && okEstado && okCliente;
    });
  }, [registros, filtroTexto, filtroEstado, filtroCliente]);

  const formatFecha = (v) => {
    if (!v) return "";
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleString("es-ES");
  };

  const getEstadoClass = (estado) => {
    if (estado === "completada") return "estado-chip estado-completada";
    if (estado === "en_progreso") return "estado-chip estado-en_progreso";
    if (estado === "pendiente") return "estado-chip estado-pendiente";
    return "estado-chip";
  };

  const handleSelect = (r) => {
    setSelected(r.id);
    setEditForm(hydrateEditForm(r));
    setMsg("");
  };

  const handleEditChange = (e) => {
    const { name, value } = e.target;
    setEditForm((prev) => (prev ? { ...prev, [name]: value } : prev));
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();
    if (!editForm) return;

    const token = getToken();
    if (!token) return setMsg("Error: no hay token.");

    try {
      setMsg("");
      const registerService = new RegisterService();

      const payload = {
        ...editForm,
        duracion: editForm.duracion === "" ? null : Number(editForm.duracion),
        id_cliente:
          editForm.id_cliente === "" || editForm.id_cliente == null ? null : Number(editForm.id_cliente),
      };

      const updated = await registerService.setRegister(editForm.id, payload, token);
      const nuevo = updated || payload;

      setRegistros((prev) => prev.map((r) => (r.id === editForm.id ? { ...r, ...nuevo } : r)));
      setEditForm((prev) => (prev ? { ...prev, ...nuevo } : prev));

      setMsg("Registro actualizado ✅");
    } catch (err) {
      console.error(err);
      setMsg("Error al guardar cambios.");
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("¿Seguro que quieres eliminar este registro?")) return;

    const token = getToken();
    if (!token) return setMsg("Error: no hay token.");

    try {
      setMsg("");
      const registerService = new RegisterService();
      await registerService.deleteRegister(id, token);

      setRegistros((prev) => prev.filter((r) => r.id !== id));
      if (selected === id) {
        setSelected(null);
        setEditForm(null);
      }
      setMsg("Registro eliminado ✅");
    } catch (err) {
      console.error(err);
      setMsg("Error eliminando registro.");
    }
  };

  const colCount = isAdmin ? 7 : 6;

  if (isLoading) {
    return (
      <div className="page-container loading-screen" role="status" aria-live="polite" aria-busy="true">
        <div className="loader" aria-hidden="true" />
        <p className="loading-text">Cargando registros...</p>
      </div>
    );
  }

  return (
    <div className="page-container">
      <h2>Mis registros</h2>

      {msg && (
        <div className={msg.toLowerCase().includes("error") ? "error-message" : "success-message"}>
          {msg}
        </div>
      )}

      <div className="table-form-layout registros-layout">
        {/* IZQUIERDA: filtros + tabla */}
        <div>
          <div className="filters-card">
            <div className="filters-grid">
              <div className="field">
                <label>Buscar</label>
                <div className="input-icon">
                  <span>🔎</span>
                  <input
                    type="text"
                    placeholder="Buscar por descripción o notas..."
                    value={filtroTexto}
                    onChange={(e) => setFiltroTexto(e.target.value)}
                  />
                </div>
              </div>

              <div className="field">
                <label>Estado</label>
                <select value={filtroEstado} onChange={(e) => setFiltroEstado(e.target.value)}>
                  <option value="">Todos</option>
                  <option value="pendiente">Pendiente</option>
                  <option value="en_progreso">En progreso</option>
                  <option value="completada">Completada</option>
                </select>
              </div>

              <div className="field">
                <label>Cliente</label>
                <ClienteCombo clientes={clientes} value={filtroCliente} onChange={(id) => setFiltroCliente(id)} />
              </div>
            </div>

            <div className="filters-actions">
              <button
                type="button"
                className="btn-ghost"
                onClick={() => {
                  setFiltroTexto("");
                  setFiltroEstado("");
                  setFiltroCliente(null);
                }}
              >
                Limpiar filtros
              </button>
            </div>
          </div>

          <div className="table-scroll">
            <table className="pretty-table">
              <thead>
                <tr>
                  <th>Descripción</th>
                  <th>Duración</th>
                  <th>Estado</th>
                  <th>Cliente</th>
                  <th>Fecha</th>
                  {isAdmin && <th>Empleado</th>}
                  <th>Notas</th>
                </tr>
              </thead>

              <tbody>
                {registrosFiltrados.map((r) => (
                  <tr
                    key={r.id}
                    className={selected === r.id ? "selected-row" : ""}
                    onClick={() => handleSelect(r)}
                  >
                    <td>
                      {(r.descripcion || "").length > 30 ? (r.descripcion || "").slice(0, 30) + "..." : r.descripcion}
                    </td>
                    <td>{r.duracion}</td>
                    <td>
                      <span className={getEstadoClass(r.estado)}>{r.estado}</span>
                    </td>
                    <td>{clientes.find((c) => String(c.id) === String(r.id_cliente))?.nombre || r.id_cliente}</td>
                    <td>{formatFecha(r.fecha_creacion)}</td>

                    {isAdmin && (
                      <td>
                        {empleados.find((e) => String(e.id) === String(r.id_empleado))?.nombre || r.id_empleado}
                      </td>
                    )}

                    <td>{(r.notas || "").length > 30 ? (r.notas || "").slice(0, 30) + "..." : r.notas}</td>
                  </tr>
                ))}

                {registrosFiltrados.length === 0 && (
                  <tr>
                    <td colSpan={colCount} className="table-empty">
                      No hay registros con esos filtros.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* DERECHA: formulario edición */}
        <div>
          <form className="form-container" onSubmit={handleEditSubmit}>
            <h3>Editar Registro</h3>

            {editForm ? (
              <>
                <label>
                  Descripción:
                  <textarea
                    name="descripcion"
                    value={editForm.descripcion ?? ""}
                    onChange={handleEditChange}
                    required
                    placeholder="Descripción del registro"
                  />
                </label>

                <label>
                  Duración (min):
                  <input
                    name="duracion"
                    type="number"
                    value={editForm.duracion ?? ""}
                    onChange={handleEditChange}
                    required
                    placeholder="Duración en minutos"
                  />
                </label>

                <div className="field">
  <label>Cliente</label>
  <ClienteCombo
    clientes={clientes}
    value={editForm.id_cliente}
    onChange={(id) =>
      setEditForm((prev) => (prev ? { ...prev, id_cliente: id } : prev))
    }
  />
</div>
                <label>
                  Estado:
                  <select name="estado" value={editForm.estado ?? "pendiente"} onChange={handleEditChange}>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_progreso">En progreso</option>
                    <option value="completada">Completada</option>
                  </select>
                </label>

                <label>
                  Notas:
                  <textarea
                    name="notas"
                    value={editForm.notas ?? ""}
                    onChange={handleEditChange}
                    placeholder="Notas adicionales"
                  />
                </label>

                <button type="submit" className="btn-primary">
                  Guardar cambios
                </button>

                <button
                  type="button"
                  className="delete-btn delete-btn--spaced"
                  onClick={() => handleDelete(editForm.id)}
                >
                  Eliminar registro
                </button>
              </>
            ) : (
              <div className="error-message">Selecciona un registro para editar.</div>
            )}
          </form>
        </div>
      </div>
    </div>
  );
}