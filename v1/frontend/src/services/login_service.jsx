import { LoginModel } from '../models/Login';

const API_URL = (import.meta.env.VITE_API_URL || "http://localhost:8000").replace(/\/+$/, "");

export async function login(loginModel) {
  const response = await fetch(`${API_URL}/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(loginModel),
  });
  if (!response.ok) {
    throw new Error('Login incorrecto');
  }
  return await response.json();
}
