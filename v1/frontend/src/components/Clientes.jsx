import React, { useCallback, useEffect, useMemo, useState } from "react";
import "./components.css";
import "../pages/pages.css";
import ClientService from "../services/clients_service.jsx";

/* =========================
   CrearCliente
   ========================= */
function CrearCliente({ onCancel, onCreated }) {
  const [form, setForm] = useState({
    nombre: "",
    email: "",
    phone: "",
    address: "",
    dni: "",
    pais: "",
    postal: "",
    poblacion: "",
    provincia: "",
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
      const clientService = new ClientService();
      const created = await clientService.createClient(form, token);
      onCreated?.(created);
    } catch (err) {
      console.error(err);
      setMsg("Error al crear: " + (err?.message ?? String(err)));
    }
  };

  return (
    <form className="form-container" onSubmit={handleSubmit}>
      <h3>Crear Cliente</h3>

      <div className="form-row-2">
        <label>
          Nombre:
          <input
            name="nombre"
            value={form.nombre}
            onChange={handleChange}
            required
            placeholder="Nombre del cliente"
          />
        </label>

        <label>
          Email:
          <input
            name="email"
            type="email"
            value={form.email}
            onChange={handleChange}
            required
            placeholder="Correo electrónico"
          />
        </label>
      </div>

      <div className="form-row-2">
        <label>
          Teléfono:
          <input
            name="phone"
            value={form.phone}
            onChange={handleChange}
            placeholder="Teléfono"
          />
        </label>

        <label>
          Dirección:
          <input
            name="address"
            value={form.address}
            onChange={handleChange}
            placeholder="Dirección"
          />
        </label>
      </div>

      <div className="form-row-2">
        <label>
          DNI:
          <input
            name="dni"
            value={form.dni}
            onChange={handleChange}
            placeholder="DNI"
          />
        </label>

        <label>
          País:
          <input
            name="pais"
            value={form.pais}
            onChange={handleChange}
            placeholder="País"
          />
        </label>
      </div>

      <div className="form-row-2">
        <label>
          Código Postal:
          <input
            name="postal"
            value={form.postal}
            onChange={handleChange}
            placeholder="Código Postal"
          />
        </label>

        <label>
          Población:
          <input
            name="poblacion"
            value={form.poblacion}
            onChange={handleChange}
            placeholder="Población"
          />
        </label>
      </div>

      <label>
        Provincia:
        <input
          name="provincia"
          value={form.provincia}
          onChange={handleChange}
          placeholder="Provincia"
        />
      </label>

      <div className="form-actions">
        <button type="submit" className="btn-primary">
          Aceptar
        </button>
        <button type="button" className="btn-ghost" onClick={onCancel}>
          Cancelar
        </button>
      </div>

      {msg && (
        <div className={msg.startsWith("Error") ? "error-message" : "success-message"}>
          {msg}
        </div>
      )}
    </form>
  );
}

