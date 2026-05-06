const API_URL = (import.meta.env.VITE_API_URL || "http://localhost:8000").replace(/\/+$/, "");

export default class RegisterService {
  constructor() {
    this.baseUrl = `${API_URL}/registers`;
  }
  async getRegisters(token) {
    return await fetch(this.baseUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.json())
      .catch(error => console.error('Error fetching registers:', error));
  }
  async getRegisterById(id, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.json())
      .catch(error => console.error(`Error fetching register with id ${id}:`, error));
  }
  async createRegister(register, token) {
    return await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
        body: JSON.stringify(register)
    })
      .then(response => response.json())
      .catch(error => console.error('Error creating register:', error));
  }
  async setRegister(id, register, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(register)
    })
      .then(response => response.json())
      .catch(error => console.error('Error setting register:', error));
  }
  async deleteRegister(id, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      method: 'DELETE',
        headers: {
        'Authorization': `Bearer ${token}`
      }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error deleting register with id ${id}: ${response.statusText}`);
            }
            return response.json();
        })
        .catch(error => console.error(`Error deleting register with id ${id}:`, error));
  }
}