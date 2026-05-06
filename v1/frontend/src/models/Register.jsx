export class Register {
    constructor(id = null, duracion, descripcion, estado, notas, id_empleado, id_cliente, fecha_creacion = null) {
        this.id = id;
        this.duracion = duracion;
        this.descripcion = descripcion;
        this.estado = estado;
        this.notas = notas;
        this.id_empleado = id_empleado;
        this.id_cliente = id_cliente;
        this.fecha_creacion = fecha_creacion;
    }
}