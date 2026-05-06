// Login.jsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { LoginModel } from '../models/Login';
import { login } from '../services/login_service';

export default function Login({ onLogin }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const loginModel = new LoginModel(email, password);
      const data = await login(loginModel);
      // Guarda token y datos del usuario
      localStorage.setItem('token', data.access_token);
      localStorage.setItem('user', JSON.stringify({
        nombre: data.name,
        email: data.email,
        isAdmin: data.isAdmin,
        user_id: data.user_id
      }));
      if (onLogin) onLogin(data);
      navigate('/dashboard');
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="login-page">
    <form className="login-form" onSubmit={handleSubmit}>
      <h2>Login</h2>
      <input
        type="email"
        placeholder="Email"
        value={email}
        onChange={e => setEmail(e.target.value)}
        required
      />
      <input
        type="password"
        placeholder="Contraseña"
        value={password}
        onChange={e => setPassword(e.target.value)}
        required
      />
      <button type="submit" >Entrar</button>
      {error && <div >{error}</div>}
    </form>
    </div>
  );
}
