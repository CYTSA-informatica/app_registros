export class User {
    constructor(id = null, name, email, contra, isAdmin = false) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.contra = contra;
        this.isAdmin = isAdmin;
    }
}