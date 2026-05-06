const API_URL = (import.meta.env.VITE_API_URL || "http://localhost:8000").replace(/\/+$/, "");

export default class UserService {
  constructor() {
    this.baseUrl = `${API_URL}/users`;
  }
  async getUsers(token) {
    return await fetch(this.baseUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.json())
      .catch(error => console.error('Error fetching users:', error));
  }
  async getUserById(id, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.json())
      .catch(error => console.error(`Error fetching user with id ${id}:`, error));
  }
  async getMyRegisters(id, token) {
    return await fetch(`${this.baseUrl}/${id}/registers`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
        .then(response => response.json())
        .catch(error => console.error(`Error fetching registers for user with id ${id}:`, error));
    }

  async createUser(user, token) {
    return await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
        body: JSON.stringify(user)
    })
      .then(response => response.json())
      .catch(error => console.error('Error creating user:', error));
  }
  async setUser(id, user, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(user)
    })
      .then(response => response.json())
      .catch(error => console.error('Error setting user:', error));
  }
  async deleteUser(id, token) {
    return await fetch(`${this.baseUrl}/${id}`, {
      method: 'DELETE',
        headers: {
        'Authorization': `Bearer ${token}`
      }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error deleting user with id ${id}: ${response.statusText}`);
            }
            return response.json();
        })
        .catch(error => console.error(`Error deleting user with id ${id}:`, error));
  }
}