export default function Clientes() {
  const [clientes, setClientes] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selected, setSelected] = useState(null);
  const [editForm, setEditForm] = useState(null);
  const [msg, setMsg] = useState("");
  const [filtroNombre, setFiltroNombre] = useState("");
  const [showCreate, setShowCreate] = useState(false);

  const normalizarCliente = useCallback((client) => {
    if (!client) return null;
    return {
      ...client,
      nombre: client.nombre ?? "",
      email: client.email ?? "",
      phone: client.phone ?? client.telefono ?? "",
      address: client.address ?? client.direccion ?? "",
      dni: client.dni ?? "",
      pais: client.pais ?? "",
      postal: client.postal ?? "",
      poblacion: client.poblacion ?? "",
      provincia: client.provincia ?? "",
    };
  }, []);

  const loadClients = useCallback(
    async (keepSelectedId = null) => {
      setIsLoading(true);
      const token = localStorage.getItem("token");
      if (!token) {
        setMsg("Error: no hay token. Vuelve a iniciar sesión.");
        setIsLoading(false);
        return;
      }

      try {
        const clientService = new ClientService();
        const cli = await clientService.getClients(token);
        const lista = Array.isArray(cli) ? cli : [];

        setClientes(lista);

        const idToSelect = keepSelectedId ?? (lista.length ? lista[0].id : null);

        if (idToSelect != null) {
          const found = lista.find((x) => String(x.id) === String(idToSelect)) ?? lista[0];
          setSelected(found?.id ?? null);
          setEditForm(normalizarCliente(found));
        } else {
          setSelected(null);
          setEditForm(null);
        }
      } catch (err) {
        console.error(err);
        setMsg("Error cargando clientes.");
      } finally {
        setIsLoading(false);
      }
    },
    [normalizarCliente]
  );

  useEffect(() => {
    loadClients();
  }, [loadClients]);

  const clientesFiltrados = useMemo(() => {
    const f = (filtroNombre || "").toLowerCase().trim();
    if (!f) return clientes;

    return clientes.filter((c) => {
      const nombre = (c.nombre ?? "").toLowerCase();
      const email = (c.email ?? "").toLowerCase();
      return nombre.includes(f) || email.includes(f);
    });
  }, [clientes, filtroNombre]);

  const handleSelect = (client) => {
    setSelected(client.id);
    setEditForm(normalizarCliente(client));
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
      const clientService = new ClientService();
      await clientService.setClient(editForm.id, editForm, token);
      setMsg("Cliente actualizado ✅");
      await loadClients(editForm.id);
    } catch (err) {
      console.error(err);
      setMsg("Error al actualizar: " + (err?.message ?? String(err)));
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("¿Seguro que quieres eliminar este cliente?")) return;

    const token = localStorage.getItem("token");
    if (!token) return setMsg("Error: no hay token.");

    try {
      setMsg("");
      const clientService = new ClientService();
      await clientService.deleteClient(id, token);

      setMsg("Cliente eliminado ✅");
      const keep = selected === id ? null : selected;
      await loadClients(keep);
    } catch (err) {
      console.error(err);
      setMsg("Error al eliminar: " + (err?.message ?? String(err)));
    }
  };

  const COLS = 9;

  if (isLoading) {
    return (
      <div className="page-container loading-screen" role="status" aria-live="polite" aria-busy="true">
        <div className="loader" aria-hidden="true" />
        <p className="loading-text">Cargando clientes...</p>
      </div>
    );
  }

  return (
    <div className="page-container">
      <h2>Clientes</h2>

      {msg && (
        <div className={msg.startsWith("Error") ? "error-message" : "success-message"}>
          {msg}
        </div>
      )}

      <div className="table-container">
        {/* IZQUIERDA: filtros + tabla */}
        <div>
          <div className="filter-bar">
            <label>
              Nombre o Correo:
              <input
                type="text"
                placeholder="Buscar cliente..."
                value={filtroNombre}
                onChange={(e) => setFiltroNombre(e.target.value)}
              />
            </label>

            <button type="button" onClick={() => setShowCreate(true)}>
              Crear cliente
            </button>
          </div>

          <div className="table-scroll">
            <table className="pretty-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Teléfono</th>
                  <th>Dirección</th>
                  <th>DNI</th>
                  <th>País</th>
                  <th>Postal</th>
                  <th>Población</th>
                  <th>Provincia</th>
                </tr>
              </thead>

              <tbody>
                {clientesFiltrados.map((c) => (
                  <tr
                    key={c.id}
                    className={selected === c.id ? "selected-row" : ""}
                    onClick={() => handleSelect(c)}
                  >
                    <td>{c.nombre ?? ""}</td>
                    <td>{c.email ?? ""}</td>
                    <td>{c.phone ?? c.telefono ?? ""}</td>
                    <td>{c.address ?? c.direccion ?? ""}</td>
                    <td>{c.dni ?? ""}</td>
                    <td>{c.pais ?? ""}</td>
                    <td>{c.postal ?? ""}</td>
                    <td>{c.poblacion ?? ""}</td>
                    <td>{c.provincia ?? ""}</td>
                  </tr>
                ))}

                {clientesFiltrados.length === 0 && (
                  <tr>
                    <td colSpan={COLS} className="table-empty">
                      No hay clientes con ese filtro.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* DERECHA: crear / editar */}
        <div>
          {showCreate ? (
            <CrearCliente
              onCancel={() => setShowCreate(false)}
              onCreated={async (createdMaybe) => {
                setShowCreate(false);
                const createdId = createdMaybe?.id ?? null;
                await loadClients(createdId);
                setMsg("Cliente creado ✅");
              }}
            />
          ) : (
            <form className="form-container" onSubmit={handleEditSubmit}>
              <h3>Editar Cliente</h3>

              {editForm ? (
                <>
                  <div className="form-row-2">
                    <label>
                      Nombre:
                      <input
                        name="nombre"
                        value={editForm.nombre ?? ""}
                        onChange={handleEditChange}
                        required
                        placeholder="Nombre del cliente"
                      />
                    </label>

                    <label>
                      Email:
                      <input
                        name="email"
                        type="email"
                        value={editForm.email ?? ""}
                        onChange={handleEditChange}
                        required
                        placeholder="Correo electrónico"
                      />
                    </label>
                  </div>

                  <div className="form-row-2">
                    <label>
                      Teléfono:
                      <input
                        name="phone"
                        value={editForm.phone ?? ""}
                        onChange={handleEditChange}
                        placeholder="Teléfono"
                      />
                    </label>

                    <label>
                      Dirección:
                      <input
                        name="address"
                        value={editForm.address ?? ""}
                        onChange={handleEditChange}
                        placeholder="Dirección"
                      />
                    </label>
                  </div>

                  <div className="form-row-2">
                    <label>
                      DNI:
                      <input
                        name="dni"
                        value={editForm.dni ?? ""}
                        onChange={handleEditChange}
                        placeholder="DNI"
                      />
                    </label>

                    <label>
                      País:
                      <input
                        name="pais"
                        value={editForm.pais ?? ""}
                        onChange={handleEditChange}
                        placeholder="País"
                      />
                    </label>
                  </div>

                  <div className="form-row-2">
                    <label>
                      Código Postal:
                      <input
                        name="postal"
                        value={editForm.postal ?? ""}
                        onChange={handleEditChange}
                        placeholder="Código Postal"
                      />
                    </label>

                    <label>
                      Población:
                      <input
                        name="poblacion"
                        value={editForm.poblacion ?? ""}
                        onChange={handleEditChange}
                        placeholder="Población"
                      />
                    </label>
                  </div>

                  <label>
                    Provincia:
                    <input
                      name="provincia"
                      value={editForm.provincia ?? ""}
                      onChange={handleEditChange}
                      placeholder="Provincia"
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
                    Eliminar cliente
                  </button>
                </>
              ) : (
                <div className="error-message">Selecciona un cliente para editar.</div>
              )}
            </form>
          )}
        </div>
      </div>
    </div>
  );
}