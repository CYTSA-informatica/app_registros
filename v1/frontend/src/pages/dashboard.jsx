import { useState } from 'react';
import RegisterForm from '../components/RegisterForm.jsx';
import RegisterService from '../services/registers_service.jsx';
import MisRegistros from '../components/MisRegistros.jsx';
import Clientes from '../components/Clientes.jsx';
import Empleados from '../components/Empleados.jsx';

export default function Dashboard() {
  const user = JSON.parse(localStorage.getItem('user'));
  const [view, setView] = useState('registrar');
  const [mensaje, setMensaje] = useState('');

  const handleRegisterSubmit = async (registro) => {
    setMensaje('');
    try {
      const token = localStorage.getItem('token');
      const registerService = new RegisterService();
      await registerService.createRegister(registro, token);
      setMensaje('Registro guardado correctamente');
    } catch (err) {
      setMensaje('Error al guardar: ' + err.message);
    }
  };

  return (
    <>
      <header className="dashboard-header">
        <span>Dashboard</span>
        {user && <span >Empleado: {user.nombre}</span>}
        <button onClick={() => setView('registrar')}>Registrar</button>
        <button onClick={() => setView('misregistros')}>Mis Registros</button>
        {user && user.isAdmin && <>
          <button onClick={() => setView('clientes')}>Clientes</button>
          <button onClick={() => setView('empleados')}>Empleados</button>
        </>}
        <button className="logout-btn" onClick={() => {
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          window.location.reload();
        }}>Logout</button>
      </header>
      <main className="dashboard-main">
        {view === 'registrar' && <div >
          <RegisterForm onSubmit={handleRegisterSubmit} />
          {mensaje && <div >{mensaje}</div>}
        </div>}
        {view === 'misregistros' && <MisRegistros />}
        {view === 'clientes' && <Clientes />}
        {view === 'empleados' && <Empleados />}
      </main>
    </>
  );
}
