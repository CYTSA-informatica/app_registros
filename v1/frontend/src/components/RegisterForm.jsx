import React, { useEffect, useMemo, useRef, useState, useCallback } from "react";
import "./components.css";
import ClientService from "../services/clients_service.jsx";

/* =====================================================
   ClienteCombo (sutil): sin botón azul, caret discreto
   - búsqueda + dropdown
   - no borra lo escrito al limpiar selección al teclear
   ===================================================== */
function ClienteCombo({ clientes = [], value, onChange, placeholder = "Buscar cliente..." }) {
  const [input, setInput] = useState("");
  const [showDropdown, setShowDropdown] = useState(false);
  const wrapperRef = useRef(null);

  const syncToValue = useCallback(() => {
    if (value === null || value === undefined || value === "") {
      // Si no hay selección, NO forces input si estás buscando
      setInput((prev) => prev);
      return;
    }
    const cli = clientes.find((c) => String(c.id) === String(value));
    setInput(cli ? cli.nombre : "");
  }, [value, clientes]);

  // Sincroniza el input con el value SOLO cuando el dropdown está cerrado
  useEffect(() => {
    if (showDropdown) return;
    if (value === null || value === undefined || value === "") {
      setInput("");
      return;
    }
    const cli = clientes.find((c) => String(c.id) === String(value));
    setInput(cli ? cli.nombre : "");
  }, [value, clientes, showDropdown]);

  // Cerrar al clicar fuera
  useEffect(() => {
    const onDocMouseDown = (e) => {
      if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
        setShowDropdown(false);
        // si había selección, volvemos a mostrar el nombre correcto
        syncToValue();
      }
    };
    document.addEventListener("mousedown", onDocMouseDown);
    return () => document.removeEventListener("mousedown", onDocMouseDown);
  }, [syncToValue]);

  const clientesFiltrados = useMemo(() => {
    const q = (input || "").toLowerCase().trim();
    if (!q) return clientes;
    return clientes.filter((c) => (c.nombre || "").toLowerCase().includes(q));
  }, [clientes, input]);

  return (
    <div ref={wrapperRef} className="cliente-combo cliente-combo--subtle">
      <div className="cliente-combo-row">
        <input
          className="cliente-combo-input"
          type="text"
          placeholder={placeholder}
          value={input}
          onFocus={() => setShowDropdown(true)}
          onChange={(e) => {
            setInput(e.target.value);
            setShowDropdown(true);
            // ✅ limpia selección pero SIN borrar lo escrito (porque showDropdown=true evita el sync)
            onChange?.("");
          }}
        />
        {/* caret discreto */}
        <span
          className="cliente-combo-caret"
          onMouseDown={(e) => {
            // evita perder foco
            e.preventDefault();
            setShowDropdown((v) => !v);
          }}
          aria-hidden="true"
        >
          ▾
        </span>
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
                  onChange?.(c.id);
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

export default function RegisterForm({ onSubmit }) {
  const [form, setForm] = useState({
    duracion: "",
    descripcion: "",
    notas: "",
    id_cliente: "",
  });

  const [clientes, setClientes] = useState([]);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchClientes = async () => {
      try {
        setError("");
        const token = localStorage.getItem("token");
        if (!token) return setError("Error: no hay token.");

        const clientService = new ClientService();
        const data = await clientService.getClients(token);
        setClientes(Array.isArray(data) ? data : []);
      } catch (err) {
        console.error(err);
        setError("No se pudieron cargar los clientes");
      }
    };
    fetchClientes();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    if (!form.id_cliente) {
      setError("Selecciona un cliente");
      return;
    }

    try {
      const user = JSON.parse(localStorage.getItem("user") || "null");
      const idEmpleado = user?.user_id ?? user?.id ?? null;

      const registro = {
        ...form,
        duracion: form.duracion === "" ? null : Number(form.duracion),
        estado: "pendiente",
        id_empleado: idEmpleado,
      };

      await onSubmit?.(registro);

      // reset
      setForm({ duracion: "", descripcion: "", notas: "", id_cliente: "" });
    } catch (err) {
      console.error(err);
      setError(err?.message || "Error al guardar");
    }
  };

  return (
    <div className="form-container">
      <form onSubmit={handleSubmit}>
        <h2>Nuevo Registro</h2>

        <div className="field">
          <label>Duración (min)</label>
          <input
            name="duracion"
            type="number"
            placeholder="Duración (min)"
            value={form.duracion}
            onChange={handleChange}
            required
          />
        </div>

        <div className="field">
          <label>Cliente</label>
          <ClienteCombo
            clientes={clientes}
            value={form.id_cliente}
            onChange={(id) => setForm((prev) => ({ ...prev, id_cliente: id }))}
          />
          
        </div>

        <div className="field">
          <label>Descripción</label>
          <textarea
            name="descripcion"
            placeholder="Descripción"
            value={form.descripcion}
            onChange={handleChange}
            required
          />
        </div>

        <div className="field">
          <label>Notas</label>
          <textarea name="notas" placeholder="Notas" value={form.notas} onChange={handleChange} />
        </div>

        <button type="submit" className="btn-primary" disabled={!form.id_cliente}>
          Guardar
        </button>

        {error && <div className="error-message">{error}</div>}
      </form>
    </div>
  );
}