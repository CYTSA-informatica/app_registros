const API_URL = (import.meta.env.VITE_API_URL || "http://localhost:8000").replace(/\/+$/, "");

export default class ClientService {
  constructor() {
    this.baseUrl = `${API_URL}/clients`;
  }
  async getClients(token) {
    return await fetch(this.baseUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.json())
      .catch(error => console.error('Error fetching clients:', error));
  }
  async getClientById(id, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.json())
      .catch(error => console.error(`Error fetching client with id ${id}:`, error));
  }
  async createClient(client, token) {
    return await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
        body: JSON.stringify(client)
    })
      .then(response => response.json())
      .catch(error => console.error('Error creating client:', error));
  }
  async setClient(id, client, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(client)
    })
      .then(response => response.json())
      .catch(error => console.error('Error setting client:', error));
  }
  async deleteClient(id, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      method: 'DELETE',
        headers: {
        'Authorization': `Bearer ${token}`
      }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error deleting client with id ${id}: ${response.statusText}`);
            }
            return response.json();
        })
        .catch(error => console.error(`Error deleting client with id ${id}:`, error));
    }
}