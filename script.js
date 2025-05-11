// Función para hacer un elemento arrastrable
function hacerArrastrable(elemento) {
    let offsetX = 0, offsetY = 0, arrastrando = false;
    elemento.addEventListener("mousedown", function(e) {
        arrastrando = true;
        offsetX = e.clientX - elemento.getBoundingClientRect().left;
        offsetY = e.clientY - elemento.getBoundingClientRect().top;
        elemento.style.cursor = "grabbing";
        elemento.style.zIndex = 9999;
    });
    document.addEventListener("mousemove", function(e) {
        if (!arrastrando) return;
        elemento.style.left = (e.clientX - offsetX) + "px";
        elemento.style.top = (e.clientY - offsetY) + "px";
    });
    document.addEventListener("mouseup", function() {
        arrastrando = false;
        elemento.style.cursor = "grab";
        elemento.style.zIndex = 1;
    });
}

// Recolectar la información de las clases en el DOM
function obtenerClases() {
    const listaClases = [];
    document.querySelectorAll("article.draggable").forEach(function(articulo) {
        const nombre = articulo.querySelector(".nombre")?.textContent.trim() || "Clase";
        const propiedades = [];
        articulo.querySelectorAll(".propiedades ul li").forEach(function(li) {
            propiedades.push(li.textContent.trim());
        });
        const metodos = [];
        articulo.querySelectorAll(".metodos ul li").forEach(function(li) {
            metodos.push(li.textContent.trim());
        });
        const posX = parseInt(articulo.style.left, 10) || 250;
        const posY = parseInt(articulo.style.top, 10) || 250;
        listaClases.push({
            className: nombre,
            properties: propiedades,
            methods: metodos,
            x: posX,
            y: posY
        });
    });
    return listaClases;
}

function listarClases() {
    console.log(obtenerClases());
}

function guardarClases() {
    const datos = obtenerClases();
    fetch('index.php?ajax=guardar_clases', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(datos)
    })
    .then(respuesta => respuesta.text())
    .then(texto => {
        alert(texto);
        console.log(texto);
    })
    .catch(error => console.error("Error al guardar clases:", error));
}

function cargarClases() {
    fetch('index.php?ajax=cargar_clases')
    .then(res => res.json())
    .then(data => {
        if (Array.isArray(data)) {
            document.querySelectorAll("article.draggable").forEach(el => el.remove());
            data.forEach(function(clase) {
                const plantilla = document.getElementById("plantilla-clase");
                const clon = plantilla.content.cloneNode(true);
                const articulo = clon.querySelector("article");
                
                // Asignar nombre
                articulo.querySelector(".nombre").textContent = clase.className;
                
                // Procesar propiedades
                const ulProp = articulo.querySelector(".propiedades ul");
                ulProp.innerHTML = "";
                (clase.properties || []).forEach(function(prop) {
                    const li = document.createElement("li");
                    li.textContent = prop;
                    ulProp.appendChild(li);
                });
                
                // Procesar métodos
                const ulMet = articulo.querySelector(".metodos ul");
                ulMet.innerHTML = "";
                (clase.methods || []).forEach(function(met) {
                    const li = document.createElement("li");
                    li.textContent = met;
                    ulMet.appendChild(li);
                });
                
                articulo.style.left = (clase.x || 250) + "px";
                articulo.style.top  = (clase.y || 250) + "px";
                document.querySelector("main").appendChild(articulo);
                hacerArrastrable(articulo);
            });
        } else if (data.error) {
            console.warn(data.error);
        }
    })
    .catch(error => console.error("Error cargando clases:", error));
}

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("agregarClase").addEventListener("click", function(e) {
        e.preventDefault();
        const plantilla = document.getElementById("plantilla-clase");
        const clon = plantilla.content.cloneNode(true);
        const articulo = clon.querySelector("article");
        document.querySelector("main").appendChild(articulo);
        hacerArrastrable(articulo);
    });
    document.getElementById("mostrarClases").addEventListener("click", function(e) {
        e.preventDefault();
        listarClases();
    });
    document.getElementById("guardarClases").addEventListener("click", function(e) {
        e.preventDefault();
        guardarClases();
    });
    cargarClases();
});