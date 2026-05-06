export class Client {
    constructor(id = null, name, email, phone = null, address = null, dni = null, pais = null, postal = null, poblacion = null, provincia = null) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.phone = phone;
        this.address = address;
        this.dni = dni;
        this.pais = pais;
        this.postal = postal;
        this.poblacion = poblacion;
        this.provincia = provincia;
    }
}